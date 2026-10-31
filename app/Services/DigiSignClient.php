<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Server-to-server client for https://digisign.dictr2.cloud
 *
 *   POST /digistat  (form: email)                 -> {"registered":bool,"p12_uploaded":bool,"require_pw":bool}
 *   POST /digisign  (multipart: email, pdf, fields[, password])
 *        fields = JSON [{"field":"Signature_1","page":1,"x1":350,"y1":80,"x2":550,"y2":140,"name":"..."}]
 *        -> application/pdf body, headers X-PDF-Fields-Success: true, X-PDF-Fields-Count: n
 *
 * The browser never calls DigiSign: the PDF is read from storage here, and the P12 password
 * is forwarded once and never stored or logged.
 */
class DigiSignClient
{
    /** @return array{ok:bool,message:string,password_required:bool} */
    public function digicheck(User $user): array
    {
        try {
            $response = $this->http()->asForm()->post($this->url('/digicheck'), ['email' => $user->email]);
        } catch (ConnectionException) {
            throw new DigiSignException('Unable to reach DigiSign. Please try again later.', 503);
        }
        if ($response->failed()) {
            throw new DigiSignException($this->errorMessage($response, 'DigiSign could not check your signing status.'), 502);
        }

        $json = (array) $response->json();

        $registered = filter_var($json['registered'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $p12 = filter_var($json['p12_uploaded'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $requirePw = filter_var($json['require_pw'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $registered) return ['ok' => false, 'message' => 'You are not registered in DigiSign yet.', 'password_required' => false];
        if (! $p12) return ['ok' => false, 'message' => 'You have not uploaded your P12 certificate in DigiSign yet.', 'password_required' => false];

        return ['ok' => true, 'message' => '', 'password_required' => $requirePw];
    }

    /**
     * @param  array<int,array<string,mixed>>  $fields  signature field definitions (see class doc)
     * @return array{bytes:string,count:int}
     */
    public function digisign(User $user, string $pdfBytes, string $fileName, array $fields, ?string $password): array
    {
        $payload = ['email' => $user->email, 'fields' => json_encode($fields, JSON_UNESCAPED_UNICODE)];
        if ($password !== null && $password !== '') $payload['password'] = $password;

        try {
            $response = $this->http()
                ->attach('pdf', $pdfBytes, $fileName, ['Content-Type' => 'application/pdf'])
                ->post($this->url('/digisign'), $payload);
        } catch (ConnectionException) {
            throw new DigiSignException('Unable to reach DigiSign. Please try again later.', 503);
        }

        if ($response->failed()) {
            $status = $response->status();
            throw new DigiSignException(
                $this->errorMessage($response, 'DigiSign could not sign this document.'),
                in_array($status, [400, 401, 403, 422], true) ? 422 : 502
            );
        }

        $success = $response->header('X-PDF-Fields-Success');
        if ($success !== '' && strtolower($success) !== 'true') {
            throw new DigiSignException('DigiSign could not place the signature field in this document.', 422);
        }
        $body = $response->body();
        if (! str_starts_with($body, '%PDF')) {
            throw new DigiSignException('DigiSign returned an invalid document.', 502);
        }

        return ['bytes' => $body, 'count' => (int) $response->header('X-PDF-Fields-Count')];
    }

    private function http(): PendingRequest
    {
        $request = Http::acceptJson()
            ->timeout((int) config('services.digisign.timeout', 60))
            ->withHeaders(['User-Agent' => 'BACTrax/1.0']);

        $token = config('services.digisign.token');

        return $token ? $request->withToken($token) : $request;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.digisign.url', 'https://digisign.dictr2.cloud'), '/') . $path;
    }

    /** Understands FastAPI-style {"detail": "..."} / {"detail":[{"msg":"..."}]} as well as {"message": "..."}. */
    private function errorMessage(Response $response, string $fallback): string
    {
        $json = $response->json();
        $detail = data_get($json, 'detail');
        if (is_array($detail)) $detail = collect($detail)->map(fn($d) => is_array($d) ? ($d['msg'] ?? null) : $d)->filter()->implode(' ');
        $message = $detail ?: data_get($json, 'message') ?: data_get($json, 'error');

        return is_string($message) && $message !== '' ? $message : $fallback;
    }
}
