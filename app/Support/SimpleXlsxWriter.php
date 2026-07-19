<?php

namespace App\Support;

use ZipArchive;

class SimpleXlsxWriter
{
    public function write(string $path, array $sheets): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels(count($sheets)));
        $zip->addFromString('xl/styles.xml', $this->styles());

        foreach (array_values($sheets) as $index => $sheet) {
            $zip->addFromString('xl/worksheets/sheet'.($index + 1).'.xml', $this->worksheet($sheet));
        }

        $zip->close();
    }

    private function worksheet(array $sheet): string
    {
        $rows = $sheet['rows'] ?? [];
        $styled = (bool) ($sheet['styled'] ?? false);
        $xmlRows = [];
        foreach (array_values($rows) as $rowIndex => $row) {
            $cells = [];
            foreach (array_values($row) as $columnIndex => $value) {
                $styleId = $this->styleId($rowIndex, $columnIndex, $styled);
                if (is_array($value)) {
                    $styleId = (int) ($value['style'] ?? $styleId);
                    $value = $value['value'] ?? '';
                }

                $cell = $this->columnName($columnIndex + 1).($rowIndex + 1);
                $style = $styleId > 0 ? ' s="'.$styleId.'"' : '';
                if (is_int($value) || is_float($value)) {
                    $cells[] = '<c r="'.$cell.'"'.$style.'><v>'.$value.'</v></c>';
                } else {
                    $cells[] = '<c r="'.$cell.'"'.$style.' t="inlineStr"><is><t>'.$this->escape((string) $value).'</t></is></c>';
                }
            }
            $height = $this->rowHeight($rowIndex, $styled);
            $xmlRows[] = '<row r="'.($rowIndex + 1).'" ht="'.$height.'" customHeight="1">'.implode('', $cells).'</row>';
        }

        $columnWidths = $this->columnWidths($sheet['widths'] ?? []);
        $mergeCells = $this->mergeCells($sheet['mergeCells'] ?? []);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .$columnWidths
            .'<sheetFormatPr defaultRowHeight="22"/>'
            .'<sheetData>'.implode('', $xmlRows).'</sheetData>'
            .$mergeCells
            .'</worksheet>';
    }

    private function columnWidths(array $widths): string
    {
        if ($widths === []) {
            return '';
        }

        $columns = [];
        foreach (array_values($widths) as $index => $width) {
            $column = $index + 1;
            $columns[] = '<col min="'.$column.'" max="'.$column.'" width="'.(float) $width.'" customWidth="1"/>';
        }

        return '<cols>'.implode('', $columns).'</cols>';
    }

    private function mergeCells(array $ranges): string
    {
        if ($ranges === []) {
            return '';
        }

        $cells = array_map(fn (string $range) => '<mergeCell ref="'.$this->escape($range).'"/>', $ranges);

        return '<mergeCells count="'.count($cells).'">'.implode('', $cells).'</mergeCells>';
    }

    private function styleId(int $rowIndex, int $columnIndex, bool $styled): int
    {
        if (! $styled) {
            return 0;
        }

        if ($rowIndex === 0) {
            return 1;
        }

        if ($rowIndex === 1) {
            return 2;
        }

        return match ($columnIndex) {
            0, 8 => 4,
            10 => 5,
            default => 3,
        };
    }

    private function rowHeight(int $rowIndex, bool $styled): int
    {
        if (! $styled) {
            return 22;
        }

        return match ($rowIndex) {
            0 => 28,
            1 => 24,
            default => 22,
        };
    }

    private function workbook(array $sheets): string
    {
        $items = [];
        foreach (array_values($sheets) as $index => $sheet) {
            $items[] = '<sheet name="'.$this->escape($this->sheetName($sheet['name'] ?? 'Sheet '.($index + 1))).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.implode('', $items).'</sheets>'
            .'</workbook>';
    }

    private function workbookRels(int $count): string
    {
        $rels = [];
        for ($i = 1; $i <= $count; $i++) {
            $rels[] = '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }
        $rels[] = '<Relationship Id="rId'.($count + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.implode('', $rels).'</Relationships>';
    }

    private function contentTypes(int $count): string
    {
        $overrides = [
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>',
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>',
        ];
        for ($i = 1; $i <= $count; $i++) {
            $overrides[] = '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .implode('', $overrides)
            .'</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0"/></numFmts>'
            .'<fonts count="3">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="14"/><color rgb="FF004528"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FF1F2937"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAF7"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border/>'
            .'<border>'
            .'<left style="thin"><color rgb="FFD1D5DB"/></left>'
            .'<right style="thin"><color rgb="FFD1D5DB"/></right>'
            .'<top style="thin"><color rgb="FFD1D5DB"/></top>'
            .'<bottom style="thin"><color rgb="FFD1D5DB"/></bottom>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="6">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function sheetName(string $name): string
    {
        return mb_substr(str_replace(['\\', '/', '?', '*', '[', ']'], '-', $name), 0, 31);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
