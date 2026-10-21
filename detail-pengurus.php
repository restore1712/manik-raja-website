<?php
require 'config.php';

$officerId = max(0, (int)($_GET['id'] ?? 0));
$statement = $pdo->prepare('SELECT o.id, o.name, o.position_name, o.period, o.level, o.region, o.photo_path, o.officer_group_id, g.name AS group_name FROM officers o LEFT JOIN officer_groups g ON g.id = o.officer_group_id WHERE o.id = ? LIMIT 1');
$statement->execute([$officerId]);
$officer = $statement->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$officer) {
    http_response_code(404);
}

$pageTitle = $officer ? $officer['name'] : 'Pengurus tidak ditemukan';
$backUrl = !$officer
    ? 'pengurus.php'
    : ($officer['level'] === 'pusat'
        ? 'pengurus.php?level=pusat&region=' . (int)($officer['officer_group_id'] ?? 0)
        : 'pengurus.php?level=' . urlencode($officer['level']) . '&region=' . ((int)($officer['officer_group_id'] ?? 0) > 0 ? (int)$officer['officer_group_id'] : urlencode((string)$officer['region'])));

require 'inc/header.php';
?>

<style>
.detail-officer-page{max-width:900px;margin:0 auto}
.detail-officer-back{display:inline-block;margin:0 0 18px;padding:10px 16px;border-radius:8px;background:#17324d;color:#fff;text-decoration:none;font-weight:700}
.detail-officer-back:hover{background:#d8a84e;color:#17324d}
.detail-officer-card{display:grid;grid-template-columns:280px 1fr;gap:30px;align-items:center;background:#fff;border:1px solid #eee7d9;border-radius:18px;padding:26px;box-shadow:0 8px 24px rgba(23,50,77,.08)}
.detail-officer-photo{width:100%;height:320px;overflow:hidden;border-radius:14px;background:#f2eee5;display:grid;place-items:center;color:#17324d}
.detail-officer-photo img{width:100%;height:100%;object-fit:cover}
.detail-officer-photo svg{width:58px;height:58px;fill:currentColor}
.detail-officer-label{margin:0 0 8px;color:#b88925;font-size:12px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase}
.detail-officer-name{margin:0 0 18px;color:#17324d;font-size:30px;line-height:1.2}
.detail-officer-position{display:inline-block;margin-bottom:18px;padding:8px 13px;border-radius:20px;background:#f7f4ed;color:#17324d;font-weight:700}
.detail-officer-meta{margin:0;color:#55616c;line-height:1.8}
@media(max-width:650px){.detail-officer-card{grid-template-columns:1fr;gap:18px;padding:18px}.detail-officer-photo{height:300px;max-width:100%}.detail-officer-name{font-size:25px}}
</style>

<div class="detail-officer-page">
    <a class="detail-officer-back" href="<?= e($backUrl) ?>">Kembali ke daftar pengurus</a>

    <?php if (!$officer): ?>
        <div class="card">Data pengurus tidak ditemukan.</div>
    <?php else: ?>
        <article class="detail-officer-card">
            <div class="detail-officer-photo">
                <?php if (!empty($officer['photo_path'])): ?>
                    <img src="<?= e($officer['photo_path']) ?>" alt="Foto <?= e($officer['name']) ?>">
                <?php else: ?>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm0 2c-4.1 0-7.5 2.1-7.5 4.7V22h15v-3.3c0-2.6-3.4-4.7-7.5-4.7Z"/></svg>
                <?php endif; ?>
            </div>
            <div>
                <p class="detail-officer-label">Pomparan Manik Raja</p>
                <h1 class="detail-officer-name"><?= e($officer['name']) ?></h1>
                <div class="detail-officer-position"><?= e($officer['position_name']) ?></div>
                <p class="detail-officer-meta">
                    <?= $officer['level'] === 'pusat' ? 'Pengurus Pusat' : ($officer['level'] === 'regional' ? 'Pengurus Regional' : 'Pengurus Daerah') ?>
                    <?php if (!empty($officer['group_name'])): ?> · <?= e($officer['group_name']) ?><?php elseif (!empty($officer['region'])): ?> · <?= e($officer['region']) ?><?php endif; ?>
                    <?php if (!empty($officer['period'])): ?><br>Periode: <?= e($officer['period']) ?><?php endif; ?>
                </p>
            </div>
        </article>
    <?php endif; ?>
</div>

<?php require 'inc/footer.php'; ?>
