<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BioTimeSummaryExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    /** @var \Illuminate\Support\Collection */
    protected Collection $rows;

    /** @var bool */
    protected bool $includeGrandTotal;

    /** @var array<string> lowercased dept names to skip subtotal for */
    protected array $skipSubtotalFor;

    /**
     * @param Collection $rows     rows with keys: department, name, emp_code, month, days, hours, overtime
     * @param bool       $includeGrandTotal  add a final Grand Total row?
     * @param array      $skipSubtotalFor    list of department names (case-insensitive) to skip subtotals for
     */
    public function __construct(Collection $rows, bool $includeGrandTotal = true, array $skipSubtotalFor = [])
    {
        $this->rows = $rows;
        $this->includeGrandTotal = $includeGrandTotal;

        // normalize skip list to lowercase for case-insensitive compare
        $this->skipSubtotalFor = array_map(
            fn($s) => mb_strtolower(trim((string)$s)),
            $skipSubtotalFor
        );
    }

    public function headings(): array
    {
        return ['#', 'Username', 'Employee Code', 'Department', 'Month', 'Punch Days', 'Total Hours', 'Overtime Hours'];
    }

    public function collection()
    {
        $out      = collect();
        $counter  = 0;

        $grandD   = 0;
        $grandH   = 0.0;
        $grandOT  = 0.0;

        $byDept = $this->rows
            ->groupBy(fn ($r) => $r['department'] ?? 'Unknown')
            ->sortKeys();

        foreach ($byDept as $deptName => $items) {
            $deptD  = 0;
            $deptH  = 0.0;
            $deptOT = 0.0;

            foreach ($items as $r) {
                $counter++;

                $out->push([
                    $counter,
                    $r['name']      ?? '—',
                    $r['emp_code']  ?? '—',
                    $deptName,
                    $r['month']     ?? '—',
                    (int) ($r['days'] ?? 0),
                    round((float) ($r['hours'] ?? 0), 2),
                    round((float) ($r['overtime'] ?? 0), 2),
                ]);

                $deptD  += (int) ($r['days'] ?? 0);
                $deptH  += (float) ($r['hours'] ?? 0);
                $deptOT += (float) ($r['overtime'] ?? 0);
            }

            // Subtotal row — skip for specific departments if requested
            $shouldSkipSubtotal = in_array(mb_strtolower(trim((string)$deptName)), $this->skipSubtotalFor, true);

            if (!$shouldSkipSubtotal) {
                $out->push(['', "Subtotal for {$deptName}", '', '', '',
                    (int) $deptD, round($deptH, 2), round($deptOT, 2)
                ]);

                // Spacer row
                $out->push(['', '', '', '', '', '', '', '']);
            }

            // Keep tracking grand totals (even if we won't output them)
            $grandD  += $deptD;
            $grandH  += $deptH;
            $grandOT += $deptOT;
        }

        // Grand total row — only if enabled
        if ($this->includeGrandTotal) {
            $out->push(['', 'Grand Total', '', '', '',
                (int) $grandD, round($grandH, 2), round($grandOT, 2)
            ]);
        }

        return $out;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'  => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor'=> ['argb' => 'FFEFEFEF'],
                ],
            ],
        ];

        $highest = $sheet->getHighestRow();
        for ($r = 2; $r <= $highest; $r++) {
            $txt = (string) $sheet->getCell("B{$r}")->getValue();
            if (stripos($txt, 'subtotal for') === 0 || stripos($txt, 'grand total') === 0) {
                $styles[$r] = [
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType'  => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor'=> ['argb' => 'FFF7F7F7'],
                    ],
                ];
            }
        }

        foreach (range('A','H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $styles;
    }

    public function columnFormats(): array
    {
        return [
            'F' => '#,##0', // Punch Days
            'G' => '0.00',  // Total Hours
            'H' => '0.00',  // Overtime Hours
        ];
    }
}
