<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; line-height: 1.5; color: #1e293b; }
        .cta { margin: 24px 0; }
        .cta a { display: inline-block; padding: 10px 16px; background: #1d4ed8; color: #fff !important; text-decoration: none; border-radius: 6px; }
        .muted { color: #64748b; font-size: 14px; }
    </style>
</head>
<body>
    <h2>Set Semula Kata Laluan</h2>
    <p>Assalamualaikum {{ $user->name }},</p>
    <p>Kami menerima permintaan untuk set semula kata laluan akaun E-Sewaan AADK anda. Klik butang di bawah untuk meneruskan:</p>

    <p class="cta">
        <a href="{{ $resetUrl }}">Set Semula Kata Laluan</a>
    </p>

    <p class="muted">Pautan ini akan tamat tempoh dalam 60 minit. Jika anda tidak membuat permintaan ini, abaikan emel ini.</p>
    <p class="muted">Ini adalah notifikasi automatik daripada Sistem E-Sewaan AADK.</p>
</body>
</html>
