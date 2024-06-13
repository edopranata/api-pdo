<?php

namespace App\Exports\Customer;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerLoanExport implements WithEvents, WithTitle, WithDrawings, FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithCustomStartCell, WithColumnFormatting, WithStyles
{

    public function headings(): array
    {
        return [
            [
                'NO',
                'CUSTOMER',
                'ADDRESS',
                'PHONE',
                'LOAN BALANCE',
            ],
        ];

    }

    public function map($row): array
    {
        return [
            [
                '=row() - 6',
                $row->name,
                $row->address,
                $row->phone,
                $row->loan?->balance ?? 0,
            ],
        ];
    }

    public function drawings(): Drawing
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('PDO');
        $drawing->setPath(public_path('logo.png'));
        $drawing->setHeight(74);
        $drawing->setOffsetX(1);
        $drawing->setOffsetY(3);
        $drawing->setCoordinates('A1');

        return $drawing;
    }

    public function title(): string
    {
        return "CUSTOMER LOAN";
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('A4:E4')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
        $sheet->getStyle('E6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('A6:E6')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A6:E6')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $event->sheet->cellValue('B2', 'CUSTOMER LOAN REPORT');
                $event->sheet->cellValue('B3', 'DATE ' . now()->format('Y-m-d'));

                $row = $this->query()->get();

                $table = new Table();
                $table->setName('table_loan');
                $table->setShowTotalsRow(true);

                $table->setRange('A6:E' . $row->count() + 7);
                $table->getColumn('B')->setTotalsRowLabel('Total');
                $table->getColumn('E')->setTotalsRowFunction('sum');

                $event->getSheet()->getCell('B' . $row->count() + 7)->setValue('Total');
                $event->getSheet()->getCell('E' . $row->count() + 7)->setValue('=SUBTOTAL(109,table_loan[LOAN BALANCE])');

                $event->sheet->numberFormat('D' . $row->count() + 7 . ':E' . $row->count() + 7, NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                $tableStyle = new TableStyle();
                $tableStyle->setTheme(TableStyle::TABLE_STYLE_LIGHT1);
                $tableStyle->setShowFirstColumn(true);
                $tableStyle->setShowRowStripes(true);

                $table->setStyle($tableStyle);

                $event->sheet->addTable($table);

            }
        ];
    }

    public function query(): Builder
    {
        return Customer::query()->withWhereHas('loan');
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 30,
            'C' => 30,
            'D' => 15,
            'E' => 18,
        ];
    }

    public function startCell(): string
    {
        return 'A6';
    }
}
