<?php

namespace App\Exports;

use App\Models\Booking;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingExport
{
    public function __construct(
        protected ?Carbon $start,
        protected ?Carbon $end,
        protected string  $period = 'monthly',
        protected int     $year   = 0,
        protected int     $month  = 0,
        protected mixed   $query  = null,
    ) {}

    public function download(string $filename): StreamedResponse
    {
        $spreadsheet = $this->build();

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }

    private function build(): Spreadsheet
    {
        $rows        = $this->getData();
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Booking');

        // ── Row 1: Judul ──────────────────────────────────────
        $title = match ($this->period) {
            'yearly'  => "Laporan Booking — Tahun {$this->year}",
            'cleanup' => 'Arsip Data — Ekspor Sebelum Hapus',
            default   => 'Laporan Booking — '
                         . Carbon::create(
                               $this->year  ?: now()->year,
                               $this->month ?: now()->month
                           )->locale('id')->isoFormat('MMMM YYYY'),
        };

        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'name' => 'Calibri', 'color' => ['rgb' => '111827']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // ── Row 2: Timestamp ──────────────────────────────────
        $sheet->mergeCells('A2:J2');
        $sheet->setCellValue('A2', 'Diekspor: ' . now()->locale('id')->isoFormat('D MMMM YYYY, HH:mm'));
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['size' => 9, 'name' => 'Calibri', 'color' => ['rgb' => '9CA3AF']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(16);

        // ── Row 3: kosong ─────────────────────────────────────
        $sheet->getRowDimension(3)->setRowHeight(6);

        // ── Row 4: Header kolom ───────────────────────────────
        $headers = ['A'=>'Kode Booking','B'=>'Status','C'=>'Nama User','D'=>'Nama Driver',
                    'E'=>'Plat Kendaraan','F'=>'Tgl Mulai','G'=>'Tgl Selesai',
                    'H'=>'Total Harga (Rp)','I'=>'Dikonfirmasi','J'=>'Dibuat'];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}4", $label);
        }

        $sheet->getStyle('A4:J4')->applyFromArray([
            'font' => [
                'bold'  => true, 'size' => 10, 'name' => 'Calibri',
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '111827'],
            ],
            'alignment' => [
                'vertical'   => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(22);

        // ── Row 5+: Data ──────────────────────────────────────
        $r = 5;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$r}", $row['kode']);
            $sheet->setCellValue("B{$r}", $row['status']);
            $sheet->setCellValue("C{$r}", $row['user']);
            $sheet->setCellValue("D{$r}", $row['driver']);
            $sheet->setCellValue("E{$r}", $row['plat']);
            $sheet->setCellValue("F{$r}", $row['mulai']);
            $sheet->setCellValue("G{$r}", $row['selesai']);
            $sheet->setCellValue("H{$r}", $row['harga']);
            $sheet->setCellValue("I{$r}", $row['confirmed']);
            $sheet->setCellValue("J{$r}", $row['dibuat']);

            $bg = ($r % 2 === 0) ? 'F7F7F5' : 'FFFFFF';
            $sheet->getStyle("A{$r}:J{$r}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'font'      => ['size' => 9, 'name' => 'Calibri'],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($r)->setRowHeight(16);
            $r++;
        }

        $lastRow = $r - 1;

        // ── Border tabel ──────────────────────────────────────
        if ($lastRow >= 4) {
            $sheet->getStyle("A4:J{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'E5E7EB'],
                    ],
                ],
            ]);
        }

        // ── Format Rupiah kolom H ─────────────────────────────
        if ($lastRow >= 5) {
            $sheet->getStyle("H5:H{$lastRow}")
                  ->getNumberFormat()
                  ->setFormatCode('#,##0');
        }

        // ── Lebar kolom ───────────────────────────────────────
        foreach (['A'=>24,'B'=>14,'C'=>22,'D'=>22,'E'=>16,'F'=>14,'G'=>14,'H'=>18,'I'=>18,'J'=>18] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // ── Freeze header (baris 4) ───────────────────────────
        $sheet->freezePane('A5');

        return $spreadsheet;
    }

    private function getData(): array
    {
        $bookings = $this->query
            ? $this->query->get()
            : $this->buildQuery()->get();

        return $bookings->map(fn($b) => [
            'kode'      => $b->booking_code ?? '-',
            'status'    => ucfirst($b->status ?? '-'),
            'user'      => $b->user['name']    ?? '-',
            'driver'    => $b->driver['name']  ?? '-',
            'plat'      => $b->vehicle['plate_number'] ?? ($b->vehicle['name'] ?? '-'),
            'mulai'     => $b->start_date    ? Carbon::parse($b->start_date)->format('d/m/Y')         : '-',
            'selesai'   => $b->end_date      ? Carbon::parse($b->end_date)->format('d/m/Y')           : '-',
            'harga'     => (int) ($b->total_price ?? 0),
            'confirmed' => $b->confirmed_at  ? Carbon::parse($b->confirmed_at)->format('d/m/Y H:i')  : '-',
            'dibuat'    => $b->created_at    ? Carbon::parse($b->created_at)->format('d/m/Y H:i')    : '-',
        ])->toArray();
    }

    private function buildQuery()
    {
        $q = Booking::orderBy('created_at', 'desc');

        if ($this->start && $this->end) {
            $q->where('created_at', '>=', $this->start)
              ->where('created_at', '<=', $this->end);
        }

        return $q;
    }
}