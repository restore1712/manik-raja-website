<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   CSRF
========================= */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

/* =========================
   DATA WILAYAH
========================= */
$wilayahList = [];
$daerahList = [];

try {
    $stmt = $pdo->query("
        SELECT id, nama
        FROM wilayah
        ORDER BY nama ASC
    ");
    $wilayahList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("
        SELECT id, wilayah_id, jenis, nama
        FROM daerah
        ORDER BY FIELD(jenis, 'kota', 'kabupaten'), nama ASC
    ");
    $daerahList = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Gagal mengambil data wilayah: " . $e->getMessage());
}

/* =========================
   DEFAULT FORM
========================= */
$husbandName = '';
$husbandStatus = 'hidup';

$wifeName = '';
$wifeStatus = 'hidup';
$panggoaran = '';

$childrenCount = 0;
$phone = '';
$email = '';
$wilayahId = '';
$jenisDaerah = '';
$daerahId = '';
$notes = '';

$success = '';
$error = '';

/* =========================
   PROSES FORM
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* CSRF */
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = 'Permintaan tidak valid. Silakan coba lagi.';
    } else {

        $husbandName = trim($_POST['husband_name'] ?? '');
        $wifeName = trim($_POST['wife_name'] ?? '');
        $panggoaran = trim($_POST['panggoaran'] ?? '');

        $husbandStatus = $_POST['husband_status'] ?? 'hidup';
        $wifeStatus = $_POST['wife_status'] ?? 'hidup';

        $childrenCount = (int)($_POST['children_count'] ?? 0);
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $wilayahId = (int)($_POST['wilayah_id'] ?? 0);
        $jenisDaerah = $_POST['jenis_daerah'] ?? '';
        $daerahId = (int)($_POST['daerah_id'] ?? 0);

        $notes = trim($_POST['notes'] ?? '');

        /* =========================
           VALIDASI
        ========================= */

        if ($husbandName === '' && $wifeName === '') {
            $error = 'Minimal isi nama suami atau nama istri.';
        }

        elseif ($panggoaran === '' || mb_strlen($panggoaran) > 150) {
            $error = 'Nama panggoaran wajib diisi (maksimal 150 karakter).';
        }

        elseif (!in_array($husbandStatus, ['hidup', 'meninggal'], true)) {
            $error = 'Pilihan nama suami tidak valid.';
        }

        elseif (!in_array($wifeStatus, ['hidup', 'meninggal'], true)) {
            $error = 'Pilihan nama istri tidak valid.';
        }

        elseif ($childrenCount < 0) {
            $error = 'Jumlah anak tidak valid.';
        }

        elseif ($phone === '') {
            $error = 'No. HP / WhatsApp wajib diisi.';
        }

        elseif (!in_array($jenisDaerah, ['kota', 'kabupaten'], true)) {
            $error = 'Silakan pilih jenis daerah.';
        }

        elseif ($wilayahId <= 0 || $daerahId <= 0) {
            $error = 'Silakan pilih wilayah dan kota/kabupaten.';
        }

        else {

            /* =========================
               CEK DAERAH
            ========================= */
            $stmt = $pdo->prepare("
                SELECT id, nama
                FROM daerah
                WHERE id = ?
                  AND wilayah_id = ?
                  AND jenis = ?
                LIMIT 1
            ");

            $stmt->execute([
                $daerahId,
                $wilayahId,
                $jenisDaerah
            ]);

            $selectedDaerah = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$selectedDaerah) {

                $error = 'Kota/kabupaten yang dipilih tidak sesuai dengan wilayah.';

            } else {

                /* Nama utama untuk data lama */
                $fullName = $husbandName !== ''
                    ? $husbandName
                    : $wifeName;

                $city = $selectedDaerah['nama'];

                try {

                    $pdo->beginTransaction();

                    /*
                     * Kolom lama tetap diisi supaya
                     * sistem yang sudah ada tetap berjalan.
                     */
                    $stmt = $pdo->prepare("
                        INSERT INTO registrations (
                            full_name,
                            panggoaran,
                            phone,
                            email,
                            city,
                            father_name,
                            grandfather_name,
                            notes,
                            status,
                            husband_name,
                            husband_status,
                            wife_name,
                            wife_status,
                            children_count,
                            wilayah_id,
                            daerah_id
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, 'pending',
                            ?, ?, ?, ?, ?, ?, ?
                        )
                    ");

                    $stmt->execute([
                        $fullName,
                        $panggoaran,
                        $phone,
                        $email !== '' ? $email : null,
                        $city,
                        '',
                        '',
                        $notes,

                        $husbandName !== '' ? $husbandName : null,
                        $husbandStatus,

                        $wifeName !== '' ? $wifeName : null,
                        $wifeStatus,

                        $childrenCount,
                        $wilayahId,
                        $daerahId
                    ]);

                    $pdo->commit();

                    $success = 'Pendaftaran keluarga berhasil dikirim. Data akan diperiksa oleh pengurus.';

                    /* Reset form */
                    $husbandName = '';
                    $husbandStatus = 'hidup';

                    $wifeName = '';
                    $wifeStatus = 'hidup';
                    $panggoaran = '';

                    $childrenCount = 0;
                    $phone = '';
                    $email = '';
                    $wilayahId = '';
                    $jenisDaerah = '';
                    $daerahId = '';
                    $notes = '';

                    /* Token baru */
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $csrfToken = $_SESSION['csrf_token'];

                } catch (PDOException $e) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $error = 'Pendaftaran gagal disimpan. Silakan coba lagi.';
                }
            }
        }
    }
}

$pageTitle = 'Pendaftaran Keluarga';

require_once 'inc/header.php';
?>

<style>
.page-title {
    margin-bottom: 8px;
    color: #17324d;
}

.page-subtitle {
    color: #66727d;
    margin-bottom: 25px;
}

.form-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 30px;
    box-shadow: 0 8px 30px rgba(23, 50, 77, 0.08);
    border: 1px solid #e8e4dc;
    max-width: 850px;
    margin: auto;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
    color: #17324d;
}

.form-control,
.form-select {
    width: 100%;
    padding: 12px 13px;
    border: 1px solid #ccd2d8;
    border-radius: 8px;
    font-size: 15px;
    background: #fff;
    color: #24313d;
}

.form-control:focus,
.form-select:focus {
    outline: none;
    border-color: #d8a84e;
    box-shadow: 0 0 0 3px rgba(216, 168, 78, 0.15);
}

/* Nama + tombol simbol */
.name-symbol {
    display: flex;
    gap: 8px;
    align-items: stretch;
}

.name-symbol .name-input {
    flex: 1;
}

.symbol-select {
    width: 62px;
    flex-shrink: 0;
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
    padding: 8px 4px;
}

.symbol-help {
    margin-top: 6px;
    font-size: 12px;
    color: #777;
}

.row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.alert {
    padding: 13px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: #e9f7ef;
    color: #176b3a;
    border: 1px solid #b9e5c9;
}

.alert-error {
    background: #fdecec;
    color: #9b1c1c;
    border: 1px solid #f1bcbc;
}

.section-title {
    color: #17324d;
    margin: 28px 0 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #d8a84e;
}

.btn-submit {
    width: 100%;
    border: 0;
    background: #17324d;
    color: white;
    padding: 14px 20px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 10px;
}

.btn-submit:hover {
    background: #d8a84e;
    color: #17324d;
}

.required {
    color: #c0392b;
}

.info-box {
    background: #f7f4ed;
    border-left: 4px solid #d8a84e;
    padding: 13px 15px;
    border-radius: 6px;
    margin-bottom: 25px;
    color: #4d5963;
    font-size: 14px;
}

@media (max-width: 700px) {

    .form-card {
        padding: 20px;
    }

    .row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .name-symbol {
        gap: 6px;
    }

    .symbol-select {
        width: 58px;
    }
}
</style>

<h1 class="page-title">Pendaftaran Keluarga</h1>

<p class="page-subtitle">
    Silakan isi data keluarga Pomparan Manik Raja dengan lengkap.
</p>

<div class="form-card">


<?php if ($success): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="info-box">
    Untuk nama yang sudah meninggal, cukup pilih simbol
    <strong>†</strong> pada kotak kecil di sebelah nama.
    Tidak perlu mengetik simbol tersebut.
</div>

<form method="post">

    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($csrfToken) ?>"
    >

    <h2 class="section-title">Data Keluarga</h2>

    <div class="info-box">
        Formulir ini mencatat satu keluarga. Nama suami dan istri digunakan untuk data keluarga. Direktori publik hanya menampilkan nama panggoaran dan wilayah.
    </div>

    <div class="form-group">
        <label for="panggoaran">Nama Panggoaran <span class="required">*</span></label>
        <input
            type="text"
            id="panggoaran"
            name="panggoaran"
            class="form-control"
            maxlength="150"
            value="<?= htmlspecialchars($panggoaran) ?>"
            placeholder="Contoh: A. Sharon Manik / Br. Lumbangaol"
            required
        >
        <div class="symbol-help">Nama ini yang akan ditampilkan pada laman publik.</div>
    </div>

    <!-- SUAMI -->
    <div class="form-group">

        <label for="husband_name">
            Nama Suami
        </label>

        <div class="name-symbol">

            <input
                type="text"
                id="husband_name"
                name="husband_name"
                class="form-control name-input"
                value="<?= htmlspecialchars($husbandName) ?>"
                placeholder="Masukkan nama suami"
            >

            <select
                name="husband_status"
                class="form-select symbol-select"
                title="Pilih simbol"
            >
                <option value="hidup"
                    <?= $husbandStatus === 'hidup' ? 'selected' : '' ?>>
                    —
                </option>

                <option value="meninggal"
                    <?= $husbandStatus === 'meninggal' ? 'selected' : '' ?>>
                    †
                </option>
            </select>

        </div>

        <div class="symbol-help">
            Pilih <strong>†</strong> jika nama tersebut sudah meninggal.
        </div>

    </div>


    <!-- ISTRI -->
    <div class="form-group">

        <label for="wife_name">
            Nama Istri
        </label>

        <div class="name-symbol">

            <input
                type="text"
                id="wife_name"
                name="wife_name"
                class="form-control name-input"
                value="<?= htmlspecialchars($wifeName) ?>"
                placeholder="Masukkan nama istri"
            >

            <select
                name="wife_status"
                class="form-select symbol-select"
                title="Pilih simbol"
            >
                <option value="hidup"
                    <?= $wifeStatus === 'hidup' ? 'selected' : '' ?>>
                    —
                </option>

                <option value="meninggal"
                    <?= $wifeStatus === 'meninggal' ? 'selected' : '' ?>>
                    †
                </option>
            </select>

        </div>

        <div class="symbol-help">
            Pilih <strong>†</strong> jika nama tersebut sudah meninggal.
        </div>

    </div>


    <!-- JUMLAH ANAK -->
    <div class="form-group">

        <label for="children_count">
            Jumlah Anak <span class="required">*</span>
        </label>

        <input
            type="number"
            id="children_count"
            name="children_count"
            class="form-control"
            min="0"
            value="<?= htmlspecialchars((string)$childrenCount) ?>"
            required
        >

    </div>


    <h2 class="section-title">Kontak</h2>

    <div class="row">

        <div class="form-group">

            <label for="phone">
                No. HP / WhatsApp <span class="required">*</span>
            </label>

            <input
                type="text"
                id="phone"
                name="phone"
                class="form-control"
                value="<?= htmlspecialchars($phone) ?>"
                placeholder="Contoh: 081234567890"
                required
            >

        </div>

        <div class="form-group">

            <label for="email">
                Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                value="<?= htmlspecialchars($email) ?>"
                placeholder="Email (opsional)"
            >

        </div>

    </div>


    <h2 class="section-title">Domisili Keluarga</h2>

    <!-- WILAYAH -->
    <div class="form-group">

        <label for="wilayah_id">
            Provinsi / Wilayah <span class="required">*</span>
        </label>

        <select
            id="wilayah_id"
            name="wilayah_id"
            class="form-select"
            required
        >

            <option value="">
                -- Pilih Provinsi --
            </option>

            <?php foreach ($wilayahList as $wilayah): ?>

                <option
                    value="<?= (int)$wilayah['id'] ?>"
                    <?= (string)$wilayahId === (string)$wilayah['id'] ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($wilayah['nama']) ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <!-- JENIS DAERAH -->
    <div class="form-group">

        <label for="jenis_daerah">
            Jenis Daerah <span class="required">*</span>
        </label>

        <select
            id="jenis_daerah"
            name="jenis_daerah"
            class="form-select"
            required
        >

            <option value="">
                -- Pilih Jenis --
            </option>

            <option
                value="kota"
                <?= $jenisDaerah === 'kota' ? 'selected' : '' ?>
            >
                Kota
            </option>

            <option
                value="kabupaten"
                <?= $jenisDaerah === 'kabupaten' ? 'selected' : '' ?>
            >
                Kabupaten
            </option>

        </select>

    </div>


    <!-- DAERAH -->
    <div class="form-group">

        <label for="daerah_id">
            Kota / Kabupaten <span class="required">*</span>
        </label>

        <select
            id="daerah_id"
            name="daerah_id"
            class="form-select"
            required
        >

            <option value="">
                -- Pilih Kota / Kabupaten --
            </option>

            <?php foreach ($daerahList as $daerah): ?>

                <option
                    value="<?= (int)$daerah['id'] ?>"
                    data-wilayah="<?= (int)$daerah['wilayah_id'] ?>"
                    data-jenis="<?= htmlspecialchars($daerah['jenis']) ?>"
                    <?= (string)$daerahId === (string)$daerah['id'] ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($daerah['nama']) ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <!-- CATATAN -->
    <div class="form-group">

        <label for="notes">
            Catatan
        </label>

        <textarea
            id="notes"
            name="notes"
            class="form-control"
            rows="4"
            placeholder="Catatan tambahan jika diperlukan"
        ><?= htmlspecialchars($notes) ?></textarea>

    </div>


    <button type="submit" class="btn-submit">
        Kirim Pendaftaran
    </button>

</form>


</div>

<script>
const wilayahSelect = document.getElementById('wilayah_id');
const jenisSelect = document.getElementById('jenis_daerah');
const daerahSelect = document.getElementById('daerah_id');

const semuaDaerah = Array.from(
    daerahSelect.querySelectorAll('option[data-wilayah]')
);

function filterDaerah() {

    const wilayah = wilayahSelect.value;
    const jenis = jenisSelect.value;

    const selectedValue = daerahSelect.value;

    daerahSelect.innerHTML = '';

    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = '-- Pilih Kota / Kabupaten --';

    daerahSelect.appendChild(defaultOption);

    semuaDaerah.forEach(function(option) {

        const cocokWilayah =
            option.dataset.wilayah === wilayah;

        const cocokJenis =
            option.dataset.jenis === jenis;

        if (cocokWilayah && cocokJenis) {

            const newOption = option.cloneNode(true);

            daerahSelect.appendChild(newOption);
        }
    });

    const masihAda = Array.from(
        daerahSelect.options
    ).some(function(option) {
        return option.value === selectedValue;
    });

    if (masihAda) {
        daerahSelect.value = selectedValue;
    }
}

wilayahSelect.addEventListener('change', function() {
    daerahSelect.value = '';
    filterDaerah();
});

jenisSelect.addEventListener('change', function() {
    daerahSelect.value = '';
    filterDaerah();
});

/* Jalankan saat halaman pertama kali dibuka */
filterDaerah();
</script>

<?php require_once 'inc/footer.php'; ?>
