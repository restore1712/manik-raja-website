<?php

require 'config.php';
require_admin();

$officerRoleSlots = [
    ['key' => 'penasehat', 'number' => 1, 'label' => 'Penasehat'],
    ['key' => 'penasehat', 'number' => 2, 'label' => 'Penasehat'],
    ['key' => 'penasehat', 'number' => 3, 'label' => 'Penasehat'],
    ['key' => 'ketua', 'number' => 1, 'label' => 'Ketua'],
    ['key' => 'wakil_ketua', 'number' => 1, 'label' => 'Wakil Ketua'],
    ['key' => 'sekretaris', 'number' => 1, 'label' => 'Sekretaris'],
    ['key' => 'wakil_sekretaris', 'number' => 1, 'label' => 'Wakil Sekretaris'],
    ['key' => 'bendahara', 'number' => 1, 'label' => 'Bendahara'],
    ['key' => 'wakil_bendahara', 'number' => 1, 'label' => 'Wakil Bendahara'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf']) ||
        !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])
    ) {
        exit('Token keamanan tidak valid.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'set_admin_recovery_email') {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $adminId = (int)($_SESSION['admin_id'] ?? 0);

        if ($adminId < 1 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $_SESSION['admin_flash'] = 'Masukkan alamat email yang valid.';
            $_SESSION['admin_flash_type'] = 'error';
        } elseif (!recovery_email_is_configured()) {
            $_SESSION['admin_flash'] = 'Pengiriman email belum dikonfigurasi. Admin server perlu mengatur APP_BASE_URL (HTTPS), MAIL_FROM, APP_KEY, dan layanan email.';
            $_SESSION['admin_flash_type'] = 'error';
        } else {
            $existingEmail = $pdo->prepare('SELECT id FROM admins WHERE email = ? AND id <> ? LIMIT 1');
            $existingEmail->execute([$email, $adminId]);
            if ($existingEmail->fetchColumn()) {
                $_SESSION['admin_flash'] = 'Email tersebut sudah dipakai akun admin lain.';
                $_SESSION['admin_flash_type'] = 'error';
            } else {
                $token = bin2hex(random_bytes(32));
                $verifyUrl = recovery_url('verify-admin-email.php', $token);
                try {
                    if ($verifyUrl === null) {
                        throw new RuntimeException('URL verifikasi belum dapat dibuat.');
                    }
                    $pdo->beginTransaction();
                    $invalidate = $pdo->prepare('UPDATE admin_email_verifications SET used_at = NOW() WHERE admin_id = ? AND used_at IS NULL');
                    $invalidate->execute([$adminId]);
                    $save = $pdo->prepare('INSERT INTO admin_email_verifications (admin_id, email, token_hash, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))');
                    $save->execute([$adminId, $email, hash('sha256', $token)]);
                    $pdo->commit();

                    $sent = send_security_email(
                        $email,
                        'Verifikasi email pemulihan admin Pomparan Manik Raja',
                        "Halo,\n\n" .
                        "Gunakan tautan berikut untuk memverifikasi email pemulihan akun admin. Tautan berlaku 30 menit dan hanya dapat digunakan sekali:\n\n" .
                        $verifyUrl . "\n\nJika Anda tidak meminta verifikasi ini, abaikan email ini.\n"
                    );
                    if ($sent) {
                        $_SESSION['admin_flash'] = 'Tautan verifikasi telah dikirim. Email pemulihan baru aktif setelah tautan dibuka dan dikonfirmasi.';
                        $_SESSION['admin_flash_type'] = 'success';
                    } else {
                        $cancel = $pdo->prepare('UPDATE admin_email_verifications SET used_at = NOW() WHERE token_hash = ?');
                        $cancel->execute([hash('sha256', $token)]);
                        $_SESSION['admin_flash'] = 'Email verifikasi gagal dikirim. Periksa konfigurasi layanan email server lalu coba lagi.';
                        $_SESSION['admin_flash_type'] = 'error';
                    }
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    error_log('Pembuatan verifikasi email admin gagal: ' . $exception->getMessage());
                    $_SESSION['admin_flash'] = 'Tautan verifikasi belum dapat dibuat. Periksa konfigurasi database dan server.';
                    $_SESSION['admin_flash_type'] = 'error';
                }
            }
        }
    }
    elseif ($action === 'add_officer_group') {
        $name = trim((string)($_POST['name'] ?? ''));
        $level = (string)($_POST['level'] ?? 'pusat');
        $period = trim((string)($_POST['period'] ?? ''));
        $region = trim((string)($_POST['region'] ?? ''));
        if (!in_array($level, ['pusat', 'regional', 'daerah'], true)) $level = 'pusat';
        if ($level === 'pusat') $region = '';

        if ($name !== '') {
            $createGroup = $pdo->prepare('INSERT INTO officer_groups (name, level, region, period) VALUES (?, ?, ?, ?)');
            $createGroup->execute([$name, $level, $region !== '' ? $region : null, $period !== '' ? $period : null]);
            $groupId = (int)$pdo->lastInsertId();
            $createSlot = $pdo->prepare("INSERT INTO officers (officer_group_id, slot_key, slot_number, name, position_name, period, level, region) VALUES (?, ?, ?, '', ?, NULL, ?, ?)");
            foreach ($officerRoleSlots as $slot) {
                $createSlot->execute([$groupId, $slot['key'], $slot['number'], $slot['label'], $level, $region !== '' ? $region : null]);
            }
            $_SESSION['admin_flash'] = 'Kelompok pengurus dibuat. Silakan isi nama dan foto pada jabatan yang tersedia.';
            $_SESSION['admin_flash_type'] = 'success';
        } else {
            $_SESSION['admin_flash'] = 'Nama kelompok wajib diisi.';
            $_SESSION['admin_flash_type'] = 'error';
        }
    }

    elseif ($action === 'update_officer_group') {
        $groupId = (int)($_POST['group_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $level = (string)($_POST['level'] ?? 'pusat');
        $period = trim((string)($_POST['period'] ?? ''));
        $region = trim((string)($_POST['region'] ?? ''));
        if (!in_array($level, ['pusat', 'regional', 'daerah'], true)) $level = 'pusat';
        if ($level === 'pusat') $region = '';

        if ($groupId > 0 && $name !== '') {
            $saveGroup = $pdo->prepare('UPDATE officer_groups SET name = ?, level = ?, region = ?, period = ? WHERE id = ?');
            $saveGroup->execute([$name, $level, $region !== '' ? $region : null, $period !== '' ? $period : null, $groupId]);
            $syncGroupMembers = $pdo->prepare('UPDATE officers SET level = ?, region = ? WHERE officer_group_id = ?');
            $syncGroupMembers->execute([$level, $region !== '' ? $region : null, $groupId]);
            $_SESSION['admin_flash'] = 'Data kelompok pengurus diperbarui.';
            $_SESSION['admin_flash_type'] = 'success';
        }
    }

    elseif ($action === 'save_officer_group_slots') {
        $groupId = (int)($_POST['group_id'] ?? 0);
        $postedSlots = $_POST['slots'] ?? [];
        $photoUploads = $_FILES['officer_photos'] ?? [];
        $newPhotos = [];
        $oldPhotos = [];

        try {
            if ($groupId < 1 || !is_array($postedSlots) || !$postedSlots) {
                throw new RuntimeException('Data jabatan tidak ditemukan. Muat ulang halaman lalu coba lagi.');
            }

            $pdo->beginTransaction();
            $findSlot = $pdo->prepare('SELECT photo_path FROM officers WHERE id = ? AND officer_group_id = ? LIMIT 1');
            $saveSlot = $pdo->prepare('UPDATE officers SET name = ?, period = ?, photo_path = ? WHERE id = ? AND officer_group_id = ?');

            foreach ($postedSlots as $postedId => $slotData) {
                $officerId = (int)$postedId;
                if ($officerId < 1 || !is_array($slotData)) {
                    throw new RuntimeException('Data salah satu jabatan tidak valid.');
                }

                $findSlot->execute([$officerId, $groupId]);
                $oldPhoto = $findSlot->fetchColumn();
                if ($oldPhoto === false) {
                    throw new RuntimeException('Jabatan tidak ditemukan di kelompok ini. Muat ulang halaman lalu coba lagi.');
                }

                $name = trim((string)($slotData['name'] ?? ''));
                $period = trim((string)($slotData['period'] ?? ''));
                $upload = [];
                foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $fileKey) {
                    $upload[$fileKey] = $photoUploads[$fileKey][$officerId] ?? ($fileKey === 'error' ? UPLOAD_ERR_NO_FILE : '');
                }
                $newPhoto = save_uploaded_image($upload, 'officers');
                if ($newPhoto !== null) $newPhotos[] = $newPhoto;
                $photoToSave = $newPhoto ?? (is_string($oldPhoto) ? $oldPhoto : null);
                $saveSlot->execute([$name, $period !== '' ? $period : null, $photoToSave, $officerId, $groupId]);

                if ($newPhoto !== null) {
                    if (is_string($oldPhoto) && $oldPhoto !== '') $oldPhotos[] = $oldPhoto;
                }
            }

            $pdo->commit();
            foreach ($oldPhotos as $oldPhoto) delete_officer_upload($oldPhoto);
            $_SESSION['admin_flash'] = 'Semua data jabatan dalam kelompok berhasil disimpan.';
            $_SESSION['admin_flash_type'] = 'success';
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            foreach ($newPhotos as $newPhoto) delete_officer_upload($newPhoto);
            $_SESSION['admin_flash'] = $exception->getMessage();
            $_SESSION['admin_flash_type'] = 'error';
        }
    }

    elseif ($action === 'save_officer_slot') {
        $groupId = (int)($_POST['group_id'] ?? 0);
        $officerId = (int)($_POST['officer_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $period = trim((string)($_POST['period'] ?? ''));
        $findSlot = $pdo->prepare('SELECT photo_path FROM officers WHERE id = ? AND officer_group_id = ? LIMIT 1');
        $findSlot->execute([$officerId, $groupId]);
        $oldPhoto = $findSlot->fetchColumn();
        if ($groupId > 0 && $officerId > 0 && $oldPhoto !== false) {
            try {
                $newPhoto = save_uploaded_image($_FILES['officer_photo'] ?? [], 'officers');
                if ($newPhoto !== null) {
                    $saveSlot = $pdo->prepare('UPDATE officers SET name = ?, period = ?, photo_path = ? WHERE id = ? AND officer_group_id = ?');
                    $saveSlot->execute([$name, $period !== '' ? $period : null, $newPhoto, $officerId, $groupId]);
                    delete_officer_upload(is_string($oldPhoto) ? $oldPhoto : null);
                } else {
                    $saveSlot = $pdo->prepare('UPDATE officers SET name = ?, period = ? WHERE id = ? AND officer_group_id = ?');
                    $saveSlot->execute([$name, $period !== '' ? $period : null, $officerId, $groupId]);
                }
                $_SESSION['admin_flash'] = $name === '' ? 'Jabatan disimpan kosong dan tidak akan tampil di publik.' : 'Data jabatan diperbarui.';
                $_SESSION['admin_flash_type'] = 'success';
            } catch (Throwable $exception) {
                $_SESSION['admin_flash'] = $exception->getMessage();
                $_SESSION['admin_flash_type'] = 'error';
            }
        }
    }

    elseif ($action === 'delete_officer_group') {
        $groupId = (int)($_POST['group_id'] ?? 0);
        if ($groupId > 0) {
            $findPhotos = $pdo->prepare('SELECT photo_path FROM officers WHERE officer_group_id = ?');
            $findPhotos->execute([$groupId]);
            foreach ($findPhotos->fetchAll(PDO::FETCH_COLUMN) as $photoPath) delete_officer_upload($photoPath);
            $pdo->prepare('DELETE FROM officers WHERE officer_group_id = ?')->execute([$groupId]);
            $pdo->prepare('DELETE FROM officer_groups WHERE id = ?')->execute([$groupId]);
            $_SESSION['admin_flash'] = 'Kelompok pengurus berhasil dihapus.';
            $_SESSION['admin_flash_type'] = 'success';
        }
    }


    /* =====================================================
       UPDATE STATUS PENDAFTARAN
       ===================================================== */

    if ($action === 'status') {

        $id = (int)($_POST['id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');

        if ($id < 1 || !in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $_SESSION['admin_flash'] = 'Status pendaftaran tidak valid. Silakan pilih ulang lalu tekan Update.';
            $_SESSION['admin_flash_type'] = 'error';
        } else {
            try {
                $pdo->beginTransaction();
                $selectRegistration = $pdo->prepare('SELECT * FROM registrations WHERE id = ? FOR UPDATE');
                $selectRegistration->execute([$id]);
                $registration = $selectRegistration->fetch(PDO::FETCH_ASSOC);

                if (!$registration) {
                    throw new DomainException('Data pendaftaran tidak ditemukan. Muat ulang halaman admin.');
                }

                $updateRegistration = $pdo->prepare('UPDATE registrations SET status = ? WHERE id = ?');
                $updateRegistration->execute([$status, $id]);

                // Jika disetujui, pastikan anggota terbentuk satu kali saja.
                if ($status === 'approved') {
                    $checkMember = $pdo->prepare('SELECT id FROM members WHERE registration_id = ? LIMIT 1');
                    $checkMember->execute([$id]);
                    $memberId = $checkMember->fetchColumn();
                    if (!$memberId) {
                        $insertMember = $pdo->prepare('INSERT INTO members
                            (registration_id, full_name, panggoaran, member_status, phone, email, city, father_name,
                             grandfather_name, status, husband_name, husband_status, wife_name,
                             wife_status, children_count, wilayah_id, daerah_id)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $insertMember->execute([
                            $id, $registration['full_name'], $registration['panggoaran'] ?? null, 'hidup', $registration['phone'],
                            $registration['email'], $registration['city'], $registration['father_name'],
                            $registration['grandfather_name'], 'active', $registration['husband_name'] ?? null,
                            $registration['husband_status'] ?? 'hidup', $registration['wife_name'] ?? null,
                            $registration['wife_status'] ?? 'hidup', $registration['children_count'] ?? 0,
                            $registration['wilayah_id'] ?? null, $registration['daerah_id'] ?? null,
                        ]);
                    } else {
                        $activateMember = $pdo->prepare("UPDATE members SET status = 'active', deleted_at = NULL WHERE id = ?");
                        $activateMember->execute([(int)$memberId]);
                    }
                } else {
                    $deactivateMember = $pdo->prepare("UPDATE members SET status = 'inactive' WHERE registration_id = ? AND deleted_at IS NULL");
                    $deactivateMember->execute([$id]);
                }

                $pdo->commit();
                $statusLabels = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
                $_SESSION['admin_flash'] = 'Status pendaftaran berhasil diperbarui menjadi ' . $statusLabels[$status] . '.';
                $_SESSION['admin_flash_type'] = 'success';
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Pembaruan status pendaftaran gagal: ' . $exception->getMessage());
                $_SESSION['admin_flash'] = $exception instanceof DomainException
                    ? $exception->getMessage()
                    : 'Status gagal diperbarui. Periksa log server atau coba lagi.';
                $_SESSION['admin_flash_type'] = 'error';
            }
        }
    }


    /* =====================================================
       UPDATE STATUS ANGGOTA
       ===================================================== */

    elseif ($action === 'update_member_status') {

        $member_id = (int)($_POST['member_id'] ?? 0);

        $member_status = $_POST['member_status'] ?? 'hidup';

        if (
            $member_id > 0 &&
            in_array(
                $member_status,
                ['hidup', 'meninggal'],
                true
            )
        ) {

            $s = $pdo->prepare("
                UPDATE members
                SET member_status = ?
                WHERE id = ?
            ");

            $s->execute([
                $member_status,
                $member_id
            ]);
        }
    }


    /* =====================================================
       HAPUS ANGGOTA
       ===================================================== */

    elseif ($action === 'delete_member') {

        $member_id = (int)($_POST['member_id'] ?? 0);

        if ($member_id > 0) {

            $s = $pdo->prepare("
                UPDATE members
                SET deleted_at = NOW()
                WHERE id = ?
                AND deleted_at IS NULL
            ");

            $s->execute([
                $member_id
            ]);

            $memberDeleted = $s->rowCount() > 0;
            $_SESSION['admin_flash'] = $memberDeleted
                ? 'Anggota berhasil dihapus dari daftar publik.'
                : 'Anggota tidak ditemukan atau sudah dihapus.';
            $_SESSION['admin_flash_type'] = $memberDeleted ? 'success' : 'error';
        }
    }


    /* =====================================================
       HAPUS PENDAFTARAN
       ===================================================== */

    elseif ($action === 'delete_registration') {

        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {

            $s = $pdo->prepare("
                DELETE FROM registrations
                WHERE id = ?
            ");

            $s->execute([$id]);
        }
    }


    /* =====================================================
       TAMBAH BERITA / SEJARAH
       ===================================================== */

    elseif ($action === 'add_post') {

        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $cat = $_POST['category'] ?? 'berita';

        if (
            $title !== '' &&
            $body !== '' &&
            in_array($cat, ['berita', 'sejarah'], true)
        ) {

            $image_path = null;

            if (
                isset($_FILES['post_image']) &&
                $_FILES['post_image']['error'] === UPLOAD_ERR_OK
            ) {

                $uploadDir = __DIR__ . '/uploads/posts/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $tmp = $_FILES['post_image']['tmp_name'];
                $original = $_FILES['post_image']['name'];

                $ext = strtolower(
                    pathinfo($original, PATHINFO_EXTENSION)
                );

                $allowed = [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];

                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
                $expectedMime = in_array($ext, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/' . $ext;
                if (
                    in_array($ext, $allowed, true) &&
                    $mime === $expectedMime &&
                    (int)($_FILES['post_image']['size'] ?? 0) <= 5 * 1024 * 1024 &&
                    getimagesize($tmp) !== false
                ) {

                    $filename =
                        time() .
                        '_' .
                        bin2hex(random_bytes(5)) .
                        '.' .
                        $ext;

                    $target = $uploadDir . $filename;

                    if (move_uploaded_file($tmp, $target)) {

                        $image_path =
                            'uploads/posts/' . $filename;
                    }
                }
            }

            $s = $pdo->prepare("
                INSERT INTO posts
                (
                    title,
                    body,
                    category,
                    image
                )
                VALUES (?, ?, ?, ?)
            ");

            $s->execute([
                $title,
                $body,
                $cat,
                $image_path
            ]);
        }
    }


    /* =====================================================
       HAPUS BERITA / SEJARAH
       ===================================================== */

    elseif ($action === 'delete_post') {

        $id = (int)($_POST['id'] ?? 0);

        $s = $pdo->prepare("
            SELECT image
            FROM posts
            WHERE id = ?
            LIMIT 1
        ");

        $s->execute([$id]);

        $post = $s->fetch(PDO::FETCH_ASSOC);

        if (
            $post &&
            !empty($post['image'])
        ) {

            $file =
                __DIR__ .
                '/' .
                $post['image'];

            if (file_exists($file)) {
                unlink($file);
            }
        }

        $s = $pdo->prepare("
            DELETE FROM posts
            WHERE id = ?
        ");

        $s->execute([$id]);
    }


    /* =====================================================
       TAMBAH PENGURUS
       ===================================================== */

    elseif ($action === 'add_officer') {

        $name = trim($_POST['name'] ?? '');
        $pos = trim($_POST['position_name'] ?? '');
        $period = trim($_POST['period'] ?? '');

        $level = $_POST['level'] ?? 'pusat';
        $region = trim($_POST['region'] ?? '');

        if (
            !in_array(
                $level,
                ['pusat', 'regional', 'daerah'],
                true
            )
        ) {
            $level = 'pusat';
        }

        if ($level === 'pusat') {
            $region = null;
        }

        if (
            $name !== '' &&
            $pos !== '' &&
            ($level === 'pusat' || $region !== '')
        ) {

            $s = $pdo->prepare("
                INSERT INTO officers
                (
                    name,
                    position_name,
                    period,
                    level,
                    region
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $s->execute([
                $name,
                $pos,
                $period,
                $level,
                $region
            ]);
        }
    }


    /* =====================================================
       EDIT PENGURUS
       ===================================================== */

    elseif ($action === 'update_officer') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $pos = trim($_POST['position_name'] ?? '');
        $period = trim($_POST['period'] ?? '');
        $level = $_POST['level'] ?? 'pusat';
        $region = trim($_POST['region'] ?? '');

        if (!in_array($level, ['pusat', 'regional', 'daerah'], true)) {
            $level = 'pusat';
        }
        if ($level === 'pusat') {
            $region = null;
        }

        if ($id > 0 && $name !== '' && $pos !== '' && ($level === 'pusat' || $region !== '')) {
            $newPhotoPath = null;
            $upload = $_FILES['officer_photo'] ?? null;
            if ($upload && $upload['error'] === UPLOAD_ERR_OK && $upload['size'] <= 5 * 1024 * 1024) {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
                $photoTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];
                if (isset($photoTypes[$mime]) && getimagesize($upload['tmp_name']) !== false) {
                    $uploadDir = __DIR__ . '/uploads/officers/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0775, true);
                    }
                    $filename = bin2hex(random_bytes(16)) . '.' . $photoTypes[$mime];
                    if (move_uploaded_file($upload['tmp_name'], $uploadDir . $filename)) {
                        $newPhotoPath = 'uploads/officers/' . $filename;
                    }
                }
            }

            if ($upload && $upload['error'] !== UPLOAD_ERR_NO_FILE && $newPhotoPath === null) {
                $_SESSION['admin_flash'] = 'Foto tidak tersimpan. Gunakan JPG, PNG, atau WEBP dengan ukuran maksimal 5 MB.';
                $_SESSION['admin_flash_type'] = 'error';
            }

            if (!$upload || $upload['error'] === UPLOAD_ERR_NO_FILE || $newPhotoPath !== null) {
                $oldPhotoQuery = $pdo->prepare('SELECT photo_path FROM officers WHERE id = ?');
                $oldPhotoQuery->execute([$id]);
                $oldPhotoPath = $oldPhotoQuery->fetchColumn();

                if ($newPhotoPath !== null) {
                    $s = $pdo->prepare("UPDATE officers SET name = ?, position_name = ?, period = ?, level = ?, region = ?, photo_path = ? WHERE id = ?");
                    $s->execute([$name, $pos, $period, $level, $region, $newPhotoPath, $id]);
                    if (is_string($oldPhotoPath) && str_starts_with($oldPhotoPath, 'uploads/officers/')) {
                        $oldPhotoFile = __DIR__ . '/uploads/officers/' . basename($oldPhotoPath);
                        if (is_file($oldPhotoFile)) {
                            unlink($oldPhotoFile);
                        }
                    }
                } else {
                    $s = $pdo->prepare("UPDATE officers SET name = ?, position_name = ?, period = ?, level = ?, region = ? WHERE id = ?");
                    $s->execute([$name, $pos, $period, $level, $region, $id]);
                }
            }
        }
    }


    /* =====================================================
       HAPUS PENGURUS
       ===================================================== */

    elseif ($action === 'delete_officer') {

        $s = $pdo->prepare("
            DELETE FROM officers
            WHERE id = ?
        ");

        $s->execute([
            (int)($_POST['id'] ?? 0)
        ]);
    }


    /* =====================================================
       TAMBAH GALERI
       ===================================================== */

    elseif ($action === 'add_gallery') {

        $title = trim($_POST['title'] ?? '');

        $type = $_POST['type'] ?? 'foto';

        $category = $_POST['category'] ?? 'album';

        $album_name = trim(
            $_POST['album_name'] ?? ''
        );

        $video_url = trim(
            $_POST['video_url'] ?? ''
        );

        $description = trim(
            $_POST['description'] ?? ''
        );

        if (
            !in_array(
                $type,
                ['foto', 'video'],
                true
            )
        ) {
            $type = 'foto';
        }


        /* =================================================
           FOTO
           ================================================= */

        if ($type === 'foto') {

            if (
                !in_array(
                    $category,
                    ['beranda', 'album'],
                    true
                )
            ) {
                $category = 'album';
            }

            if ($category === 'beranda') {
                $album_name = null;
            }

            if (
                $title !== '' &&
                isset($_FILES['image']) &&
                $_FILES['image']['error'] === UPLOAD_ERR_OK
            ) {

                $uploadDir =
                    __DIR__ . '/uploads/gallery/';

                if (!is_dir($uploadDir)) {
                    mkdir(
                        $uploadDir,
                        0775,
                        true
                    );
                }

                $tmp =
                    $_FILES['image']['tmp_name'];

                $original =
                    $_FILES['image']['name'];

                $ext = strtolower(
                    pathinfo(
                        $original,
                        PATHINFO_EXTENSION
                    )
                );

                $allowed = [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];

                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
                $expectedMime = in_array($ext, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/' . $ext;
                if (
                    in_array(
                        $ext,
                        $allowed,
                        true
                    )
                    && $mime === $expectedMime
                    && (int)($_FILES['image']['size'] ?? 0) <= 5 * 1024 * 1024
                    && getimagesize($tmp) !== false
                ) {

                    $filename =
                        time() .
                        '_' .
                        bin2hex(
                            random_bytes(5)
                        ) .
                        '.' .
                        $ext;

                    $target =
                        $uploadDir .
                        $filename;

                    if (
                        move_uploaded_file(
                            $tmp,
                            $target
                        )
                    ) {

                        $image_path =
                            'uploads/gallery/' .
                            $filename;

                        $s = $pdo->prepare("
                            INSERT INTO gallery
                            (
                                title,
                                image_path,
                                type,
                                category,
                                album_name,
                                description
                            )
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");

                        $s->execute([
                            $title,
                            $image_path,
                            'foto',
                            $category,
                            $album_name,
                            $description
                        ]);
                    }
                }
            }
        }


        /* =================================================
           VIDEO
           ================================================= */

        elseif ($type === 'video') {

            if (
                $title !== '' &&
                $video_url !== ''
            ) {

                $s = $pdo->prepare("
                    INSERT INTO gallery
                    (
                        title,
                        type,
                        category,
                        video_url,
                        description
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $s->execute([
                    $title,
                    'video',
                    'album',
                    $video_url,
                    $description
                ]);
            }
        }
    }


    /* =====================================================
       HAPUS GALERI
       ===================================================== */

    elseif ($action === 'delete_gallery') {

        $id = (int)(
            $_POST['id'] ?? 0
        );

        $s = $pdo->prepare("
            SELECT image_path
            FROM gallery
            WHERE id = ?
        ");

        $s->execute([$id]);

        $galleryItem =
            $s->fetch(PDO::FETCH_ASSOC);

        if (
            $galleryItem &&
            !empty($galleryItem['image_path'])
        ) {

            $file =
                __DIR__ .
                '/' .
                $galleryItem['image_path'];

            if (file_exists($file)) {
                unlink($file);
            }
        }

        $s = $pdo->prepare("
            DELETE FROM gallery
            WHERE id = ?
        ");

        $s->execute([$id]);
    }


    /* =====================================================
       KEMBALI KE DASHBOARD
       ===================================================== */

    header('Location: admin.php');
    exit;
}


/* =========================================================
   CSRF
   ========================================================= */

if (empty($_SESSION['csrf'])) {

    $_SESSION['csrf'] =
        bin2hex(
            random_bytes(32)
        );
}


/* =========================================================
   AMBIL DATA PENDAFTARAN
   ========================================================= */

$regs = $pdo->query("
    SELECT *
    FROM registrations
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);


/* Jumlah pendaftar yang masih menunggu */

$pendingCount = $pdo->query("
    SELECT COUNT(*)
    FROM registrations
    WHERE status = 'pending'
")->fetchColumn();


/* =========================================================
   AMBIL DATA ANGGOTA
   ========================================================= */

$members = $pdo->query("
    SELECT
        m.*,
        w.nama AS wilayah_nama,
        d.nama AS daerah_nama
    FROM members m
    LEFT JOIN wilayah w
        ON w.id = m.wilayah_id
    LEFT JOIN daerah d
        ON d.id = m.daerah_id
    WHERE m.deleted_at IS NULL
    ORDER BY m.full_name ASC
")->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   AMBIL DATA BERITA / SEJARAH
   ========================================================= */

$posts = $pdo->query("
    SELECT *
    FROM posts
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   AMBIL DATA PENGURUS
   ========================================================= */

$officerSearch = trim((string)($_GET['officer_q'] ?? ''));
$officerLevel = (string)($_GET['officer_level'] ?? '');
$officerRegion = trim((string)($_GET['officer_region'] ?? ''));
$officerFilterSubmitted = isset($_GET['officer_filter']);
$officerWhere = [];
$officerParams = [];
if ($officerSearch !== '') {
    $officerWhere[] = '(name LIKE ? OR region LIKE ?)';
    $term = '%' . $officerSearch . '%';
    array_push($officerParams, $term, $term);
}
if (in_array($officerLevel, ['pusat', 'regional', 'daerah'], true)) {
    $officerWhere[] = 'level = ?';
    $officerParams[] = $officerLevel;
}
if ($officerRegion !== '') {
    $officerWhere[] = 'name = ?';
    $officerParams[] = $officerRegion;
}
$officerSql = 'SELECT * FROM officer_groups';
if ($officerWhere) {
    $officerSql .= ' WHERE ' . implode(' AND ', $officerWhere);
}
$officerSql .= " ORDER BY CASE level WHEN 'pusat' THEN 0 WHEN 'regional' THEN 1 ELSE 2 END, name ASC, id ASC";
$officerGroups = [];
if ($officerFilterSubmitted) {
    $officerStmt = $pdo->prepare($officerSql);
    $officerStmt->execute($officerParams);
    $officerGroups = $officerStmt->fetchAll(PDO::FETCH_ASSOC);
}
$officerRegions = $pdo->query("SELECT name, level FROM officer_groups WHERE name <> '' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$groupMembers = [];
if ($officerGroups) {
    $groupIds = array_map(static fn($group) => (int)$group['id'], $officerGroups);
    $groupPlaceholders = implode(',', array_fill(0, count($groupIds), '?'));
    $memberQuery = $pdo->prepare("SELECT * FROM officers WHERE officer_group_id IN ($groupPlaceholders) ORDER BY CASE WHEN position_name = 'Penasehat' THEN 0 WHEN position_name LIKE '%Ketua%' AND position_name NOT LIKE '%Wakil%' THEN 1 WHEN position_name LIKE '%Wakil Ketua%' THEN 2 WHEN position_name = 'Sekretaris' THEN 3 WHEN position_name LIKE '%Wakil Sekretaris%' THEN 4 WHEN position_name = 'Bendahara' THEN 5 WHEN position_name LIKE '%Wakil Bendahara%' THEN 6 ELSE 7 END, slot_number ASC, id ASC");
    $memberQuery->execute($groupIds);
    foreach ($memberQuery->fetchAll(PDO::FETCH_ASSOC) as $officerMember) {
        $groupMembers[(int)$officerMember['officer_group_id']][] = $officerMember;
    }
}


/* =========================================================
   AMBIL DATA GALERI
   ========================================================= */

$gallery = $pdo->query("
    SELECT *
    FROM gallery
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$adminAccountStmt = $pdo->prepare('SELECT email, email_verified_at FROM admins WHERE id = ? LIMIT 1');
$adminAccountStmt->execute([(int)($_SESSION['admin_id'] ?? 0)]);
$adminAccount = $adminAccountStmt->fetch(PDO::FETCH_ASSOC) ?: ['email' => null, 'email_verified_at' => null];


$pageTitle = 'Dashboard Admin';

require 'header.php';

?>

<h1>Dashboard Admin</h1>

<?php if (!empty($_SESSION['admin_flash'])): ?>
    <div class="notice <?= ($_SESSION['admin_flash_type'] ?? '') === 'error' ? 'danger' : '' ?>"><?= e($_SESSION['admin_flash']) ?></div>
    <?php unset($_SESSION['admin_flash'], $_SESSION['admin_flash_type']); ?>
<?php endif; ?>

<p>
    Selamat datang,
    <?= e($_SESSION['admin_name'] ?? 'Admin') ?>.
</p>

<h2>Email Pemulihan Akun Admin</h2>
<div class="card" style="max-width:760px">
    <p>
        <?php if (!empty($adminAccount['email_verified_at'])): ?>
            Email terverifikasi: <strong><?= e($adminAccount['email']) ?></strong>
        <?php elseif (!empty($adminAccount['email'])): ?>
            Email <strong><?= e($adminAccount['email']) ?></strong> belum diverifikasi.
        <?php else: ?>
            Belum ada email pemulihan yang terdaftar.
        <?php endif; ?>
    </p>
    <p class="muted">Masukkan email yang dapat Anda akses. Tautan verifikasi akan dikirim ke email tersebut.</p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <input type="hidden" name="action" value="set_admin_recovery_email">
        <label for="adminRecoveryEmail">Email pemulihan</label>
        <input id="adminRecoveryEmail" type="email" name="email" maxlength="190" value="<?= e($adminAccount['email']) ?>" autocomplete="email" required>
        <button type="submit">Kirim tautan verifikasi</button>
    </form>
</div>

<!-- =====================================================
     NOTIFIKASI PENDAFTARAN
     ===================================================== -->

<?php if ($pendingCount > 0): ?>

<div style="
    background:#fff3cd;
    border:1px solid #ffeeba;
    color:#856404;
    padding:18px 20px;
    border-radius:10px;
    margin:20px 0;
">

<strong style="font-size:18px;">
    🔔 Pendaftaran Baru
</strong>

<p style="margin:8px 0 12px;">
    Ada
    <strong><?= (int)$pendingCount ?></strong>
    pendaftar yang masih menunggu pemeriksaan.
</p>

<a
    href="#pendaftaran"
    class="btn"
>
    👁️ Lihat Pendaftaran
</a>

</div>

<?php else: ?>

<div style="
    background:#d4edda;
    border:1px solid #c3e6cb;
    color:#155724;
    padding:15px 20px;
    border-radius:10px;
    margin:20px 0;
">

✅ Tidak ada pendaftaran baru yang menunggu.

</div>

<?php endif; ?>


<!-- =====================================================
     MENU DASHBOARD
     ===================================================== -->

<div class="actions">

<a
    class="btn"
    href="index.php"
>
    🌐 Lihat Website
</a>

<a
    class="btn"
    href="#pendaftaran"
    style="position:relative;"
>

    👥 Pendaftaran

    <?php if ($pendingCount > 0): ?>

    <span style="
        background:#dc3545;
        color:white;
        border-radius:50%;
        min-width:22px;
        height:22px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        font-size:12px;
        font-weight:bold;
        margin-left:5px;
        padding:0 5px;
    ">
        <?= (int)$pendingCount ?>
    </span>

    <?php endif; ?>

</a>

<a
    class="btn"
    href="#anggota"
>
    👨‍👩‍👧‍👦 Data Anggota
</a>

<a
    class="btn"
    href="logout.php"
>
    🚪 Logout
</a>

</div>


<!-- =====================================================
     PENDAFTARAN
     ===================================================== -->

<h2 id="pendaftaran">
    Pendaftaran Anggota
    (<?= count($regs) ?>)
</h2>

<p class="muted">
    Jangan bagikan data pribadi anggota kepada publik.
</p>

<div class="card" style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
    <a class="btn" href="export-anggota.php">Unduh Template / Export Excel (.xlsx)</a>
    <form method="post" action="import-anggota.php" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <label for="excelAnggota">Import file Excel (.xlsx, maks. 10 MB)</label>
        <input id="excelAnggota" type="file" name="excel_file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
        <button type="submit">Import Anggota</button>
    </form>
</div>

<div style="overflow-x:auto">

<table>

<thead>

<tr>

<th>Nama / Kontak</th>

<th>Data Keluarga</th>

<th>Status</th>

<th>Ubah Status</th>

</tr>

</thead>

<tbody>

<?php foreach ($regs as $r): ?>

<tr>

<td>

<strong>
    <?= e($r['full_name']) ?>
</strong>

<br>

<?= e($r['phone']) ?>

<br>

<?= e($r['email']) ?>

<br>

<?= e($r['city']) ?>

<br>

<span class="muted">
    <?= e($r['created_at']) ?>
</span>

<br><br>

<a
    href="detail-pendaftar.php?id=<?= (int)$r['id'] ?>"
    class="btn"
>
    👁️ Lihat Detail
</a>

<br><br>

<form
    method="post"
    style="display:inline;"
    onsubmit="return confirm('Yakin ingin menghapus pendaftar ini? Data yang sudah dihapus tidak dapat dikembalikan.')"
>

    <input
        type="hidden"
        name="csrf"
        value="<?= e($_SESSION['csrf']) ?>"
    >

    <input
        type="hidden"
        name="action"
        value="delete_registration"
    >

    <input
        type="hidden"
        name="id"
        value="<?= (int)$r['id'] ?>"
    >

    <button
        type="submit"
        style="
            background:#dc3545;
            color:white;
            border:0;
            padding:9px 12px;
            border-radius:6px;
            cursor:pointer;
        "
    >
        &#128465; Hapus
    </button>

</form>

</td>

<td>

<strong>Nama Panggoaran:</strong><br>
<?= e($r['panggoaran'] ?? '-') ?>

<br><br>

<strong>Suami:</strong><br>

<?php

$husbandName = trim(
    $r['husband_name'] ?? ''
);

if ($husbandName !== '') {

    if (
        ($r['husband_status'] ?? 'hidup')
        === 'meninggal'
    ) {
        echo '† ';
    }

    echo e($husbandName);

} else {

    echo '-';
}

?>

<br><br>

<strong>Istri:</strong><br>

<?php

$wifeName = trim(
    $r['wife_name'] ?? ''
);

if ($wifeName !== '') {

    if (
        ($r['wife_status'] ?? 'hidup')
        === 'meninggal'
    ) {
        echo '† ';
    }

    echo e($wifeName);

} else {

    echo '-';
}

?>

<br><br>

<strong>Jumlah Anak:</strong>

<?= (int)($r['children_count'] ?? 0) ?>

orang

<br><br>

<?php if (!empty($r['notes'])): ?>

    <strong>Catatan:</strong><br>

    <?= nl2br(e($r['notes'])) ?>

<?php endif; ?>

</td>

<td>

<?php if ($r['status'] === 'pending'): ?>

<span style="
    background:#fff3cd;
    color:#856404;
    padding:6px 10px;
    border-radius:20px;
    font-size:13px;
    font-weight:bold;
">
    🟡 Menunggu
</span>

<?php elseif ($r['status'] === 'approved'): ?>

<span style="
    background:#d4edda;
    color:#155724;
    padding:6px 10px;
    border-radius:20px;
    font-size:13px;
    font-weight:bold;
">
    🟢 Disetujui
</span>

<?php elseif ($r['status'] === 'rejected'): ?>

<span style="
    background:#f8d7da;
    color:#721c24;
    padding:6px 10px;
    border-radius:20px;
    font-size:13px;
    font-weight:bold;
">
    🔴 Ditolak
</span>

<?php endif; ?>

</td>

<td>

<form method="post">

<input
    type="hidden"
    name="csrf"
    value="<?= e($_SESSION['csrf']) ?>"
>

<input
    type="hidden"
    name="action"
    value="status"
>

<input
    type="hidden"
    name="id"
    value="<?= (int)$r['id'] ?>"
>

<select name="status">

    <option
        value="pending"
        <?= $r['status'] === 'pending' ? 'selected' : '' ?>
    >
        Menunggu
    </option>

    <option
        value="approved"
        <?= $r['status'] === 'approved' ? 'selected' : '' ?>
    >
        Disetujui
    </option>

    <option
        value="rejected"
        <?= $r['status'] === 'rejected' ? 'selected' : '' ?>
    >
        Ditolak
    </option>

</select>

<button type="submit">
    Update
</button>

</form>

</td>

</tr>

<?php endforeach; ?>

<?php if (!$regs): ?>

<tr>

<td colspan="4">
    Belum ada pendaftaran.
</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>


<!-- =====================================================
     DATA ANGGOTA
     ===================================================== -->

<h2 id="anggota">
    👨‍👩‍👧‍👦 Data Anggota
    (<?= count($members) ?>)
</h2>

<p class="muted">
    Data ini hanya untuk pengelolaan admin.
    Data pribadi tidak ditampilkan di website publik.
</p>

<div style="overflow-x:auto">

<table>

<thead>

<tr>

<th>No.</th>

<th>Suami</th>

<th>Istri</th>

<th>Jumlah Anak</th>

<th>Wilayah</th>

<th>Status Anggota</th>

<th>Detail</th>

<th>Ubah Status</th>

<th>Aksi</th>

</tr>

</thead>

<tbody>

<?php if (!$members): ?>

<tr>

<td colspan="9">
    Belum ada anggota.
</td>

</tr>

<?php else: ?>

<?php $memberNo = 1; ?>

<?php foreach ($members as $member): ?>

<?php

/*
=========================================================
DATA SUAMI
=========================================================
*/

$memberStatus =
    $member['member_status'] ?? 'hidup';

$husbandName =
    trim($member['husband_name'] ?? '');

$wifeName =
    trim($member['wife_name'] ?? '');

$husbandStatus =
    $member['husband_status'] ?? 'hidup';

$wifeStatus =
    $member['wife_status'] ?? 'hidup';

?>

<tr>

<!-- NO -->

<td>
    <?= $memberNo++ ?>
</td>


<!-- SUAMI -->

<td>

    <?php if ($husbandName !== ''): ?>

        <?php if ($husbandStatus === 'meninggal'): ?>

            †

        <?php endif; ?>

        <strong>
            <?= e($husbandName) ?>
        </strong>

    <?php else: ?>

        -

    <?php endif; ?>

</td>


<!-- ISTRI -->

<td>

    <?php if ($wifeName !== ''): ?>

        <?php if ($wifeStatus === 'meninggal'): ?>

            †

        <?php endif; ?>

        <?= e($wifeName) ?>

    <?php else: ?>

        -

    <?php endif; ?>

</td>


<!-- JUMLAH ANAK -->

<td>

    <?= (int)(
        $member['children_count'] ?? 0
    ) ?>

</td>


<!-- WILAYAH -->

<td>

    <?php if (!empty($member['wilayah_nama'])): ?>

        <?= e($member['wilayah_nama']) ?>

        <?php if (!empty($member['daerah_nama'])): ?>

            <br>

            <small>
                <?= e($member['daerah_nama']) ?>
            </small>

        <?php endif; ?>

    <?php else: ?>

        -

    <?php endif; ?>

</td>


<!-- STATUS ANGGOTA -->

<td>

    <?php if ($memberStatus === 'meninggal'): ?>

        <span style="
            background:#f8d7da;
            color:#721c24;
            padding:6px 10px;
            border-radius:20px;
            font-size:13px;
            font-weight:bold;
        ">
            † Meninggal
        </span>

    <?php else: ?>

        <span style="
            background:#d4edda;
            color:#155724;
            padding:6px 10px;
            border-radius:20px;
            font-size:13px;
            font-weight:bold;
        ">
            🟢 Hidup
        </span>

    <?php endif; ?>

</td>


<!-- DETAIL -->

<td>

<a
    href="detail-anggota.php?id=<?= (int)$member['id'] ?>"
    class="btn"
    style="
        display:inline-block;
        margin-bottom:8px;
        text-decoration:none;
    "
>
    👁️ Detail
</a>

</td>


<!-- UBAH STATUS -->

<td>

<form method="post">

    <input
        type="hidden"
        name="csrf"
        value="<?= e($_SESSION['csrf']) ?>"
    >

    <input
        type="hidden"
        name="action"
        value="update_member_status"
    >

    <input
        type="hidden"
        name="member_id"
        value="<?= (int)$member['id'] ?>"
    >

    <select name="member_status">

        <option
            value="hidup"
            <?= $memberStatus === 'hidup' ? 'selected' : '' ?>
        >
            Hidup
        </option>

        <option
            value="meninggal"
            <?= $memberStatus === 'meninggal' ? 'selected' : '' ?>
        >
            Meninggal
        </option>

    </select>

    <button type="submit">
        Update
    </button>

</form>

</td>

<!-- HAPUS ANGGOTA -->

<td>

<form
    method="post"
    onsubmit="return confirm('Hapus anggota ini dari daftar anggota dan laman publik?')"
>

    <input
        type="hidden"
        name="csrf"
        value="<?= e($_SESSION['csrf']) ?>"
    >

    <input
        type="hidden"
        name="action"
        value="delete_member"
    >

    <input
        type="hidden"
        name="member_id"
        value="<?= (int)$member['id'] ?>"
    >

    <button
        type="submit"
        style="background:#dc3545;color:white;border:0;padding:9px 12px;border-radius:6px;cursor:pointer"
    >
        Hapus
    </button>

</form>

</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>


<!-- =====================================================
     BERITA / SEJARAH
     ===================================================== -->

<h2>
    Tambah Berita atau Sejarah
</h2>

<form
    class="card"
    method="post"
    enctype="multipart/form-data"
>

<input
    type="hidden"
    name="csrf"
    value="<?= e($_SESSION['csrf']) ?>"
>

<input
    type="hidden"
    name="action"
    value="add_post"
>

<label>
    Jenis
</label>

<select name="category">

    <option value="berita">
        Berita
    </option>

    <option value="sejarah">
        Sejarah
    </option>

</select>


<label>
    Judul
</label>

<input
    name="title"
    maxlength="200"
    required
>


<label>
    Isi
</label>

<textarea
    name="body"
    required
></textarea>


<label>
    Foto Berita
</label>

<input
    type="file"
    name="post_image"
    accept=".jpg,.jpeg,.png,.webp"
>


<p class="muted">
    Format foto: JPG, JPEG, PNG atau WEBP.
</p>

<br>

<button type="submit">
    📰 Simpan Tulisan
</button>

</form>


<h2>
    Daftar Tulisan
</h2>

<div class="card">

<?php foreach ($posts as $p): ?>

<div style="
    padding:15px 0;
    border-bottom:1px solid #eee;
">

<?php if (!empty($p['image'])): ?>

    <img
        src="<?= e($p['image']) ?>"
        alt="<?= e($p['title']) ?>"
        style="
            width:180px;
            height:100px;
            object-fit:cover;
            border-radius:8px;
            display:block;
            margin-bottom:10px;
        "
    >

<?php endif; ?>

<strong>
    <?= e($p['title']) ?>
</strong>

<span class="muted">
    (<?= e($p['category']) ?>)
</span>

<br><br>

<form
    method="post"
    style="display:inline"
>

    <input
        type="hidden"
        name="csrf"
        value="<?= e($_SESSION['csrf']) ?>"
    >

    <input
        type="hidden"
        name="action"
        value="delete_post"
    >

    <input
        type="hidden"
        name="id"
        value="<?= (int)$p['id'] ?>"
    >

    <button
        type="submit"
        onclick="return confirm('Hapus tulisan ini?')"
    >
        &#128465; Hapus
    </button>

</form>

</div>

<?php endforeach; ?>

<?php if (!$posts): ?>

<p>
    Belum ada tulisan.
</p>

<?php endif; ?>

</div>


<!-- =====================================================
     PENGURUS
     ===================================================== -->

<h2>Tambah Kelompok Pengurus</h2>
<div class="notice" style="margin-bottom:12px">
    Buat kelompok terlebih dahulu, misalnya <strong>Pengurus Jabodetabek</strong>.
    Setelah disimpan, isi nama dan foto orang pada jabatan yang tersedia. Jabatan yang dibiarkan kosong tidak tampil di laman publik.
</div>

<form class="card" method="post">
    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
    <input type="hidden" name="action" value="add_officer_group">

    <label>Nama kelompok</label>
    <input name="name" placeholder="Contoh: Pengurus Jabodetabek" required>

    <label>Tingkat Pengurus</label>
    <select name="level" id="levelPengurus" required>
        <option value="pusat">Pengurus Pusat</option>
        <option value="regional">Pengurus Regional</option>
        <option value="daerah">Pengurus Daerah</option>
    </select>

    <label>Periode</label>
    <input name="period" placeholder="Contoh: 2026-2030">
    <small class="muted">Setelah menekan Simpan Kelompok, formulir jabatan akan muncul di bawah.</small>
    <br><br>
    <button type="submit">Simpan Kelompok</button>
</form>

<h2>Data Kelompok Pengurus</h2>
<div class="card">
    <form method="get" class="officer-filter-form">
        <input type="hidden" name="officer_filter" value="1">
        <label style="flex:1;min-width:180px">Cari nama kelompok
            <input type="search" name="officer_q" value="<?= e($officerSearch) ?>" placeholder="Contoh: Jabodetabek">
        </label>
        <label>Tingkat
            <select name="officer_level" id="officerLevelFilter">
                <option value="">Semua tingkat</option>
                <option value="pusat" <?= $officerLevel === 'pusat' ? 'selected' : '' ?>>Pengurus Pusat</option>
                <option value="regional" <?= $officerLevel === 'regional' ? 'selected' : '' ?>>Pengurus Regional</option>
                <option value="daerah" <?= $officerLevel === 'daerah' ? 'selected' : '' ?>>Pengurus Daerah</option>
            </select>
        </label>
        <label>Kelompok
            <select name="officer_region" id="officerGroupFilter">
                <option value="">Semua kelompok</option>
                <?php foreach ($officerRegions as $regionOption): ?>
                    <option value="<?= e($regionOption['name']) ?>" data-level="<?= e($regionOption['level']) ?>" <?= $officerRegion === $regionOption['name'] ? 'selected' : '' ?>><?= e($regionOption['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="officer-filter-actions">
            <button type="submit" class="officer-filter-search">Cari</button>
            <a href="admin.php" class="officer-filter-reset">Reset</a>
        </div>
    </form>

    <?php if ($officerFilterSubmitted): ?>
        <p>Menampilkan <?= count($officerGroups) ?> kelompok.</p>
    <?php endif; ?>

    <?php if (!$officerFilterSubmitted): ?>
        <p class="muted">Masukkan nama kelompok atau pilih filter, lalu tekan <strong>Cari</strong> untuk menampilkan data.</p>
    <?php elseif (!$officerGroups): ?>
        <p>Tidak ada kelompok yang cocok dengan pencarian atau filter.</p>
    <?php endif; ?>

    <?php foreach ($officerGroups as $group): ?>
        <?php $groupId = (int)$group['id']; ?>
        <section class="card" style="margin:18px 0;padding:18px">
            <h3><?= e($group['name']) ?></h3>
            <p class="muted">
                <?= $group['level'] === 'pusat' ? 'Pengurus Pusat' : ($group['level'] === 'regional' ? 'Pengurus Regional' : 'Pengurus Daerah') ?>
                <?= !empty($group['period']) ? ' · Periode ' . e($group['period']) : '' ?>
            </p>

            <details>
                <summary>Edit nama, tingkat, atau periode kelompok</summary>
                <form method="post" class="card">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                    <input type="hidden" name="action" value="update_officer_group">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <label>Nama kelompok</label>
                    <input name="name" value="<?= e($group['name']) ?>" required>
                    <label>Tingkat Pengurus</label>
                    <select name="level">
                        <option value="pusat" <?= $group['level'] === 'pusat' ? 'selected' : '' ?>>Pengurus Pusat</option>
                        <option value="regional" <?= $group['level'] === 'regional' ? 'selected' : '' ?>>Pengurus Regional</option>
                        <option value="daerah" <?= $group['level'] === 'daerah' ? 'selected' : '' ?>>Pengurus Daerah</option>
                    </select>
                    <label>Periode</label>
                    <input name="period" value="<?= e($group['period'] ?? '') ?>" placeholder="Contoh: 2026-2030">
                    <button type="submit">Simpan Perubahan Kelompok</button>
                </form>
            </details>

            <form method="post" style="margin:10px 0" onsubmit="return confirm('Hapus seluruh kelompok dan data jabatan di dalamnya?')">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="delete_officer_group">
                <input type="hidden" name="group_id" value="<?= $groupId ?>">
                <button type="submit" style="background:#dc3545;color:#fff">Hapus Kelompok</button>
            </form>

            <h4>Susunan Jabatan</h4>
            <form method="post" enctype="multipart/form-data" class="officer-slots-form">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="save_officer_group_slots">
                <input type="hidden" name="group_id" value="<?= $groupId ?>">
                <p class="muted">Edit data pada jabatan yang diperlukan, lalu simpan semuanya sekaligus.</p>
            <?php foreach ($groupMembers[$groupId] ?? [] as $person): ?>
                <?php $isLegacySlot = ($person['slot_key'] ?? '') === 'legacy'; ?>
                <div class="card officer-slot-card">
                    <strong><?= e($person['position_name']) ?><?= $isLegacySlot ? ' (data sebelumnya)' : '' ?><?= !$isLegacySlot && (($person['slot_key'] ?? '') === 'penasehat' || (int)$person['slot_number'] > 1) ? ' ' . (int)$person['slot_number'] : '' ?></strong>
                    <label>Nama</label>
                    <input name="slots[<?= (int)$person['id'] ?>][name]" value="<?= e($person['name']) ?>" placeholder="Kosongkan jika jabatan ini belum diisi">
                    <label>Periode (opsional)</label>
                    <input name="slots[<?= (int)$person['id'] ?>][period]" value="<?= e($person['period'] ?? '') ?>" placeholder="<?= e($group['period'] ?? 'Contoh: 2026-2030') ?>">
                    <?php if (!empty($person['photo_path'])): ?>
                        <p>Foto saat ini:</p>
                        <img src="<?= e($person['photo_path']) ?>" alt="Foto <?= e($person['name']) ?>" style="width:72px;height:72px;object-fit:cover;border-radius:50%">
                    <?php endif; ?>
                    <label>Foto (opsional, JPG/PNG/WEBP, maks. 5 MB)</label>
                    <input type="file" name="officer_photos[<?= (int)$person['id'] ?>]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                </div>
            <?php endforeach; ?>
                <button type="submit">Simpan Semua Perubahan</button>
            </form>
        </section>
    <?php endforeach; ?>
</div>

<style>
.officer-filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: end;
    margin-bottom: 16px;
}
.officer-filter-form > label {
    margin: 0;
}
.officer-filter-form > label:first-of-type {
    flex: 1 1 220px;
}
.officer-filter-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 1px;
}
.officer-filter-search,
.officer-filter-reset {
    box-sizing: border-box;
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    justify-content: center;
    padding: 0 16px;
    border: 1px solid #d9a441;
    border-radius: 6px;
    font: inherit;
    font-weight: 600;
    line-height: 1;
    text-decoration: none;
    cursor: pointer;
}
.officer-filter-search {
    background: #d9a441;
    color: #17324d;
}
.officer-filter-reset {
    background: #fff;
    color: #17324d;
}
.officer-filter-search:hover,
.officer-filter-reset:hover {
    filter: brightness(.96);
}
@media (max-width: 640px) {
    .officer-filter-form > label,
    .officer-filter-form > label:first-of-type {
        flex: 1 1 100%;
    }
    .officer-filter-actions {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const levelSelect = document.getElementById('officerLevelFilter');
    const groupSelect = document.getElementById('officerGroupFilter');
    if (!levelSelect || !groupSelect) return;

    function filterOfficerGroups(resetInvalidSelection) {
        const selectedLevel = levelSelect.value;
        let selectedIsVisible = false;

        Array.from(groupSelect.options).forEach(function (option, index) {
            if (index === 0) {
                option.hidden = false;
                return;
            }
            const visible = selectedLevel === '' || option.dataset.level === selectedLevel;
            option.hidden = !visible;
            if (visible && option.selected) selectedIsVisible = true;
        });

        if (resetInvalidSelection && !selectedIsVisible) groupSelect.value = '';
    }

    levelSelect.addEventListener('change', function () {
        filterOfficerGroups(true);
    });
    filterOfficerGroups(false);
});
</script>
<!-- =====================================================
     GALERI
     ===================================================== -->

<h2>
    🖼️ Kelola Galeri
</h2>

<form
    class="card"
    method="post"
    enctype="multipart/form-data"
>

<input
    type="hidden"
    name="csrf"
    value="<?= e($_SESSION['csrf']) ?>"
>

<input
    type="hidden"
    name="action"
    value="add_gallery"
>


<label>
    Jenis
</label>

<select
    name="type"
    id="galleryType"
    onchange="ubahJenisGaleri()"
>

    <option value="foto">
        📷 Foto
    </option>

    <option value="video">
        🎥 Vidio
    </option>

</select>


<label>
    Judul
</label>

<input
    name="title"
    required
>


<div id="bagianFoto">

    <label>
        Kategori Foto
    </label>

    <select
        name="category"
        id="galleryCategory"
        onchange="ubahKategoriFoto()"
    >

        <option value="beranda">
            🏠 Foto Beranda
        </option>

        <option value="album">
            📁 Foto Album
        </option>

    </select>


    <div
        id="bagianAlbum"
        style="display:none;"
    >

        <label>
            Nama Album
        </label>

        <input
            name="album_name"
            placeholder="Contoh: Pesta Bona Taon 2026"
        >

    </div>


    <label>
        Pilih Foto
    </label>

    <input
        type="file"
        name="image"
        accept=".jpg,.jpeg,.png,.webp"
    >

</div>


<div
    id="bagianVideo"
    style="display:none;"
>

    <label>
        Link Vidio
    </label>

    <input
        type="url"
        name="video_url"
        placeholder="https://www.youtube.com/watch?v=..."
    >

</div>


<label>
    Keterangan
</label>

<textarea
    name="description"
    placeholder="Keterangan foto atau vidio"
></textarea>


<br>
<br>

<button type="submit">
    Simpan ke Galeri
</button>

</form>


<script>

function ubahJenisGaleri() {

    const type =
        document.getElementById('galleryType').value;

    const foto =
        document.getElementById('bagianFoto');

    const video =
        document.getElementById('bagianVideo');

    if (type === 'foto') {

        foto.style.display = 'block';

        video.style.display = 'none';

    } else {

        foto.style.display = 'none';

        video.style.display = 'block';
    }
}


function ubahKategoriFoto() {

    const category =
        document.getElementById('galleryCategory').value;

    const album =
        document.getElementById('bagianAlbum');

    if (category === 'album') {

        album.style.display = 'block';

    } else {

        album.style.display = 'none';
    }
}


document.addEventListener('DOMContentLoaded', function() {

    ubahJenisGaleri();
    ubahKategoriFoto();

});

</script>


<h2>
    Daftar Galeri
</h2>

<div class="grid">

<?php if (!$gallery): ?>

<div class="card">
    Belum ada foto atau vidio.
</div>

<?php endif; ?>

<?php foreach ($gallery as $g): ?>

<div class="card">

    <?php if ($g['type'] === 'foto'): ?>

        <?php if (!empty($g['image_path'])): ?>

            <img
                src="<?= e($g['image_path']) ?>"
                alt="<?= e($g['title']) ?>"
                style="
                    width:100%;
                    height:200px;
                    object-fit:cover;
                    border-radius:7px;
                "
            >

        <?php endif; ?>


        <h3>
            <?= e($g['title']) ?>
        </h3>


        <?php if (
            $g['category'] === 'beranda'
        ): ?>

            <p class="muted">
                🏠 Foto Beranda
            </p>

        <?php else: ?>

            <p class="muted">
                📁 Album:
                <?= e($g['album_name']) ?>
            </p>

        <?php endif; ?>


    <?php else: ?>

        <h3>
            🎥 <?= e($g['title']) ?>
        </h3>


        <p class="muted">
            Vidio
        </p>


        <?php if (!empty($g['video_url'])): ?>

            <p>

                <a
                    class="btn"
                    href="<?= e($g['video_url']) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    ▶ Tonton Vidio
                </a>

            </p>

        <?php endif; ?>

    <?php endif; ?>


    <?php if (!empty($g['description'])): ?>

        <p>
            <?= nl2br(e($g['description'])) ?>
        </p>

    <?php endif; ?>


    <form
        method="post"
        onsubmit="return confirm('Hapus data galeri ini?')"
    >

        <input
            type="hidden"
            name="csrf"
            value="<?= e($_SESSION['csrf']) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="delete_gallery"
        >

        <input
            type="hidden"
            name="id"
            value="<?= (int)$g['id'] ?>"
        >

        <button type="submit">
            &#128465; Hapus
        </button>

    </form>

</div>

<?php endforeach; ?>

</div>

<?php require 'footer.php'; ?>

