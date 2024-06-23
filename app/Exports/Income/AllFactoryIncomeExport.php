<?php

namespace App\Exports\Income;

use App\Models\Income;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
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

class AllFactoryIncomeExport implements WithEvents, WithTitle, WithDrawings, FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithCustomStartCell, WithColumnFormatting, WithStyles
{

    protected int $month;
    protected int $year;

    public function __construct($date)
    {
        $this->year = (int)$date[0];
        $this->month = (int)$date[1];
    }

    public function headings(): array
    {
        return [
            [
                'NO',
                'FACTORY',
                'TRANSFER DATE',
                'PERIOD START',
                'PERIOD END',
                'TOTAL WEIGHT',
                'AVG MARGIN',
                'AVG FACTORY PRICE',
                'PPN',
                'PPH22',
                'GROSS TOTAL',
                'CUSTOMER TOTAL',
                'BANK TRANSFER',
                'INCOME',
            ],
        ];

    }

    /**
     * 'orders' => $this->whenLoaded('orders', [
     * 'customer_price' => $this->orders->avg('customer_price'),
     * 'customer_total' => $this->orders->sum('customer_total'),
     * 'factory_price' => $this->orders->avg('net_price'),
     * 'margin' => $this->orders->avg('margin'),
     * 'gross_total' => $this->orders->sum('gross_total'),
     * 'total_weight' => $this->orders->sum('net_weight'),
     * 'ppn_total' => $this->orders->sum('ppn_total'),
     * 'pph22_total' => $this->orders->sum('pph22_total'),
     * 'gross_ppn_total' => $this->orders->sum('gross_total') + $this->orders->sum('ppn_total'),
     * 'total' => ($this->orders->sum('gross_total') + $this->orders->sum('ppn_total')) - $this->orders->sum('pph22_total'),
     * 'margin_income' => $this->orders->sum('net_total'),
     * 'net_income' => $this->orders->sum('gross_total')  - $this->orders->sum('pph22_total') - $this->orders->sum('customer_total')
     * ]),
     */

    public function map($row): array
    {
        return [
            [
                '=row() - 6',
                $row->factory?->name ?? '',
                $row->trade_date,
                $row->period_start,
                $row->period_end,
                $row->orders->sum('net_weight'),
                $row->orders->avg('margin'),
                $row->orders->avg('net_price'),
                $row->orders->sum('ppn_total'),
                $row->orders->sum('pph22_total'),
                $row->orders->sum('gross_total'),
                $row->orders->sum('customer_total'),
                $row->orders->sum('gross_total') - $row->orders->sum('pph22_total') + $row->orders->sum('ppn_total'),
                $row->orders->sum('gross_total') - $row->orders->sum('pph22_total') - $row->orders->sum('customer_total'),
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'D' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'E' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'F' => NumberFormat::FORMAT_NUMBER_0,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 30,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 18,
            'G' => 18,
            'H' => 18,
            'I' => 18,
            'J' => 18,
            'K' => 18,
            'L' => 18,
            'M' => 18,
            'N' => 18,
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
        return "FACTORY INCOME";
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('A4:N4')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
        $sheet->getStyle('E6:N6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('A6:N6')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A6:N6')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $event->sheet->cellValue('B2', 'FACTORY INCOME REPORT');
                $event->sheet->cellValue('B3', 'DATE PERIOD ' . $this->year . ' - ' . sprintf('%02d', $this->month));

                $row = $this->query()->get();

                $table = new Table();
                $table->setName('table_income');
                $table->setShowTotalsRow(true);

                $table->setRange('A6:N' . $row->count() + 7);
                $table->getColumn('E')->setTotalsRowLabel('Total');
                $table->getColumn('F')->setTotalsRowFunction('sum');
                $table->getColumn('G')->setTotalsRowFunction('average');
                $table->getColumn('H')->setTotalsRowFunction('average');
                $table->getColumn('I')->setTotalsRowFunction('sum');
                $table->getColumn('J')->setTotalsRowFunction('sum');
                $table->getColumn('K')->setTotalsRowFunction('sum');
                $table->getColumn('L')->setTotalsRowFunction('sum');
                $table->getColumn('M')->setTotalsRowFunction('sum');
                $table->getColumn('N')->setTotalsRowFunction('sum');

                $event->getSheet()->getCell('E' . $row->count() + 7)->setValue('Total');
                $event->getSheet()->getCell('F' . $row->count() + 7)->setValue('=SUBTOTAL(109,table_income[TOTAL WEIGHT])');
                $event->getSheet()->getCell('G' . $row->count() + 7)->setValue('=SUBTOTAL(101,table_income[AVG MARGIN])');
                $event->getSheet()->getCell('H' . $row->count() + 7)->setValue('=SUBTOTAL(101,table_income[AVG FACTORY PRICE])');
                $event->getSheet()->getCell('I' . $row->count() + 7)->setValue('=SUBTOTAL(109,table_income[PPN])');
                $event->getSheet()->getCell('J' . $row->count() + 7)->setValue('=SUBTOTAL(109,table_income[PPH22])');
                $event->getSheet()->getCell('K' . $row->count() + 7)->setValue('=SUBTOTAL(109,table_income[GROSS TOTAL])');
                $event->getSheet()->getCell('L' . $row->count() + 7)->setValue('=SUBTOTAL(109,table_income[CUSTOMER TOTAL])');
                $event->getSheet()->getCell('M' . $row->count() + 7)->setValue('=SUBTOTAL(109,table_income[BANK TRANSFER])');
                $event->getSheet()->getCell('N' . $row->count() + 7)->setValue('=SUBTOTAL(109,table_income[INCOME])');

                $event->sheet->numberFormat('F' . $row->count() + 7 . ':N' . $row->count() + 7, NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                $tableStyle = new TableStyle();
                $tableStyle->setTheme(TableStyle::TABLE_STYLE_LIGHT1);
                $tableStyle->setShowFirstColumn(true);
                $tableStyle->setShowRowStripes(true);
                $table->setStyle($tableStyle);

                $event->sheet->addTable($table);

            }
        ];
    }

    public function query(): Relation|Builder|\Illuminate\Database\Query\Builder
    {

        return Income::query()
            ->with(['factory', 'orders'])
            ->whereYear('trade_date', '=', $this->year)
            ->whereMonth('trade_date', '=', $this->month)
            ->orderBy('trade_date');
    }

    public function startCell(): string
    {
        return 'A6';
    }
}
