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
            'D' => NumberFormat::FORMAT_NUMBER,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
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
                $row->gross_total,
                $row->ppn_total,
                $row->pph22_total,
                $row->net_total,
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
                'NET PRICE',
                'CUSTOMER TOTAL',
                'MARGIN',
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
            'A' => 12,
            'B' => 20,
            'C' => 50,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $event->sheet->cellValue('B2', 'REPORT DO ' . str($this->factory->name)->upper());
                $event->sheet->cellValue('B3', 'PERIOD DATE ' . Carbon::create(request()->get('start_date'))->format('Y-m-d') . ' - ' . Carbon::create(request()->get('end_date'))->format('Y-m-d'));

            }
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('A4:K4')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
        $sheet->getStyle('D6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('A6:K6')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A6:K6')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

        $sheet->getStyle('D6:K6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('PDO');
        $drawing->setPath(public_path('logo.png'));
        $drawing->setHeight(74);
        $drawing->setCoordinates('A1');

        return $drawing;
    }

}
