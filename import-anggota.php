<?php
require 'config.php';
require_admin();

function importError(string $message): never
{
    $_SESSION['admin_flash'] = $message;
    $_SESSION['admin_flash_type'] = 'error';
    // Flash ditampilkan di bagian atas admin.php; jangan lompat ke anchor
    // agar pesan hasil impor tetap terlihat setelah redirect.
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}
if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf'])) {
    http_response_code(403);
    exit('Token keamanan tidak valid. Muat ulang laman admin dan coba kembali.');
}
if (!class_exists(ZipArchive::class)) {
    importError('Import Excel memerlukan ekstensi PHP ZipArchive.');
}
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    importError('File Excel gagal diunggah. Pilih file .xlsx dan pastikan ukurannya tidak lebih dari 10 MB.');
}
$file = $_FILES['excel_file'];
if ($file['size'] > 10 * 1024 * 1024 || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
    importError('Format file harus .xlsx dan ukurannya maksimal 10 MB.');
}

$zip = new ZipArchive();
if ($zip->open($file['tmp_name']) !== true) {
    importError('File Excel tidak dapat dibaca. Unduh template terbaru lalu coba kembali.');
}
$workbookXml = $zip->getFromName('xl/workbook.xml');
$relationshipsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
$sharedXml = $zip->getFromName('xl/sharedStrings.xml');
if ($workbookXml === false || $relationshipsXml === false) {
    $zip->close();
    importError('Struktur file bukan workbook Excel .xlsx yang valid.');
}

libxml_use_internal_errors(true);
$workbook = simplexml_load_string($workbookXml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
$relationships = simplexml_load_string($relationshipsXml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
if (!$workbook || !$relationships) {
    $zip->close();
    importError('Struktur workbook Excel tidak valid.');
}
$workbook->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
$workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
$sheetNodes = $workbook->xpath('//x:sheets/x:sheet');
$sheetTarget = null;
foreach ($sheetNodes ?: [] as $sheetNode) {
    if ((string)$sheetNode['name'] === 'Anggota') {
        $relationId = (string)$sheetNode->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        break;
    }
}
if (empty($relationId)) {
    $zip->close();
    importError('Lembar "Anggota" tidak ditemukan. Gunakan template Excel dari admin.');
}
$relationships->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
foreach ($relationships->xpath('//r:Relationship') ?: [] as $relationship) {
    if ((string)$relationship['Id'] === $relationId) {
        $sheetTarget = (string)$relationship['Target'];
        break;
    }
}
if (!$sheetTarget || str_contains($sheetTarget, '..')) {
    $zip->close();
    importError('Lokasi lembar Anggota di workbook tidak valid.');
}
$sheetPath = str_starts_with($sheetTarget, '/') ? ltrim($sheetTarget, '/') : 'xl/' . ltrim($sheetTarget, '/');
$sheetXml = $zip->getFromName($sheetPath);
if ($sheetXml === false || strlen($sheetXml) > 20 * 1024 * 1024) {
    $zip->close();
    importError('Isi lembar Excel kosong atau terlalu besar.');
}

$sharedStrings = [];
if ($sharedXml !== false && strlen($sharedXml) <= 10 * 1024 * 1024) {
    $shared = simplexml_load_string($sharedXml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
    if ($shared) {
        foreach ($shared->xpath('//*[local-name()="si"]') ?: [] as $item) {
            $parts = [];
            foreach ($item->xpath('.//*[local-name()="t"]') ?: [] as $part) {
                $parts[] = (string)$part;
            }
            $sharedStrings[] = implode('', $parts);
        }
    }
}
$sheet = simplexml_load_string($sheetXml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
if (!$sheet) {
    $zip->close();
    importError('Data di lembar Excel tidak dapat dibaca.');
}
$excelRows = [];
foreach ($sheet->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $rowNode) {
    $rowNumber = (int)$rowNode['r'];
    if ($rowNumber > 10002) {
        $zip->close();
        importError('Template melebihi batas 10.000 baris.');
    }
    $cells = [];
    foreach ($rowNode->xpath('./*[local-name()="c"]') ?: [] as $cell) {
        $reference = (string)$cell['r'];
        preg_match('/^[A-Z]+/', $reference, $columnMatch);
        if (!$columnMatch) {
            continue;
        }
        $column = 0;
        foreach (str_split($columnMatch[0]) as $letter) {
            $column = $column * 26 + ord($letter) - 64;
        }
        $type = (string)$cell['t'];
        $value = '';
        if ($type === 'inlineStr') {
            $parts = [];
            foreach ($cell->xpath('./*[local-name()="is"]//*[local-name()="t"]') ?: [] as $part) {
                $parts[] = (string)$part;
            }
            $value = implode('', $parts);
        } else {
            $valueNode = $cell->xpath('./*[local-name()="v"]');
            $value = isset($valueNode[0]) ? (string)$valueNode[0] : '';
            if ($type === 's' && $value !== '') {
                $value = $sharedStrings[(int)$value] ?? '';
            }
        }
        $cells[$column] = trim($value);
    }
    $excelRows[$rowNumber] = $cells;
}
$zip->close();

$expectedHeaders = ['Nama Panggoaran', 'Telepon', 'Email', 'Wilayah', 'Kota', 'Catatan', 'Status Anggota', 'Suami', 'Status Suami', 'Istri', 'Status Istri', 'Jumlah Anak', 'Daerah'];
foreach ($expectedHeaders as $index => $header) {
    if (($excelRows[1][$index + 1] ?? '') !== $header) {
        importError('Kolom template tidak sesuai. Unduh kembali template Excel terbaru dari admin.');
    }
}

$findRegion = $pdo->prepare('SELECT id FROM wilayah WHERE nama = ? LIMIT 1');
$findAreaByName = $pdo->prepare('SELECT id FROM daerah WHERE wilayah_id = ? AND nama = ? LIMIT 1');
$existingMember = $pdo->prepare('SELECT id, deleted_at FROM members WHERE id = ? LIMIT 1');
$findMemberByIdentity = $pdo->prepare("SELECT id, deleted_at FROM members
    WHERE deleted_at IS NULL
      AND panggoaran = ?
      AND COALESCE(husband_name, '') = ?
      AND COALESCE(wife_name, '') = ?
    ORDER BY id");
$records = [];
$seenIds = [];
$seenNewIdentities = [];
$errors = [];
$validStatuses = ['hidup' => 'hidup', 'meninggal' => 'meninggal'];
foreach ($excelRows as $rowNumber => $cells) {
    if ($rowNumber === 1) {
        continue;
    }
    $values = [];
    for ($i = 1; $i <= 14; $i++) {
        $values[$i] = trim((string)($cells[$i] ?? ''));
    }
    if (implode('', $values) === '') {
        continue;
    }
    $name = $values[1];
    $status = mb_strtolower($values[7]);
    $husbandStatus = mb_strtolower($values[9] ?: 'hidup');
    $wifeStatus = mb_strtolower($values[11] ?: 'hidup');
    $husbandStatus = $validStatuses[$husbandStatus] ?? null;
    $wifeStatus = $validStatuses[$wifeStatus] ?? null;
    $status = match ($status) {
        'aktif', 'active', '' => 'active',
        'nonaktif', 'inactive' => 'inactive',
        default => null,
    };
    $phone = $values[2];
    $email = $values[3];
    $regionName = $values[4];
    $cityName = $values[5];
    $notes = $values[6];
    $husbandName = $values[8];
    $wifeName = $values[10];
    $childrenRaw = $values[12];
    $areaName = $values[13];
    $idRaw = $values[14];
    $id = $idRaw === '' ? null : (ctype_digit($idRaw) ? (int)$idRaw : -1);
    $children = $childrenRaw === '' ? 0 : (ctype_digit($childrenRaw) ? (int)$childrenRaw : -1);

    if ($name === '' || mb_strlen($name) > 150) $errors[] = "Baris $rowNumber: Nama panggoaran wajib diisi (maksimal 150 karakter).";
    if (!$husbandStatus || !$wifeStatus || !$status) $errors[] = "Baris $rowNumber: Periksa pilihan status anggota serta status suami dan istri.";
    if (mb_strlen($phone) > 40 || mb_strlen($email) > 150 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) $errors[] = "Baris $rowNumber: Telepon atau email tidak valid.";
    if (mb_strlen($cityName) > 100 || mb_strlen($husbandName) > 150 || mb_strlen($wifeName) > 150) $errors[] = "Baris $rowNumber: Salah satu isian melebihi batas panjang kolom.";
    if ($children < 0) $errors[] = "Baris $rowNumber: Jumlah Anak harus berupa angka nol atau lebih.";
    if ($id === -1) $errors[] = "Baris $rowNumber: ID internal tidak valid.";
    $existing = null;
    if ($id !== null && $id > 0) {
        if (isset($seenIds[$id])) $errors[] = "Baris $rowNumber: ID anggota muncul lebih dari satu kali.";
        $seenIds[$id] = true;
        $existingMember->execute([$id]);
        $existing = $existingMember->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            $errors[] = "Baris $rowNumber: ID anggota tidak ditemukan.";
        }
    }

    // A blank ID can happen when the same workbook is imported again before
    // downloading a fresh export. Match the household to update it instead
    // of inserting a duplicate member.
    if ($id === null) {
        $identityKey = mb_strtolower($name . "\0" . $husbandName . "\0" . $wifeName, 'UTF-8');
        if (isset($seenNewIdentities[$identityKey])) {
            $errors[] = "Baris $rowNumber: Data keluarga yang sama muncul lebih dari sekali di template.";
        }
        $seenNewIdentities[$identityKey] = true;

        $findMemberByIdentity->execute([$name, $husbandName, $wifeName]);
        $identityMatches = $findMemberByIdentity->fetchAll(PDO::FETCH_ASSOC);
        if (count($identityMatches) === 1) {
            $id = (int)$identityMatches[0]['id'];
            $existing = $identityMatches[0];
        } elseif (count($identityMatches) > 1) {
            $errors[] = "Baris $rowNumber: Data keluarga cocok dengan beberapa anggota. Unduh template terbaru yang memuat ID internal.";
        }
    }

    $regionId = null;
    $areaId = null;
    $regionIsJakarta = $regionName === 'Daerah Khusus Ibukota Jakarta';
    if ($regionName !== '') {
        $findRegion->execute([$regionName]);
        $regionId = $findRegion->fetchColumn();
        if (!$regionId) {
            $errors[] = "Baris $rowNumber: Wilayah '$regionName' tidak ditemukan.";
        } else {
            if ($cityName !== '') {
                // Data lama dapat menyimpan kabupaten pada kolom Kota.
                // Terima nama kota/kabupaten yang valid dalam wilayah tersebut.
                $findAreaByName->execute([(int)$regionId, $cityName]);
                if (!$findAreaByName->fetchColumn()) $errors[] = "Baris $rowNumber: Kota/kabupaten '$cityName' tidak sesuai dengan wilayah yang dipilih.";
            }
            if ($regionIsJakarta && $areaName === '') $areaName = $cityName;
            if ($areaName !== '') {
                // Kolom Daerah dapat berisi kota (termasuk DKI) atau kabupaten,
                // bergantung pada pilihan anggota di template.
                $findAreaByName->execute([(int)$regionId, $areaName]);
                $areaId = $findAreaByName->fetchColumn();
                if (!$areaId) $errors[] = "Baris $rowNumber: Daerah '$areaName' tidak sesuai dengan wilayah yang dipilih.";
            }
        }
    } elseif ($cityName !== '' || $areaName !== '') {
        $errors[] = "Baris $rowNumber: Pilih Wilayah sebelum mengisi Kota atau Daerah.";
    }

    $records[] = [
        'id' => $id && $id > 0 ? $id : null,
        'restore' => $existing && $existing['deleted_at'] !== null,
        'panggoaran' => $name,
        'full_name' => $husbandName !== '' ? $husbandName : ($wifeName !== '' ? $wifeName : $name),
        'phone' => $phone,
        'email' => $email,
        'city' => $cityName,
        'notes' => $notes,
        'status' => $status,
        'husband_name' => $husbandName,
        'husband_status' => $husbandStatus,
        'wife_name' => $wifeName,
        'wife_status' => $wifeStatus,
        'children_count' => $children,
        'wilayah_id' => $regionId ? (int)$regionId : null,
        'daerah_id' => $areaId ? (int)$areaId : null,
    ];
}
if ($errors) {
    importError('Import dibatalkan, belum ada data yang diubah. ' . implode(' ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? ' Masih ada ' . (count($errors) - 5) . ' kesalahan lain.' : ''));
}
if (!$records) {
    importError('Tidak ada baris data anggota untuk diimpor.');
}

try {
    $pdo->beginTransaction();
    $update = $pdo->prepare('UPDATE members SET panggoaran = ?, phone = ?, email = ?, city = ?, notes = ?, status = ?, husband_name = ?, husband_status = ?, wife_name = ?, wife_status = ?, children_count = ?, wilayah_id = ?, daerah_id = ?, deleted_at = NULL WHERE id = ?');
    $insert = $pdo->prepare('INSERT INTO members (panggoaran, full_name, phone, email, city, notes, status, husband_name, husband_status, wife_name, wife_status, children_count, wilayah_id, daerah_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $updated = 0;
    $restored = 0;
    $inserted = 0;
    foreach ($records as $record) {
        $memberValues = [
            $record['panggoaran'], $record['phone'] ?: null, $record['email'] ?: null,
            $record['city'] ?: null, $record['notes'] ?: null, $record['status'], $record['husband_name'] ?: null,
            $record['husband_status'], $record['wife_name'] ?: null, $record['wife_status'], $record['children_count'],
            $record['wilayah_id'], $record['daerah_id'],
        ];
        if ($record['id']) {
            $update->execute([...$memberValues, $record['id']]);
            if ($record['restore']) $restored++;
            else $updated++;
        } else {
            $insert->execute([
                $record['panggoaran'], $record['full_name'], ...array_slice($memberValues, 1),
            ]);
            $inserted++;
        }
    }
    $pdo->commit();
    $_SESSION['admin_flash'] = "Import berhasil. $inserted anggota baru ditambahkan, $updated data diperbarui, dan $restored anggota yang sebelumnya dihapus dipulihkan.";
    $_SESSION['admin_flash_type'] = 'success';
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Import anggota gagal: ' . $exception->getMessage());
    $_SESSION['admin_flash'] = 'Import gagal karena terjadi kesalahan saat menyimpan. Tidak ada data yang diubah.';
    $_SESSION['admin_flash_type'] = 'error';
}
header('Location: admin.php');
exit;
