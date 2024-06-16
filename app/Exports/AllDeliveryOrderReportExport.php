<?php

namespace App\Exports;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
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

class AllDeliveryOrderReportExport  implements WithEvents, WithTitle, WithDrawings, FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithCustomStartCell, WithColumnFormatting, WithStyles
{
    use Exportable;

    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query(): Relation|Builder
    {
        return Order::query()
            ->with(['customer', 'factory'])
            ->when($this->request->get('start_date'), function (Builder $builder, $start_date) {
                $builder->whereDate('trade_date', '>=', $start_date);
            })
            ->when($this->request->get('end_date'), function (Builder $builder, $end_date) {
                $builder->whereDate('trade_date', '<=', $end_date);
            })
            ->when($this->request->get('monthly'), function (Builder $builder, $monthly) {
                $monthly = str($monthly)->split('#/#');

                $builder
                    ->whereYear('trade_date', '=', $monthly[0])
                    ->whereMonth('trade_date', '=', $monthly[1]);
            })
            ->orderBy('trade_date');
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'B' => NumberFormat::FORMAT_GENERAL,
            'C' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'O' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function map($row): array
    {
        return [
            [
                '=row() - 6',
                $row->factory->name,
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
                $row->gross_total - ($row->customer_total + $row->pph22_total),
            ],
        ];
    }

    public function headings(): array
    {
        return [
            [
                'NO',
                'FACTORY',
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
                'BANK TRANSFER',
                'INCOME',
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
            'A' => 14,
            'B' => 50,
            'C' => 12,
            'D' => 50,
            'E' => 15,
            'F' => 15,
            'G' => 20,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,
            'L' => 15,
            'M' => 15,
            'N' => 15,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $monthly = $this->request->has('monthly') ? str($this->request->get('monthly'))->split('#/#') : [];

                $period = $this->request->has('monthly') ? "MONTH $monthly[1]-$monthly[0]"  : "DATE " . Carbon::create($this->request->get('start_date'))->format('Y-m-d') . " - " . Carbon::create($this->request->get('end_date'))->format('Y-m-d');

                $event->sheet->cellValue('C2', 'REPORT TRANSACTION DELIVERY ORDER');
                $event->sheet->cellValue('C3', 'PERIOD ' . $period);

                $row = $this->query()->get();

                $table = new Table();
                $table->setName('table_do');
                $table->setShowTotalsRow(true);

                $table->setRange('A6:N' . $row->count() + 7);
                $table->getColumn('D')->setTotalsRowLabel('Total');
                $table->getColumn('E')->setTotalsRowFunction('sum');
                $table->getColumn('F')->setTotalsRowFunction('average');
                $table->getColumn('G')->setTotalsRowFunction('sum');
                $table->getColumn('H')->setTotalsRowFunction('average');
                $table->getColumn('I')->setTotalsRowFunction('sum');
                $table->getColumn('J')->setTotalsRowFunction('sum');
                $table->getColumn('K')->setTotalsRowFunction('sum');
                $table->getColumn('L')->setTotalsRowFunction('sum');
                $table->getColumn('M')->setTotalsRowFunction('sum');
                $table->getColumn('N')->setTotalsRowFunction('sum');

                $event->getSheet()->getCell('D' . $row->count() + 7)->setValue( 'Total');
                $event->getSheet()->getCell('E' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[NET WEIGHT])');
                $event->getSheet()->getCell('F' . $row->count() + 7)->setValue( '=SUBTOTAL(101,table_do[CUST PRICE])');
                $event->getSheet()->getCell('G' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[CUSTOMER TOTAL])');
                $event->getSheet()->getCell('H' . $row->count() + 7)->setValue( '=SUBTOTAL(101,table_do[MARGIN])');
                $event->getSheet()->getCell('I' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[FACTORY PRICE])');
                $event->getSheet()->getCell('J' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[GROSS TOTAL])');
                $event->getSheet()->getCell('K' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[PPN])');
                $event->getSheet()->getCell('L' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[PPh22])');
                $event->getSheet()->getCell('M' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[BANK TRANSFER])');
                $event->getSheet()->getCell('N' . $row->count() + 7)->setValue( '=SUBTOTAL(109,table_do[INCOME])');

                $event->sheet->numberFormat('D' . $row->count() + 7 . ':N' . $row->count() + 7, NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

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
        $sheet->getStyle('A4:N4')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
        $sheet->getStyle('A6:N6')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A6:N6')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

        $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E6:N6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('PDO');
        $drawing->setPath(public_path('logo.png'));
        $drawing->setHeight(74);
        $drawing->setOffsetX(0);
        $drawing->setOffsetY(3);
        $drawing->setCoordinates('A1');

        return $drawing;
    }
}
