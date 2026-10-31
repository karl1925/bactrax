@php
    $detailRows = collect($details ?? []);
    $heroPurpose = data_get($detailRows->firstWhere('label', 'Procurement title / purpose'), 'value', 'Purchase Request');
    $heroPhase = data_get($detailRows->firstWhere('label', 'Phase'), 'value', '—');
    $heroSigningStep = data_get($detailRows->firstWhere('label', 'Signing Step'), 'value', '—');
    $heroProgress = data_get($detailRows->firstWhere('label', 'Overall Progress'), 'value', '—');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $subject }}</title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-shell { width: 100% !important; min-width: 0 !important; }
            .email-pad { padding-left: 16px !important; padding-right: 16px !important; }
            .hero-overlay { height: auto !important; min-height: 375px !important; }
            .hero-label { width: 92px !important; }
            .hero-value { font-size: 12px !important; }
        }
    </style>
</head>
<body style="width:100%;margin:0;padding:0;background-color:#f0f1f5;color:#334155;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        {{ $subject }} — BACTrax procurement routing update.
    </div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#f0f1f5" style="background-color:#f0f1f5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff" class="email-shell" style="width:600px;max-width:600px;min-width:320px;margin:0 auto;background-color:#ffffff;">
                    <tr>
                        <td align="center" class="email-pad" style="padding:24px 24px 16px;">
                            <img src="{{ $logoImageUrl }}" width="284" alt="BACTrax — DICT Region II" border="0" style="display:block;width:284px;height:auto;max-width:100%;">
                        </td>
                    </tr>
                    <tr>
                        <td align="center" class="email-pad" style="padding:0 24px 12px;color:#172b4d;font-size:25px;line-height:35px;font-weight:700;">
                            Hello {{ $recipientName }},
                        </td>
                    </tr>
                    <tr>
                        <td align="center" class="email-pad" style="padding:0 24px 20px;color:#595959;font-size:16px;line-height:23px;">
                            <strong style="color:#134991;">{{ $subject }}</strong>
                            @foreach($lines as $line)
                                <div style="padding-top:5px;">{{ $line }}</div>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:0 0 18px;">
                            <!--[if gte mso 9]>
                            <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:600px;height:375px;">
                                <v:fill type="frame" src="{{ $heroImageUrl }}" color="#164fe5" />
                                <v:textbox inset="0,0,0,0">
                            <![endif]-->
                            <table role="presentation" width="600" height="375" cellspacing="0" cellpadding="0" border="0" background="{{ $heroImageUrl }}" class="hero-overlay" style="width:100%;max-width:600px;height:375px;background-color:#164fe5;background-image:url('{{ $heroImageUrl }}');background-size:100% 100%;background-position:center;background-repeat:no-repeat;">
                                <tr><td height="119" colspan="2" style="height:119px;font-size:1px;line-height:1px;">&nbsp;</td></tr>
                                <tr>
                                    <td colspan="2" height="34" style="height:34px;padding:0 28px;color:#ffffff;font-size:13px;line-height:18px;font-weight:700;">
                                        Procurement: {{ \Illuminate\Support\Str::limit($heroPurpose, 62) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td width="145" height="32" class="hero-label" style="width:145px;height:32px;padding-left:29px;color:#ffffff;font-size:14px;line-height:20px;font-weight:700;">Phase:</td>
                                    <td height="32" class="hero-value" style="height:32px;padding-right:24px;color:#ffffff;font-size:14px;line-height:20px;font-weight:700;">{{ $heroPhase }}</td>
                                </tr>
                                <tr>
                                    <td width="145" height="32" class="hero-label" style="width:145px;height:32px;padding-left:29px;color:#ffffff;font-size:14px;line-height:20px;font-weight:700;">Signing Step:</td>
                                    <td height="32" class="hero-value" style="height:32px;padding-right:24px;color:#ffffff;font-size:14px;line-height:20px;font-weight:700;">{{ $heroSigningStep }}</td>
                                </tr>
                                <tr>
                                    <td width="145" height="32" class="hero-label" style="width:145px;height:32px;padding-left:29px;color:#ffffff;font-size:14px;line-height:20px;font-weight:700;">Overall Progress:</td>
                                    <td height="32" class="hero-value" style="height:32px;padding-right:24px;color:#ffffff;font-size:14px;line-height:20px;font-weight:700;">{{ $heroProgress }}</td>
                                </tr>
                                <tr><td height="94" colspan="2" style="height:94px;font-size:1px;line-height:1px;">&nbsp;</td></tr>
                            </table>
                            <!--[if gte mso 9]>
                                </v:textbox>
                            </v:rect>
                            <![endif]-->
                        </td>
                    </tr>
                    <tr>
                        <td align="center" class="email-pad" style="padding:0 24px 24px;">
                            <table role="presentation" width="300" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:300px;">
                                <tr>
                                    <td align="center" bgcolor="#1a76e7" style="background-color:#1a76e7;border:3px solid #1a76e7;border-radius:28px;">
                                        <a href="{{ $url }}" target="_blank" style="display:block;padding:10px 12px;color:#ffffff;font-size:17px;line-height:24px;font-weight:700;text-decoration:none;">{{ $actionLabel }} &nbsp;➔</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @php
                        $heroDetailLabels = ['Procurement title / purpose', 'Phase', 'Signing Step', 'Overall Progress'];
                        $extraDetails = $detailRows->reject(fn ($detail) => in_array($detail['label'], $heroDetailLabels, true));
                    @endphp
                    @if($extraDetails->isNotEmpty())
                        <tr>
                            <td class="email-pad" style="padding:0 24px 18px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#e0efff" style="background-color:#e0efff;border-radius:10px;">
                                    @foreach($extraDetails as $detail)
                                        <tr>
                                            <td style="padding:10px 14px;color:#475569;font-size:12px;line-height:18px;">{{ $detail['label'] }}</td>
                                            <td align="right" style="padding:10px 14px;color:#134991;font-size:12px;line-height:18px;font-weight:700;">{{ $detail['value'] }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="email-pad" style="padding:16px 32px;background-color:#f5f5f5;color:#595959;font-size:14px;line-height:24px;">
                            <strong>Cheers,</strong><br>
                            From DICT R2 Systems Development Team
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:22px 24px;background-color:#222222;color:#ffffff;">
                            <div style="font-size:27px;line-height:34px;font-weight:700;">Procurement Made Easy</div>
                            <div style="padding-top:8px;color:#bdbdbd;font-size:13px;line-height:20px;">BACTrax is a DICT R2 Procurement Routing System<br>internally developed by its Systems Development Team</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-pad" style="padding:18px 32px;color:#64748b;font-size:11px;line-height:18px;">
                            If the button does not work, copy and paste this address into your browser:<br>
                            <a href="{{ $url }}" style="color:#1677ff;word-break:break-all;text-decoration:underline;">{{ $url }}</a>
                            @if($appUrl)<br><a href="{{ $appUrl }}" style="color:#134991;text-decoration:none;">Open BACTrax</a>@endif
                            <br><br>This is an automated message. Please do not reply directly to this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
