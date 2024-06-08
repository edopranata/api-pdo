<?php

namespace App\Exports\Transaction;

use App\Models\Factory;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Maatwebsite\Excel\Concerns\Exportable;
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
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionExport implements WithEvents, WithTitle, WithDrawings, FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithCustomStartCell, WithColumnFormatting, WithStyles
{
    use Exportable;

    protected Factory $factory;

    public function __construct($factory)
    {
        $this->factory = $factory;
    }


    public function query(): Relation|Builder
    {
        return Order::query()
            ->where('factory_id', $this->factory->id)
            ->with(['customer'])
            ->whereDate('trade_date', '>=', request()->get('start_date'))
            ->whereDate('trade_date', '<=', request()->get('end_date'));
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function map($row): array
    {
        return [
            [
                '=row() - 6',
                Date::dateTimeToExcel($row->trade_date),
                $row->customer->name,
                $row->net_weight,
                $row->customer_price,
                $row->customer_total,
                $row->margin,
                $row->net_price,
                $row->gross_total,
                $row->ppn_total,
                $row->pph22_total,
                $row->gross_total + $row->ppn_total - $row->pph22_total,
            ],
        ];
    }

    public function headings(): array
    {
        return [
            [
                'NO',
                'TRADE DATE',
                'CUSTOMER NAME',
                'NET WEIGHT',
                'CUST PRICE',
                'CUSTOMER TOTAL',
                'MARGIN',
                'FACTORY PRICE',
                'GROSS TOTAL',
                'PPN',
                'PPh22',
                'NET INCOME'
            ],
        ];
    }

    public function title(): string
    {
        return 'DO';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 12,
            'C' => 50,
            'D' => 15,
            'E' => 15,
            'F' => 20,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,
            'L' => 15,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $event->sheet->cellValue('C2', 'REPORT DO ' . str($this->factory->name)->upper());
                $event->sheet->cellValue('C3', 'PERIOD DATE ' . Carbon::create(request()->get('start_date'))->format('Y-m-d') . ' - ' . Carbon::create(request()->get('end_date'))->format('Y-m-d'));

                $row = $this->query()->get();

                $table = new Table();
                $table->setName('table_do');
                $table->setShowTotalsRow(true);

                $table->setRange('A6:L' . $row->count() + 7);
                $table->getColumn('C')->setTotalsRowLabel('Total');
                $table->getColumn('D')->setTotalsRowFunction('sum');
                $table->getColumn('E')->setTotalsRowFunction('average');
                $table->getColumn('F')->setTotalsRowFunction('sum');
                $table->getColumn('G')->setTotalsRowFunction('average');
                $table->getColumn('H')->setTotalsRowFunction('sum');
                $table->getColumn('I')->setTotalsRowFunction('sum');
                $table->getColumn('J')->setTotalsRowFunction('sum');
                $table->getColumn('K')->setTotalsRowFunction('sum');
                $table->getColumn('L')->setTotalsRowFunction('sum');

                $event->getSheet()->getCell('C' . $row->count() + 7)->setValue( 'Total');
                $event->getSheet()->getCell('D' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[NET WEIGHT])');
                $event->getSheet()->getCell('E' . $row->count() + 7)->setValue( '=SUBTOTAL(101,table_do[CUST PRICE])');
                $event->getSheet()->getCell('F' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[CUSTOMER TOTAL])');
                $event->getSheet()->getCell('G' . $row->count() + 7)->setValue( '=SUBTOTAL(101,table_do[MARGIN])');
                $event->getSheet()->getCell('H' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[FACTORY PRICE])');
                $event->getSheet()->getCell('I' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[GROSS TOTAL])');
                $event->getSheet()->getCell('J' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[PPN])');
                $event->getSheet()->getCell('K' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[PPh22])');
                $event->getSheet()->getCell('L' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[NET INCOME])');

                $event->sheet->numberFormat('D' . $row->count() + 7 . ':L' . $row->count() + 7, NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                $tableStyle = new TableStyle();
                $tableStyle->setTheme(TableStyle::TABLE_STYLE_LIGHT1);
                $tableStyle->setShowFirstColumn(true);
                $tableStyle->setShowRowStripes(true);

                $table->setStyle($tableStyle);

                $event->sheet->addTable($table);

            }
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('A4:L4')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
        $sheet->getStyle('D6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('A6:L6')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A6:L6')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

        $sheet->getStyle('B6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('D6:L6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);


    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('PDO');
        $drawing->setPath(public_path('logo.png'));
        $drawing->setHeight(74);
        $drawing->setOffsetX(25);
        $drawing->setOffsetY(3);
        $drawing->setCoordinates('A1');

        return $drawing;
    }

}
