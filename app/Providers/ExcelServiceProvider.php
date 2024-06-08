<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Sheet;

class ExcelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        Sheet::listen(AfterSheet::class, function () {
            Sheet::macro('fontColor', function (Sheet $sheet, string $cellRange, string $color) {
                $sheet->getDelegate()->getStyle($cellRange)->applyFromArray([
                    'font' => [
                        'color' => [
                            'rgb' => $color,
                        ]
                    ],
                ]);
            });
            Sheet::macro('fontBold', function (Sheet $sheet, string $cellRange, bool $bold) {
                $sheet->getDelegate()->getStyle($cellRange)->applyFromArray([
                    'font' => [
                        'bold' => $bold
                    ],
                ]);
            });
            Sheet::macro('fontItalic', function (Sheet $sheet, string $cellRange, bool $italic) {
                $sheet->getDelegate()->getStyle($cellRange)->applyFromArray([
                    'font' => [
                        'italic' => $italic
                    ],
                ]);
            });
            Sheet::macro('hAlignment', function (Sheet $sheet, string $cellRange, string $style) {
                $sheet->getDelegate()->getStyle($cellRange)->applyFromArray([
                    'alignment' => [
                        'horizontal' => $style,
                    ],
                ]);
            });
            Sheet::macro('vAlignment', function (Sheet $sheet, string $cellRange, string $style) {
                $sheet->getDelegate()->getStyle($cellRange)->applyFromArray([
                    'alignment' => [
                        'vertical' => $style,
                    ],
                ]);
            });
            Sheet::macro('setFilter', function (Sheet $sheet, string $cell){
                $sheet->getDelegate()->setAutoFilter($cell);
            });
            Sheet::macro('greaterThan', function (Sheet $sheet, string $cell, array $conditions){

                $conditionalStyles = $sheet->getDelegate()->getStyle($cell)->getConditionalStyles();

                foreach ($conditions as $key => $condition) {

                    $conditionalStyles[$key] = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
                    $conditionalStyles[$key]->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS);
                    $conditionalStyles[$key]->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_GREATERTHAN);
                    $conditionalStyles[$key]->addCondition($condition['value']);
                    $conditionalStyles[$key]->getStyle()->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'color' => [
                                'rgb' => $condition['fill_color'],
                            ]
                        ],
                        'font' => [
                            'bold' => $condition['bold'],
                            'color' => [
                                'rgb' => isset($condition['font_color']) ? $condition['font_color'] : '000000',
                            ],
                        ],
                    ]);
                }
                $sheet->getDelegate()->getStyle($cell)->setConditionalStyles($conditionalStyles);
            });
            Sheet::macro('setOrientation', function (Sheet $sheet, $orientation) {
                $sheet->getDelegate()->getPageSetup()->setOrientation($orientation);
            });
            Sheet::macro('pageMargin', function(Sheet $sheet, int $top, int $bottom, int $left, int $right){
                $sheet->getDelegate()->getPageMargins()->setTop($top);
                $sheet->getDelegate()->getPageMargins()->setRight($right);
                $sheet->getDelegate()->getPageMargins()->setLeft($left);
                $sheet->getDelegate()->getPageMargins()->setBottom($bottom);
            });
            Sheet::macro('setScale', function (Sheet $sheet, int $scale_value) {
                $sheet->getDelegate()->getPageSetup()->setScale($scale_value);
            });
            Sheet::macro('hCenter', function (Sheet $sheet, bool $hcenter) {
                $sheet->getDelegate()->getPageSetup()->setHorizontalCentered($hcenter);
            });
            Sheet::macro('vCenter', function (Sheet $sheet, bool $vcenter) {
                $sheet->getDelegate()->getPageSetup()->setVerticalCentered($vcenter);
            });
            Sheet::macro('fitToPage', function(Sheet $sheet, bool $fit = false){
                $sheet->getDelegate()->getPageSetup()->setFitToPage($fit);
            });
            Sheet::macro('fitPage', function(Sheet $sheet, bool $fitWidth = false,  bool $fitHeight = false){
                $sheet->getDelegate()->getPageSetup()->setFitToWidth($fitWidth);
                $sheet->getDelegate()->getPageSetup()->setFitToHeight($fitHeight);
            });
            Sheet::macro('rowRepeat', function(Sheet $sheet, int $start, int $end){
                $sheet->getDelegate()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($start, $end);
            });
            Sheet::macro('styleCells', function (Sheet $sheet, string $cellRange, array $style) {
                $sheet->getDelegate()->getStyle($cellRange)->applyFromArray($style);
            });
            Sheet::macro('cellValue', function (Sheet $sheet, string $cell, string $value) {
                $sheet->getDelegate()->setCellValue($cell, $value);
            });
            Sheet::macro('colWidth', function (Sheet $sheet, string $cell, int $value) {
                $sheet->getDelegate()->getColumnDimension($cell)->setWidth($value);
            });
            Sheet::macro('rowHeight', function (Sheet $sheet, int $cell, int $value) {
                $sheet->getDelegate()->getRowDimension($cell)->setRowHeight($value);
            });
            Sheet::macro('mergeCell', function (Sheet $sheet, string $cells){
                $sheet->mergeCells($cells);
            });
            Sheet::macro('numberFormat', function (Sheet $sheet, string $cellRange, $format){
                $sheet->getDelegate()->getStyle($cellRange)->getNumberFormat()->setFormatCode($format);
            });
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
