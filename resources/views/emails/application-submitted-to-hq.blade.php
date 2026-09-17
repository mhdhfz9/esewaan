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
    <h2>Permohonan Menunggu Semakan Ibu Pejabat</h2>
    <p>Negeri <strong>{{ $submittedBy->name }}</strong> telah menghantar permohonan sewaan untuk semakan Ibu Pejabat.</p>

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
            <th>Kategori Permohonan</th>
            <td>{{ \App\Support\ApplicationCategories::label($contract->kategori_permohonan) }}</td>
        </tr>
        @if ($contract->remark)
            <tr>
                <th>Remark</th>
                <td>{{ $contract->remark }}</td>
            </tr>
        @endif
    </table>

    <p class="cta">
        <a href="{{ route('status-permohonan.review', $contract) }}">Semak Permohonan</a>
    </p>

    <p>Ini adalah notifikasi automatik daripada Sistem E-Sewaan AADK.</p>
</body>
</html>
