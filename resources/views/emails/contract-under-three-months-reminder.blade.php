<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; color: #0f172a; }
        table { border-collapse: collapse; width: 100%; margin: 16px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        .muted { color: #64748b; font-size: 13px; }
    </style>
</head>
<body>
    <h2>Peringatan Mingguan: Kontrak Sewaan Bawah 3 Bulan</h2>
    <p>
        Terdapat kontrak sewaan aktif dengan baki tempoh bawah 3 bulan yang belum mula tindakan lanjutan/pindah.
        Sila mula tindakan segera melalui sistem e-Sewaan.
    </p>
    <table>
        <thead>
            <tr>
                <th>Nama Premis</th>
                <th>Negeri</th>
                <th>Tarikh Tamat</th>
                <th>Baki Tempoh</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contracts as $contract)
                <tr>
                    <td>{{ $contract->premise?->nama_ptj ?? '–' }}</td>
                    <td>{{ $contract->premise?->negeri ?? '–' }}</td>
                    <td>{{ $contract->contractEndDate()?->format('d/m/Y') ?? '–' }}</td>
                    <td>{{ $contract->daysUntilContractEndLabel() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p>
        <a href="{{ route('kontrak-sewaan.index') }}">Buka Senarai Kontrak Sewaan</a>
    </p>
    <p class="muted">
        Emel ini dihantar setiap Isnin pukul 9:00 pagi dan akan berhenti secara automatik
        selepas Negeri memulakan permohonan lanjutan atau pindah untuk kontrak berkenaan.
    </p>
    <p class="muted">Ini adalah notifikasi automatik daripada Sistem E-Sewaan AADK.</p>
</body>
</html>
