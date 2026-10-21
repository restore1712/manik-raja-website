<?php
require 'config.php';
require_admin();

if (!class_exists(ZipArchive::class)) {
    http_response_code(500);
    exit('Fitur ekspor XLSX memerlukan ekstensi PHP ZipArchive.');
}

$members = $pdo->query("SELECT m.*, w.nama AS wilayah_nama, d.nama AS daerah_nama, d.jenis AS daerah_jenis
    FROM members m
    LEFT JOIN wilayah w ON w.id = m.wilayah_id
    LEFT JOIN daerah d ON d.id = m.daerah_id
    WHERE m.deleted_at IS NULL ORDER BY m.full_name ASC")->fetchAll();
$regions = $pdo->query('SELECT id, nama FROM wilayah ORDER BY nama')->fetchAll();
$areas = $pdo->query('SELECT wilayah_id, jenis, nama FROM daerah ORDER BY nama')->fetchAll();

function xlsxEsc(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function xlsxCol(int $number): string
{
    $result = '';
    while ($number > 0) {
        $number--;
        $result = chr(65 + ($number % 26)) . $result;
        $number = intdiv($number, 26);
    }
    return $result;
}

function xlsxInlineCell(string $ref, string $value, int $style = 0): string
{
    return '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t xml:space="preserve">' . xlsxEsc($value) . '</t></is></c>';
}

function xlsxNumberCell(string $ref, int $value): string
{
    return '<c r="' . $ref . '"><v>' . $value . '</v></c>';
}

function xlsxFormulaCell(string $ref, string $formula): string
{
    return '<c r="' . $ref . '" t="str"><f>' . xlsxEsc($formula) . '</f><v></v></c>';
}

function xlsxSheetData(array $rows, array $widths = [], ?string $validations = null, ?string $filter = null): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="18"/>';
    if ($widths) {
        $xml .= '<cols>';
        foreach ($widths as $index => $width) {
            $col = $index + 1;
            $xml .= '<col min="' . $col . '" max="' . $col . '" width="' . $width . '" customWidth="1"' . ($col >= 14 ? ' hidden="1"' : '') . '/>';
        }
        $xml .= '</cols>';
    }
    $xml .= '<sheetData>';
    foreach ($rows as $rowIndex => $cells) {
        $r = $rowIndex + 1;
        $xml .= '<row r="' . $r . '">';
        foreach ($cells as $colIndex => $value) {
            $ref = xlsxCol($colIndex + 1) . $r;
            $style = $r === 1 ? 1 : ($colIndex === 1 ? 2 : 0);
            if (is_array($value) && isset($value['formula'])) {
                $xml .= xlsxFormulaCell($ref, (string)$value['formula']);
            } elseif (is_int($value)) {
                $xml .= xlsxNumberCell($ref, $value);
            } else {
                $xml .= xlsxInlineCell($ref, (string)$value, $style);
            }
        }
        $xml .= '</row>';
    }
    $xml .= '</sheetData>';
    if ($filter) {
        $xml .= '<autoFilter ref="' . $filter . '"/>';
    }
    if ($validations) {
        $xml .= $validations;
    }
    return $xml . '</worksheet>';
}

$headers = ['Nama Panggoaran', 'Telepon', 'Email', 'Wilayah', 'Kota', 'Catatan', 'Status Anggota', 'Suami', 'Status Suami', 'Istri', 'Status Istri', 'Jumlah Anak', 'Daerah', 'ID_INTERNAL'];
$rows = [$headers];
foreach ($members as $m) {
    $city = (string)($m['city'] ?? '');
    if ($city === '' && ($m['daerah_jenis'] ?? '') === 'kota') {
        $city = (string)($m['daerah_nama'] ?? '');
    }
    $rows[] = [
        (string)($m['panggoaran'] ?? ''),
        (string)($m['phone'] ?? ''),
        (string)($m['email'] ?? ''),
        (string)($m['wilayah_nama'] ?? ''),
        $city,
        (string)($m['notes'] ?? ''),
        ($m['status'] ?? 'active') === 'inactive' ? 'Nonaktif' : 'Aktif',
        (string)($m['husband_name'] ?? ''),
        (string)($m['husband_status'] ?? 'hidup'),
        (string)($m['wife_name'] ?? ''),
        (string)($m['wife_status'] ?? 'hidup'),
        (int)($m['children_count'] ?? 0),
        (string)($m['daerah_nama'] ?? ''),
        (int)$m['id'],
    ];
}

$lastRow = max(501, count($rows) + 100);
$memberCount = count($rows);
for ($excelRow = 2; $excelRow <= $lastRow; $excelRow++) {
    $rowIndex = $excelRow - 1;
    if (!isset($rows[$rowIndex])) {
        $rows[$rowIndex] = array_fill(0, 14, '');
    }
    $rows[$rowIndex][14] = ['formula' => 'IFERROR("Prov_"&INDEX(WilayahIds,MATCH(D' . $excelRow . ',WilayahList,0))&"_Kota","EmptyList")'];
    $rows[$rowIndex][15] = ['formula' => 'IFERROR("Prov_"&INDEX(WilayahIds,MATCH(D' . $excelRow . ',WilayahList,0))&"_Daerah","EmptyList")'];
}
$provinceEndRow = count($regions) + 1;
$validationXml = '<dataValidations count="5">'
    . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="D2:D' . $lastRow . '"><formula1>WilayahList</formula1></dataValidation>'
    . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="E2:E' . $lastRow . '"><formula1>INDIRECT($O2)</formula1></dataValidation>'
    . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="G2:G' . $lastRow . '"><formula1>&quot;Aktif,Nonaktif&quot;</formula1></dataValidation>'
    . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="I2:I' . $lastRow . ' K2:K' . $lastRow . '"><formula1>&quot;hidup,meninggal&quot;</formula1></dataValidation>'
    . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="M2:M' . $lastRow . '"><formula1>INDIRECT($P2)</formula1></dataValidation>'
    . '</dataValidations>';

$provinceRows = [['ID', 'Wilayah']];
$cityRows = [];
$districtRows = [];
$definedNames = '<definedName name="WilayahList">ListWilayah!$B$2:$B$' . (count($regions) + 1) . '</definedName>'
    . '<definedName name="WilayahIds">ListWilayah!$A$2:$A$' . (count($regions) + 1) . '</definedName>';
$byProvince = [];
foreach ($areas as $area) {
    $byProvince[(int)$area['wilayah_id']][$area['jenis']][] = (string)$area['nama'];
}
foreach ($regions as $index => $region) {
    $provinceId = (int)$region['id'];
    $rowNumber = $index + 1;
    $provinceRows[] = [$provinceId, (string)$region['nama']];
    $provinceCities = $byProvince[$provinceId]['kota'] ?? [];
    $provinceDistricts = $byProvince[$provinceId]['kabupaten'] ?? [];
    // Kota lists city-level areas only; Daerah accepts cities and regencies.
    $provinceDistricts = array_values(array_unique(array_merge($provinceCities, $provinceDistricts)));
    $cityRows[] = $provinceCities ?: [''];
    $districtRows[] = $provinceDistricts ?: [''];
    $cityLastCol = xlsxCol(max(1, count($provinceCities)));
    $districtLastCol = xlsxCol(max(1, count($provinceDistricts)));
    $definedNames .= '<definedName name="Prov_' . $provinceId . '_Kota">ListKota!$A$' . $rowNumber . ':$' . $cityLastCol . '$' . $rowNumber . '</definedName>';
    $definedNames .= '<definedName name="Prov_' . $provinceId . '_Daerah">ListDaerah!$A$' . $rowNumber . ':$' . $districtLastCol . '$' . $rowNumber . '</definedName>';
}
$emptyListRow = count($regions) + 1;
$cityRows[] = [''];
$districtRows[] = [''];
$definedNames .= '<definedName name="EmptyList">ListKota!$A$' . $emptyListRow . '</definedName>';

$memberSheet = xlsxSheetData($rows, [28, 20, 30, 32, 35, 36, 18, 28, 17, 28, 17, 16, 38, 14, 0, 0], $validationXml, 'A1:M' . max(1, $memberCount));
$provinceSheet = xlsxSheetData($provinceRows);
$citySheet = xlsxSheetData($cityRows);
$districtSheet = xlsxSheetData($districtRows);
$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><name val="Calibri"/><sz val="11"/></font><font><name val="Calibri"/><b/><color rgb="FFFFFFFF"/><sz val="11"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD9E2EC"/></left><right style="thin"><color rgb="FFD9E2EC"/></right><top style="thin"><color rgb="FFD9E2EC"/></top><bottom style="thin"><color rgb="FFD9E2EC"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/><xf numFmtId="49" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet4.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
$rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
$workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Anggota" sheetId="1" r:id="rId1"/><sheet name="ListWilayah" sheetId="2" state="hidden" r:id="rId2"/><sheet name="ListKota" sheetId="3" state="hidden" r:id="rId3"/><sheet name="ListDaerah" sheetId="4" state="hidden" r:id="rId4"/></sheets><definedNames>' . $definedNames . '</definedNames><calcPr calcId="191029" fullCalcOnLoad="1"/></workbook>';
$workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/><Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet4.xml"/><Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
$temporary = tempnam(sys_get_temp_dir(), 'anggota-xlsx-');
$zip = new ZipArchive();
if ($zip->open($temporary, ZipArchive::OVERWRITE) !== true) {
    @unlink($temporary);
    http_response_code(500);
    exit('Tidak dapat membuat file Excel.');
}
$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', $rootRels);
$zip->addFromString('xl/workbook.xml', $workbook);
$zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
$zip->addFromString('xl/styles.xml', $styles);
$zip->addFromString('xl/worksheets/sheet1.xml', $memberSheet);
$zip->addFromString('xl/worksheets/sheet2.xml', $provinceSheet);
$zip->addFromString('xl/worksheets/sheet3.xml', $citySheet);
$zip->addFromString('xl/worksheets/sheet4.xml', $districtSheet);
$zip->close();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="template-data-anggota.xlsx"');
header('Content-Length: ' . filesize($temporary));
header('Cache-Control: no-store, no-cache, must-revalidate');
readfile($temporary);
unlink($temporary);
exit;
