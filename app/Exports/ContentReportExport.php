<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ContentReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Collection $rows)
    {
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Judul Konten',
            'URL',
            'Tanggal Upload',
            'Views',
            'Likes',
            'Comments',
        ];
    }

    public function map($row): array
    {
        return [
            $row->judul,
            $row->url,
            $row->tanggal_upload,
            $row->views,
            $row->likes,
            $row->comments,
        ];
    }
}
