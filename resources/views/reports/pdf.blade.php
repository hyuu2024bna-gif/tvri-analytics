<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        h2 { font-size: 12px; font-weight: normal; color: #555; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .summary { margin-top: 10px; font-size: 12px; }
        .footer { margin-top: 20px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <h1>Laporan Performa Konten Media Sosial</h1>
    <h2>TVRI Stasiun Aceh — Divisi Konten Media Baru (KMB)</h2>
    <p>Konten yang diupload pada periode: <strong>{{ $startDate }}</strong> s/d <strong>{{ $endDate }}</strong></p>

    @if($rows->isEmpty())
        <p style="margin-top:20px; color:#b45309;">
            Tidak ada konten yang diupload pada rentang tanggal ini.
        </p>
    @else
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Judul Konten</th>
                <th>Tanggal Upload</th>
                <th class="text-right">Views</th>
                <th class="text-right">Likes</th>
                <th class="text-right">Comments</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->judul }}</td>
                    <td>{{ $row->tanggal_upload }}</td>
                    <td class="text-right">{{ number_format($row->views) }}</td>
                    <td class="text-right">{{ number_format($row->likes) }}</td>
                    <td class="text-right">{{ number_format($row->comments) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="summary">
        Total konten: {{ $rows->count() }} &nbsp;|&nbsp;
        Total views seluruh konten periode ini: <strong>{{ number_format($totalViews) }}</strong>
    </p>
    @endif

    <p class="footer">Dokumen ini digenerate otomatis dari Sistem Analitik Konten KMB TVRI Aceh pada {{ now()->format('d-m-Y H:i') }} WIB.</p>
</body>
</html>
