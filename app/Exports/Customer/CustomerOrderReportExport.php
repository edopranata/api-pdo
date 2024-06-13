<?php

namespace App\Exports\Customer;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

class CustomerOrderReportExport  implements WithEvents, WithTitle, WithDrawings, FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithCustomStartCell, WithColumnFormatting, WithStyles
{

    protected int $month;
    protected int $year;

    public function __construct($date){
        $this->year = (int) $date[0];
        $this->month = (int) $date[1];
    }
    public function headings(): array
    {
        return [
            [
                'NO',
                'CUSTOMER',
                'ADDRESS',
                'PHONE',
                'TOTAL DO',
                'TOTAL WEIGHT',
                'AVG CUSTOMER PRICE',
                'CUSTOMER TOTAL',
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
                $row->orders_count ?? 0 . " DO",
                $row->orders_sum_net_weight ?? 0,
                $row->orders_avg_customer_price ?? 0,
                $row->orders_sum_customer_total ?? 0,
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
        return "CUSTOMER ORDER";
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('A4:H4')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
        $sheet->getStyle('E6:H6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('A6:H6')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A6:H6')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $event->sheet->cellValue('B2', 'CUSTOMER DELIVERY ORDER REPORT');
                $event->sheet->cellValue('B3', 'DATE PERIOD ' . $this->year . ' - ' . sprintf('%02d', $this->month));

                $row = $this->query()->get();

                $table = new Table();
                $table->setName('table_order');
                $table->setShowTotalsRow(true);

                $table->setRange('A6:H' . $row->count() + 7);
                $table->getColumn('B')->setTotalsRowLabel('Total');
                $table->getColumn('E')->setTotalsRowFunction('sum');
                $table->getColumn('F')->setTotalsRowFunction('sum');
                $table->getColumn('G')->setTotalsRowFunction('average');
                $table->getColumn('H')->setTotalsRowFunction('sum');

                $event->getSheet()->getCell('B' . $row->count() + 7)->setValue('Total');
                $event->getSheet()->getCell('E' . $row->count() + 7)->setValue('=SUBTOTAL(109,table_order[TOTAL DO])');
                $event->getSheet()->getCell('F' . $row->count() + 7)->setValue('=SUBTOTAL(109,table_order[TOTAL WEIGHT])');
                $event->getSheet()->getCell('G' . $row->count() + 7)->setValue('=SUBTOTAL(101,table_order[AVG CUSTOMER PRICE])');
                $event->getSheet()->getCell('H' . $row->count() + 7)->setValue('=SUBTOTAL(109,table_order[CUSTOMER TOTAL])');

                $event->sheet->numberFormat('F' . $row->count() + 7 . ':H' . $row->count() + 7, NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                $tableStyle = new TableStyle();
                $tableStyle->setTheme(TableStyle::TABLE_STYLE_LIGHT1);
                $tableStyle->setShowFirstColumn(true);
                $tableStyle->setShowRowStripes(true);
                $table->setStyle($tableStyle);

                $event->sheet->addTable($table);

            }
        ];
    }

    public function query(): \Illuminate\Database\Eloquent\Relations\Relation|Builder|\Illuminate\Database\Query\Builder
    {
        return Customer::query()
            ->whereHas('orders', function ($query) {
                $query->whereMonth('trade_date', $this->month)->whereYear('trade_date', $this->year);
            })
            ->withCount(['orders' => function ($query) {
                $query->whereMonth('trade_date', $this->month)->whereYear('trade_date', $this->year);
            }])
            ->withSum(['orders' => function ($query) {
                $query->whereMonth('trade_date', $this->month)->whereYear('trade_date', $this->year);
            }], 'net_weight')
            ->withAvg(['orders' => function ($query) {
                $query->whereMonth('trade_date', $this->month)->whereYear('trade_date', $this->year);
            }], 'customer_price')
            ->withSum(['orders' => function ($query) {
                $query->whereMonth('trade_date', $this->month)->whereYear('trade_date', $this->year);
            }], 'customer_total');
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_GENERAL,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 30,
            'C' => 30,
            'D' => 15,
            'E' => 15,
            'F' => 18,
            'G' => 18,
            'H' => 18,
        ];
    }

    public function startCell(): string
    {
        return 'A6';
    }
}
