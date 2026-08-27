<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; color: #1f2937; font-size: 12px; }
        .header {
            background-color: #1e3a8a; color: white; padding: 24px 20px;
            margin-bottom: 20px;
        }
        .header .brand { font-size: 11px; letter-spacing: 1px; opacity: 0.8; }
        .header h1 { font-size: 26px; margin: 6px 0 4px 0; }
        .header .periode { font-size: 12px; opacity: 0.9; }

        h2.section-title {
            font-size: 15px; color: #1e3a8a; border-bottom: 2px solid #1e3a8a;
            padding-bottom: 4px; margin-top: 24px; margin-bottom: 12px;
        }

        table.cards { width: 100%; border-collapse: separate; border-spacing: 8px; }
        table.cards td {
            width: 33%; background-color: #f3f4f6; border: 1px solid #e5e7eb;
            border-radius: 6px; padding: 12px; text-align: left; vertical-align: top;
        }
        table.cards .label { font-size: 10px; color: #6b7280; text-transform: uppercase; }
        table.cards .value { font-size: 20px; font-weight: bold; color: #111827; margin-top: 4px; }
        table.cards .growth { font-size: 11px; color: #16a34a; margin-top: 2px; }

        .chart-wrap { text-align: center; margin: 10px 0; }
        .chart-wrap img { width: 100%; max-width: 650px; }

        table.top3 { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.top3 td { border: 1px solid #e5e7eb; padding: 8px; vertical-align: top; }
        table.top3 .thumb { width: 90px; }
        table.top3 .thumb img { width: 80px; border-radius: 4px; }
        table.top3 .rank {
            display: inline-block; background: #1e3a8a; color: white;
            padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-bottom: 4px;
        }
        table.top3 .judul { font-weight: bold; font-size: 11px; }
        table.top3 .metrik { font-size: 10px; color: #4b5563; margin-top: 4px; }

        .footer { margin-top: 24px; font-size: 9px; color: #9ca3af; text-align: center; }
        .empty-note { color: #b45309; font-size: 11px; margin: 10px 0; }
    </style>
</head>
<body>

    <div class="header">
        <div class="brand">TVRI STASIUN ACEH &middot; KONTEN MEDIA BARU</div>
        <h1>Laporan Media Sosial &mdash; YouTube</h1>
        <div class="periode">Periode: {{ $startDate }} s/d {{ $endDate }}</div>
    </div>

    <h2 class="section-title">Ringkasan</h2>
    <table class="cards">
        <tr>
            <td>
                <div class="label">Total Post</div>
                <div class="value">{{ number_format($totalPost) }}</div>
            </td>
            <td>
                <div class="label">Views</div>
                <div class="value">{{ number_format($totalViews) }}</div>
            </td>
            <td>
                <div class="label">Likes</div>
                <div class="value">{{ number_format($totalLikes) }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Comments</div>
                <div class="value">{{ number_format($totalComments) }}</div>
            </td>
            <td>
                <div class="label">Subscribers</div>
                <div class="value">{{ number_format($subscriberCurrent) }}</div>
                @if($latestSyncDate)
                    <div style="font-size:9px; color:#9ca3af; margin-top:2px;">
                        per {{ $latestSyncDate }}
                    </div>
                @endif
            </td>
            <td>
                <div class="label">Subscribers Growth</div>
                <div class="value">{{ $subscriberGrowth >= 0 ? '+' : '' }}{{ number_format($subscriberGrowth) }}</div>
            </td>
        </tr>
    </table>

    <h2 class="section-title">Grafik Subscribers</h2>
    @if($chartUrl)
        <div class="chart-wrap">
            <img src="{{ $chartUrl }}">
        </div>
    @else
        <p class="empty-note">
            Grafik belum bisa ditampilkan - butuh minimal 2 hari data subscriber
            tercatat dalam periode ini.
        </p>
    @endif

    <h2 class="section-title">Top 3 Konten</h2>
    @if($top3->isEmpty())
        <p class="empty-note">Tidak ada konten yang diupload pada periode ini.</p>
    @else
        <table class="top3">
            <tr>
                @foreach($top3 as $i => $item)
                    <td style="width: 33%;">
                        <span class="rank">#{{ $i + 1 }}</span><br>
                        @if($item->thumbnail_url)
                            <img src="{{ $item->thumbnail_url }}" style="width:100%; border-radius:4px; margin:4px 0;">
                        @endif
                        <div class="judul">{{ $item->judul }}</div>
                        <div class="metrik">
                            Views: {{ number_format($item->views) }}<br>
                            Likes: {{ number_format($item->likes) }}<br>
                            Comments: {{ number_format($item->comments) }}
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <p class="footer">
        Dokumen ini digenerate otomatis dari Sistem Analitik Konten KMB TVRI Aceh
        pada {{ now()->format('d-m-Y H:i') }} WIB. Data mencakup platform YouTube.
    </p>
</body>
</html>
