<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; font-family: 'Helvetica Neue', Arial, sans-serif; background: #f4f5f7; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #1E40AF; padding: 28px 40px; }
        .header h1 { margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; }
        .body { padding: 36px 40px; color: #374151; font-size: 15px; line-height: 1.65; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 20px 40px; text-align: center; font-size: 12px; color: #9ca3af; }
        a { color: #1E40AF; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>VillaCRM</h1>
        </div>
        <div class="body">
            {!! $bodyHtml !!}
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} VillaCRM &mdash; Real Estate CRM. All rights reserved.<br>
            You are receiving this email because you are a contact of our agency.
        </div>
    </div>
</body>
</html>
