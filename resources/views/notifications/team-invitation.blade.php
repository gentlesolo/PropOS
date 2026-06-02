<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>You've been invited to join {{ $agency->name }}</title>
<style>
    body { margin:0; padding:0; background:#f4f6f8; font-family: {{ $agency->font_family ? "'".$agency->font_family."', sans-serif" : "'Geist', 'Inter', sans-serif" }}; color:#1a1a1a; -webkit-font-smoothing:antialiased; }
    a { color: {{ $primaryColor }}; }
    .wrapper { max-width:600px; margin:0 auto; padding:32px 16px; }
    .card { background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.08); }
    .header { background: {{ $primaryColor }}; padding:32px 40px; text-align:center; }
    .header img { max-height:48px; max-width:180px; object-fit:contain; }
    .header .monogram { display:inline-block; width:48px; height:48px; border-radius:10px; background:rgba(255,255,255,0.2); line-height:48px; text-align:center; font-size:22px; font-weight:900; color:#ffffff; }
    .header .agency-name { margin:12px 0 0; font-size:20px; font-weight:700; color:#ffffff; letter-spacing:-0.3px; }
    .body { padding:40px; }
    .body h1 { margin:0 0 8px; font-size:22px; font-weight:700; color:#111827; letter-spacing:-0.3px; }
    .body p { margin:0 0 20px; font-size:15px; line-height:1.6; color:#4b5563; }
    .badge { display:inline-block; padding:3px 10px; border-radius:999px; background: {{ $primaryColor }}1a; color: {{ $primaryColor }}; font-size:13px; font-weight:600; border:1px solid {{ $primaryColor }}33; }
    .btn-wrap { text-align:center; margin:32px 0; }
    .btn { display:inline-block; padding:14px 32px; background: {{ $primaryColor }}; color:#ffffff !important; border-radius:8px; font-size:15px; font-weight:600; text-decoration:none; letter-spacing:-0.1px; }
    .btn:hover { opacity:0.92; }
    .footer { padding:24px 40px; border-top:1px solid #f0f0f0; text-align:center; }
    .footer p { margin:0; font-size:12px; color:#9ca3af; line-height:1.6; }
</style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <!-- Header -->
        <div class="header">
            @if($agency->logo_path)
                <img src="{{ asset('storage/'.$agency->logo_path) }}" alt="{{ $agency->name }}">
            @else
                <div class="monogram">{{ strtoupper(substr($agency->name, 0, 1)) }}</div>
                <p class="agency-name">{{ $agency->name }}</p>
            @endif
        </div>

        <!-- Body -->
        <div class="body">
            <h1>You've been invited!</h1>
            <p>
                You have been invited to join <strong>{{ $agency->name }}</strong> on
                {{ config('app.name', 'PropOS') }} as a
                <span class="badge">{{ ucfirst($invitation->role) }}</span>.
            </p>
            <p>
                Click the button below to accept your invitation and set up your account.
                This invitation expires in <strong>7 days</strong>.
            </p>

            <div class="btn-wrap">
                <a href="{{ $acceptUrl }}" class="btn">Accept Invitation</a>
            </div>

            <p style="font-size:13px; color:#9ca3af;">
                Or copy and paste this link into your browser:<br>
                <a href="{{ $acceptUrl }}" style="word-break:break-all;">{{ $acceptUrl }}</a>
            </p>
            <p style="font-size:13px; color:#9ca3af; margin-top:24px; margin-bottom:0;">
                If you did not expect this invitation, you can safely ignore this email.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            @if($agency->tagline)
                <p style="font-weight:500; color:#6b7280; margin-bottom:6px;">{{ $agency->tagline }}</p>
            @endif
            <p>{{ $agency->name }} &bull; Powered by {{ config('app.name', 'PropOS') }}</p>
        </div>
    </div>
</div>
</body>
</html>
