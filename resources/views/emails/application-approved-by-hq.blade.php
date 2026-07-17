<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; line-height: 1.5; }
        table { border-collapse: collapse; width: 100%; max-width: 640px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f4f4f4; width: 35%; }
        .cta { margin-top: 20px; }
        .cta a { display: inline-block; padding: 10px 16px; background: #1d4ed8; color: #fff !important; text-decoration: none; border-radius: 6px; }
    </style>
</head>
<body>
    <h2>Permohonan Disahkan oleh Admin</h2>
    <p>Admin <strong>{{ $approvedBy->name }}</strong> telah mengesahkan permohonan sewaan berikut.</p>

    <table>
        <tr>
            <th>Nama Premis</th>
            <td>{{ $contract->premise?->nama_ptj ?? '—' }}</td>
        </tr>
        <tr>
            <th>Negeri</th>
            <td>{{ $contract->premise?->negeri ?? '—' }}</td>
        </tr>
        <tr>
            <th>Negeri</th>
            <td>{{ $contract->displayAdminNegeriName() }}</td>
        </tr>
        <tr>
            <th>Kategori Permohonan</th>
            <td>{{ \App\Support\ApplicationCategories::label($contract->kategori_permohonan) }}</td>
        </tr>
        <tr>
            <th>Status Seterusnya</th>
            <td>Penyediaan Draf Perjanjian</td>
        </tr>
        <tr>
            <th>Tarikh Pengesahan HQ</th>
            <td>{{ $contract->hq_approved_at?->format('d/m/Y H:i') ?? '—' }}</td>
        </tr>
    </table>

    <p class="cta">
        <a href="{{ route('kontrak-sewaan.show', $contract) }}">Lihat Kontrak Sewaan</a>
    </p>

    <p>Ini adalah notifikasi automatik daripada Sistem E-Sewaan AADK.</p>
</body>
</html>
