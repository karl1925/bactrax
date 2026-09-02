<?php

namespace App\Http\Controllers;

use App\Models\UserSignature;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use RuntimeException;
use Illuminate\Support\Facades\Log;

class ProfileSignatureController extends Controller
{
    public function edit(Request $request): View
    {
        return view('users.signatures.edit', [
            'user' => $request->user(),
        ]);
    }

    public function setFields(Request $request): JsonResponse
    {
        if (is_string($request->input('fields'))) {
            $decoded = json_decode($request->input('fields'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['fields' => $decoded]);
            }
        }

        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:51200'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.field' => ['required', 'string'],
            'fields.*.page' => ['required', 'integer', 'min:1'],
            'fields.*.display_x' => ['required_without:fields.*.x1', 'nullable', 'numeric'],
            'fields.*.display_y' => ['required_without:fields.*.y1', 'nullable', 'numeric'],
            'fields.*.display_width' => ['required_without:fields.*.x2', 'nullable', 'numeric', 'gt:0'],
            'fields.*.display_height' => ['required_without:fields.*.y2', 'nullable', 'numeric', 'gt:0'],
            'fields.*.display_page_width' => ['required_without:fields.*.x2', 'nullable', 'numeric', 'gt:0'],
            'fields.*.display_page_height' => ['required_without:fields.*.y2', 'nullable', 'numeric', 'gt:0'],
            'fields.*.x1' => ['nullable', 'numeric'],
            'fields.*.y1' => ['nullable', 'numeric'],
            'fields.*.x2' => ['nullable', 'numeric'],
            'fields.*.y2' => ['nullable', 'numeric'],
            'fields.*.name' => ['nullable', 'string'],
        ]);

        try {
            $user = $request->user();
            $defaultSignerName = $user->signature?->p12_name;

            $pdfFile = $request->file('pdf');
            $pdfContents = file_get_contents($pdfFile->getRealPath());

            if ($pdfContents === false) {
                throw new RuntimeException('Unable to read the uploaded PDF.');
            }

            $transformedFields = array_map(
                fn($field) => $this->transformField($field, $pdfContents, $defaultSignerName),
                $request->input('fields')
            );

            $response = Http::timeout(120)
                ->attach('pdf', $pdfContents, $pdfFile->getClientOriginalName())
                ->attach('fields', json_encode($transformedFields))
                ->post($this->signerUrl('setFields'));

            if (!$response->successful()) {
                return response()->json(['success' => false, 'message' => $this->extractErrorMessage($response)], 422);
            }

            $preparedPdfBytes = $response->body();
            if (empty($preparedPdfBytes)) {
                throw new RuntimeException('The service returned an empty PDF document.');
            }

            $staticPath = $this->storePdf($user->id, $preparedPdfBytes, 'prepared');

            return response()->json([
                'success'      => true,
                'message'      => 'PDF signature fields set successfully.',
                'path'         => $staticPath,
                'fields'       => $transformedFields,
                'fields_count' => count($transformedFields),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function signDocument(Request $request): JsonResponse
    {
        $request->validate([
            'pdf'   => ['required', 'file', 'mimes:pdf', 'max:51200'],
            'field' => ['nullable', 'string'],
            'page'  => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $user = $request->user();
            $signature = $user->signature;

            if (!$signature?->p12_path) {
                throw ValidationException::withMessages([
                    'signature' => 'Please configure your signature certificate first.',
                ]);
            }

            $signee = $signature->p12_name;
            if (empty($signee)) {
                throw new RuntimeException('The certificate signer name (p12_name) is missing in your signature settings.');
            }

            $credentials = $this->getSigningCredentials($signature);
            $pdfFile = $request->file('pdf');

            $imageDisk = Storage::disk('private')->exists($signature->signature_image_path ?? '') ? 'private' : 'local';
            $signatureImagePath = Storage::disk($imageDisk)->path($signature->signature_image_path ?? '');

            if (!is_file($signatureImagePath)) {
                throw new RuntimeException('Configured signature image was not found.');
            }

            $fieldName = $request->input('field', 'Signature_1');

            $response = Http::timeout(120)
                ->attach('pdf', file_get_contents($pdfFile->getRealPath()), $pdfFile->getClientOriginalName())
                ->attach('p12', file_get_contents($credentials['path']), 'certificate.p12')
                ->attach('signature', file_get_contents($signatureImagePath), 'signature.png')
                ->post($this->signerUrl('sign'), [
                    'password' => (string) $credentials['password'],
                    'signee'   => (string) $signee,
                    'field'    => (string) $fieldName,
                ]);

            if (!$response->successful()) {
                return response()->json(['success' => false, 'message' => $this->extractErrorMessage($response)], 422);
            }

            $signedContents = $response->body();
            if (empty($signedContents)) {
                throw new RuntimeException('The signing service did not return a signed PDF.');
            }

            $signedPath = $this->storePdf($user->id, $signedContents, 'signed');

            return response()->json([
                'success' => true,
                'message' => 'PDF signed successfully.',
                'url'     => route('user.signature.signed.download'),
                'path'    => $signedPath,
                'signer'  => $signee,
                'field'   => $fieldName,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function downloadSigned(Request $request)
    {
        $userId = $request->user()->id;
        $signedPath = "signatures/{$userId}/signed.pdf";

        if (!Storage::disk('private')->exists($signedPath)) {
            return response()->json(['message' => 'Signed PDF document not found.'], 404);
        }

        return Storage::disk('private')->download(
            $signedPath,
            'signed_document.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function getPreparedPdf(Request $request)
    {
        try {
            $filePath = "signatures/{$request->user()->id}/prepared.pdf";

            if (!Storage::disk('private')->exists($filePath)) {
                Log::warning("Prepared PDF not found at path: {$filePath}");
                return response()->json(['message' => 'Prepared PDF not found.'], 404);
            }

            return Storage::disk('private')->response($filePath, 'prepared.pdf', [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            Log::error("GET PREPARED PDF ERROR: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Internal server error while retrieving prepared PDF.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function deletePreparedFile(Request $request)
    {
        $filePath = "signatures/{$request->user()->id}/prepared.pdf";

        if (Storage::disk('private')->exists($filePath)) {
            Storage::disk('private')->delete($filePath);

            return response()->json([
                'success' => true,
                'message' => 'Prepared file deleted successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'File not found or already removed.'
        ], 404);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->handleP12Upload($request, 'Your P12 digital signing certificate has been configured successfully.');
    }

    public function replace(Request $request): RedirectResponse
    {
        return $this->handleP12Upload($request, 'Your P12 certificate has been replaced successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate(['p12_password' => ['required', 'string', 'max:1000']]);

        $signature = $request->user()->signature;

        if (!$signature?->p12_path) {
            return back()->withErrors(['p12_certificate' => 'Please upload a P12 certificate before configuring a password.']);
        }

        $validation = $this->validateP12($request, $signature->p12_path, $request->p12_password, true);

        if (!$validation['success']) {
            return back()->withErrors(['p12_certificate' => $validation['message']])->withInput();
        }

        return redirect()
            ->route('user.signature.edit')
            ->with('success', 'P12 password validated and saved securely.');
    }

    public function deletePassword(Request $request): RedirectResponse
    {
        $signature = $request->user()->signature;
        if ($signature) {
            $signature->update(['p12_password' => null]);
        }

        return redirect()
            ->route('user.signature.edit')
            ->with('success', 'P12 password unset. From now on, you will be prompted for your P12 password each time you sign a document.');
    }

    public function updateAppearance(Request $request): RedirectResponse
    {
        $request->validate(['signature_image' => ['nullable', 'file', 'mimes:png', 'max:2048']]);

        $user = $request->user();
        $signature = $user->signature;

        if (!$signature) {
            return back()->withErrors(['signature_image' => 'Please configure your P12 certificate first.']);
        }

        if ($request->hasFile('signature_image')) {
            if ($signature->signature_image_path && Storage::disk('private')->exists($signature->signature_image_path)) {
                Storage::disk('private')->delete($signature->signature_image_path);
            }

            $path = "signatures/{$user->id}/signature.png";
            Storage::disk('private')->putFileAs("signatures/{$user->id}", $request->file('signature_image'), 'signature.png');
            $signature->update(['signature_image_path' => $path]);
        }

        return back()->with('success', 'Signature appearance updated successfully.');
    }

    public function image(Request $request)
    {
        $signature = $request->user()->signature;
        abort_unless($signature && filled($signature->signature_image_path), 404);

        return Storage::disk('private')->response($signature->signature_image_path);
    }

    private function handleP12Upload(Request $request, string $successMessage): RedirectResponse
    {
        $request->validate([
            'p12_certificate' => ['required', 'file', 'max:10240'],
            'p12_password'    => ['required', 'string'],
        ]);

        $validation = $this->validateP12(
            $request,
            $request->file('p12_certificate'),
            $request->input('p12_password'),
            $request->has('save_p12_password')
        );

        if (!$validation['success']) {
            return back()->withErrors(['p12_certificate' => $validation['message']])->withInput();
        }

        return redirect()->route('user.signature.edit')->with('success', $successMessage);
    }

    private function validateP12(Request $request, UploadedFile|string $p12, string $password, bool $remember): array
    {
        $user = $request->user();
        $updatePassOnly = !($p12 instanceof UploadedFile);

        try {
            if (!$updatePassOnly) {
                $contents = file_get_contents($p12->getRealPath());
                $filename = $p12->getClientOriginalName();
            } else {
                $contents = Storage::disk('private')->get($p12);
                $filename = basename($p12);
            }

            if ($contents === false) {
                return ['success' => false, 'status' => 500, 'message' => 'Unable to read the P12 certificate.'];
            }

            $response = Http::timeout(30)
                ->attach('p12', $contents, $filename)
                ->post($this->signerUrl('verify'), ['password' => $password]);
        } catch (\Throwable $e) {
            return ['success' => false, 'status' => 503, 'message' => 'Unable to contact the PDF signing service.'];
        }

        if (!$response->successful()) {
            return [
                'success' => false,
                'status'  => 422,
                'message' => $this->extractErrorMessage($response),
            ];
        }

        $result = $response->json();
        if (!($result['success'] ?? false) || !($result['valid'] ?? false)) {
            return [
                'success' => false,
                'status'  => 422,
                'message' => $result['message'] ?? $result['detail'] ?? 'The P12 certificate or password is invalid.',
            ];
        }

        $certificate = $result['certificate'];
        $p12Path = "signatures/{$user->id}/certificate.p12";

        if ($updatePassOnly) {
            $user->signature->update(['p12_password' => Crypt::encryptString($password)]);
        } else {
            if ($user->signature?->p12_path && $user->signature->p12_path !== $p12Path) {
                Storage::disk('private')->delete($user->signature->p12_path);
            }

            Storage::disk('private')->putFileAs("signatures/{$user->id}", $p12, 'certificate.p12');

            $user->signature()->updateOrCreate([], [
                'p12_path'            => $p12Path,
                'certificate_subject' => $certificate['subject'] ?? null,
                'certificate_issuer'  => $certificate['issuer'] ?? null,
                'p12_name'            => $certificate['common_name'] ?? null,
                'certificate_serial'  => $certificate['serial_number'] ?? null,
                'valid_from'          => $certificate['not_valid_before'],
                'valid_until'         => $certificate['not_valid_after'] ?? null,
                'p12_password'        => $remember ? Crypt::encryptString($password) : null,
            ]);
        }

        return [
            'success'                 => true,
            'status'                  => 200,
            'message'                 => 'P12 certificate validated successfully.',
            'certificate'             => $result['certificate'] ?? null,
            'has_private_key'         => $result['has_private_key'] ?? false,
            'additional_certificates' => $result['additional_certificates'] ?? 0,
        ];
    }

    protected function getSigningCredentials(UserSignature $signature): array
    {
        if (!$signature->p12_path) {
            throw ValidationException::withMessages([
                'p12' => 'The user has not configured a P12 signing certificate.',
            ]);
        }

        return [
            'path'     => Storage::disk('private')->path($signature->p12_path),
            'password' => $signature->p12_password ? Crypt::decryptString($signature->p12_password) : null,
        ];
    }

    private function transformField(array $field, string $pdfContents, ?string $defaultSignerName): array
    {
        $fieldName = is_array($field['field']) ? ($field['field'][0] ?? 'Signature1') : $field['field'];
        $page = (int) ($field['page'] ?? 1);

        [$pdfWidth, $pdfHeight] = $this->getPdfDimensions($pdfContents, $page);

        if (isset($field['x1'], $field['y1'], $field['x2'], $field['y2'])) {
            $rawX1 = (float) $field['x1'];
            $rawY1 = (float) $field['y1'];
            $rawX2 = (float) $field['x2'];
            $rawY2 = (float) $field['y2'];
        } else {
            $scaleX = $pdfWidth / (float) $field['display_page_width'];
            $scaleY = $pdfHeight / (float) $field['display_page_height'];

            $rawX1 = (float) $field['display_x'] * $scaleX;
            $rawX2 = ((float) $field['display_x'] + (float) $field['display_width']) * $scaleX;
            $rawY1 = ((float) $field['display_page_height'] - ((float) $field['display_y'] + (float) $field['display_height'])) * $scaleY;
            $rawY2 = ((float) $field['display_page_height'] - (float) $field['display_y']) * $scaleY;
        }

        $x1 = max(0.0, min($pdfWidth, min($rawX1, $rawX2)));
        $x2 = max(0.0, min($pdfWidth, max($rawX1, $rawX2)));
        $y1 = max(0.0, min($pdfHeight, min($rawY1, $rawY2)));
        $y2 = max(0.0, min($pdfHeight, max($rawY1, $rawY2)));

        if (($x2 - $x1) < 10.0) {
            $x2 = min($pdfWidth, $x1 + 20.0);
        }
        if (($y2 - $y1) < 10.0) {
            $y2 = min($pdfHeight, $y1 + 20.0);
        }

        return [
            'field' => (string) $fieldName,
            'page'  => $page,
            'x1'    => round($x1, 2),
            'y1'    => round($y1, 2),
            'x2'    => round($x2, 2),
            'y2'    => round($y2, 2),
            'name'  => $field['name'] ?? $defaultSignerName,
        ];
    }

    private function getPdfDimensions(string $pdfContents, int $page = 1): array
    {
        if (preg_match_all('/\/MediaBox\s*\[\s*([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.\-]+)\s*\]/', $pdfContents, $matches, PREG_SET_ORDER)) {
            $m = $matches[$page - 1] ?? $matches[0];
            $w = abs((float)$m[3] - (float)$m[1]);
            $h = abs((float)$m[4] - (float)$m[2]);
            if ($w > 0 && $h > 0) {
                return [$w, $h];
            }
        }
        return [612.0, 792.0];
    }

    private function storePdf(int $userId, string $contents, string $type = 'prepared'): string
    {
        $filename = ($type === 'signed') ? 'signed.pdf' : 'prepared.pdf';
        $path = "signatures/{$userId}/{$filename}";

        Storage::disk('private')->put($path, $contents);

        return $path;
    }

    private function signerUrl(string $endpoint): string
    {
        $baseUrl = rtrim(config('services.pdf_signer.url'), '/');
        if (empty($baseUrl)) {
            throw new RuntimeException('PDF signing service URL is not configured.');
        }

        return "{$baseUrl}/{$endpoint}";
    }

    public function downloadCertificate(Request $request)
    {
        $signature = $request->user()->signature;

        abort_unless(
            $signature && $signature->p12_path && Storage::disk('private')->exists($signature->p12_path),
            404,
            'Certificate file not found.'
        );

        return Storage::disk('private')->download(
            $signature->p12_path,
            'certificate.p12'
        );
    }

    private function extractErrorMessage($response): string
    {
        return $response->json('detail')
            ?? $response->json('message')
            ?? $response->body()
            ?? 'An unknown error occurred with the PDF signing service.';
    }
}
