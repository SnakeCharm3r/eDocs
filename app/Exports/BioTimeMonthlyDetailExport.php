<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BioTimeMonthlyDetailExport implements FromCollection, WithHeadings, WithStyles
{
    /** @var \Illuminate\Support\Collection */
    protected Collection $rows;

    /** @var string 'raw' | 'daily' */
    protected string $mode;

    /**
     * $rows for:
     * - raw:   ['date' => Y-m-d, 'time' => HH:MM, 'direction' => 'Check In/Out/Punch']
     * - daily: ['date' => Y-m-d, 'in' => HH:MM, 'out' => HH:MM, 'total' => HH:MM, 'ot_str' => HH:MM]
     */
    public function __construct(Collection $rows, string $mode = 'raw')
    {
        $this->mode = $mode;

        if ($mode === 'raw') {
            // sort by date + time
            $this->rows = $rows->sortBy([
                ['date', 'asc'],
                ['time', 'asc'],
            ])->values();
        } else { // 'daily' fallback
            $this->rows = $rows->sortBy([
                ['date', 'asc'],
                ['in', 'asc'],
            ])->values();
        }
    }

    public function headings(): array
    {
        if ($this->mode === 'raw') {
            return ['Date', 'Time', 'Direction'];
        }
        return ['Date', 'Check In', 'Check Out', 'Total (HH:MM)', 'OT > 8h (HH:MM)'];
    }

    public function collection()
    {
        if ($this->mode === 'raw') {
            return $this->rows->map(function ($r) {
                $date = is_array($r) ? ($r['date'] ?? '') : ($r->date ?? '');
                $time = is_array($r) ? ($r['time'] ?? '') : ($r->time ?? '');
                $dir  = is_array($r) ? ($r['direction'] ?? '') : ($r->direction ?? '');
                return [$date, $time, $dir];
            });
        }

        // daily fallback (kept for compatibility)
        $toHHMM = function ($hoursOrString): string {
            if (is_string($hoursOrString) && preg_match('/^\d{1,2}:\d{2}$/', $hoursOrString)) {
                return $hoursOrString;
            }
            $mins = (int) round(((float) $hoursOrString) * 60);
            $h = intdiv($mins, 60);
            $m = $mins % 60;
            return sprintf('%02d:%02d', max(0, $h), max(0, $m));
        };

        return $this->rows->map(function ($r) use ($toHHMM) {
            $date  = is_array($r) ? ($r['date'] ?? '') : ($r->date ?? '');
            $in    = is_array($r) ? ($r['in']   ?? '') : ($r->in   ?? '');
            $out   = is_array($r) ? ($r['out']  ?? '') : ($r->out  ?? '');

            $total = is_array($r) ? ($r['total'] ?? null) : ($r->total ?? null);
            $otStr = is_array($r) ? ($r['ot_str'] ?? null) : ($r->ot_str ?? null);
            $hoursNum = is_array($r) ? ($r['hours'] ?? null) : ($r->hours ?? null);
            $otNum    = is_array($r) ? ($r['ot']    ?? null) : ($r->ot    ?? null);

            $totalHHMM = $total !== null ? $toHHMM($total) : $toHHMM($hoursNum ?? 0);
            $otHHMM    = $otStr !== null ? $toHHMM($otStr) : $toHHMM($otNum    ?? 0);

            return [$date ?: '', $in ?: '', $out ?: '', $totalHHMM, $otHHMM];
        });
    }

    public function styles(Worksheet $sheet)
    {
        // Bold header + light fill + autosize
        if ($this->mode === 'raw') {
            $sheet->getStyle('A1:C1')->getFont()->setBold(true);
            $sheet->getStyle('A1:C1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFEFEFEF');

            foreach (range('A', 'C') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        } else {
            $sheet->getStyle('A1:E1')->getFont()->setBold(true);
            $sheet->getStyle('A1:E1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFEFEFEF');
            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        return [];
    }
}
