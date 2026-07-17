<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>
    <h2>Peringatan: Kontrak Sewaan Kurang 8 Bulan</h2>
    <p>Sila sediakan JRP (Jadual Perbelanjaan Rumah) untuk premis berikut:</p>
    <table>
        <thead>
            <tr>
                <th>Nama Premis</th>
                <th>Negeri</th>
                <th>Tarikh Tamat</th>
                <th>Baki Hari</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contracts as $c)
                <tr>
                    <td>{{ $c->premise?->nama_ptj }}</td>
                    <td>{{ $c->premise?->negeri }}</td>
                    <td>{{ $c->tarikh_tamat?->format('d/m/Y') }}</td>
                    <td>{{ $c->baki_hari }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p>Ini adalah notifikasi automatik daripada Sistem E-Sewaan AADK.</p>
</body>
</html>
