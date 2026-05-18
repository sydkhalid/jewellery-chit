<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class BaseReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  array{title: string, headings: array<int, string>, rows: array<int, array<int, mixed>>}  $payload
     */
    public function __construct(
        protected array $payload
    ) {
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return $this->payload['rows'];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->payload['headings'];
    }

    public function title(): string
    {
        return str($this->payload['title'])->limit(31, '')->toString();
    }
}
