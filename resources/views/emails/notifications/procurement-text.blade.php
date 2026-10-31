BACTrax — DICT Region II Procurement Routing System

{{ $subject }}

Hello {{ $recipientName }},

@foreach($lines as $line)
{{ $line }}
@endforeach

@if(!empty($details))
Procurement details:
@foreach($details as $detail)
{{ $detail['label'] }}: {{ $detail['value'] }}
@endforeach
@endif

{{ $actionLabel }}: {{ $url }}

BACTrax Procurement Routing System
This is an automated message. Please do not reply directly to this email.
@if($appUrl)
Open BACTrax: {{ $appUrl }}
@endif
