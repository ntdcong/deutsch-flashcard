<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

$notebook_id = (int)($_GET['notebook_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM notebooks WHERE id=? AND user_id=?');
$stmt->execute([$notebook_id, $user_id]);
$notebook = $stmt->fetch();
if (!$notebook) { die('Không tìm thấy sổ tay hoặc không có quyền.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_public'])) {
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $token = $notebook['public_token'] ?? '';
        if ($is_public && (!$token || trim($token) === '')) {
            $token = bin2hex(random_bytes(9));
        }
        $stmt = $pdo->prepare('UPDATE notebooks SET is_public=?, public_token=? WHERE id=? AND user_id=?');
        $stmt->execute([$is_public, $token, $notebook_id, $user_id]);
        $stmt = $pdo->prepare('SELECT * FROM notebooks WHERE id=? AND user_id=?');
        $stmt->execute([$notebook_id, $user_id]);
        $notebook = $stmt->fetch();
    }
    if (isset($_POST['regenerate_token'])) {
        $token = bin2hex(random_bytes(9));
        $stmt = $pdo->prepare('UPDATE notebooks SET public_token=? WHERE id=? AND user_id=?');
        $stmt->execute([$token, $notebook_id, $user_id]);
        $stmt = $pdo->prepare('SELECT * FROM notebooks WHERE id=? AND user_id=?');
        $stmt->execute([$notebook_id, $user_id]);
        $notebook = $stmt->fetch();
    }
    if (isset($_POST['create_import_link']) || isset($_POST['regenerate_import_link'])) {
        $new_code = md5($notebook_id . '|' . $user_id . '|' . microtime(true) . '|' . random_int(1000, 999999));
        // lấy share_code hiện tại
        $share_code = null;
        try {
            $stmt = $pdo->prepare('SELECT share_code FROM notebook_shares WHERE notebook_id = ? AND user_id = ?');
            $stmt->execute([$notebook_id, $user_id]);
            $row = $stmt->fetch();
            if ($row && !empty($row['share_code'])) {
                $share_code = $row['share_code'];
            }
        } catch (Exception $e) {}
        if ($share_code) {
            $stmt = $pdo->prepare('UPDATE notebook_shares SET share_code=? WHERE notebook_id=? AND user_id=?');
            $stmt->execute([$new_code, $notebook_id, $user_id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO notebook_shares (notebook_id, user_id, share_code) VALUES (?, ?, ?)');
            $stmt->execute([$notebook_id, $user_id, $new_code]);
        }
        $importMessage = 'Đã tạo link chia sẻ nhập sổ tay!';
        // refresh lại $notebook để đồng bộ
        $stmt = $pdo->prepare('SELECT * FROM notebooks WHERE id=? AND user_id=?');
        $stmt->execute([$notebook_id, $user_id]);
        $notebook = $stmt->fetch();
    }
}

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';
$token = $notebook['public_token'] ?? '';
$is_public = (int)($notebook['is_public'] ?? 0);

$flashcardLink = $token ? $baseUrl . 'study_flashcard.php?token=' . urlencode($token) : '';
$quizMeaningLink = $token ? $baseUrl . 'study_quiz.php?token=' . urlencode($token) : '';
$quizGenderLink  = $token ? $baseUrl . 'study_gender.php?token=' . urlencode($token) : '';
$publicLandingLink = $token ? $baseUrl . 'public_notebook.php?token=' . urlencode($token) : '';

$importMessage = $importMessage ?? '';
$importLink = '';
$share_code = null;
try {
    $stmt = $pdo->prepare('SELECT share_code FROM notebook_shares WHERE notebook_id = ? AND user_id = ?');
    $stmt->execute([$notebook_id, $user_id]);
    $row = $stmt->fetch();
    if ($row && !empty($row['share_code'])) {
        $share_code = $row['share_code'];
    }
} catch (Exception $e) {}
if ($share_code) {
    $importLink = $baseUrl . 'import_shared.php?code=' . urlencode($share_code);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Chia sẻ sổ tay</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; font-family: 'Inter', system-ui, sans-serif }
    .card { border: none; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,.1) }
    .mode-card { border: 1px solid rgba(15,23,42,.08); border-radius: 12px; padding: 16px; background: #fff }
    .link-box { background: #f8fafc; border: 1px solid rgba(15,23,42,.08); padding: 10px 12px; border-radius: 10px }
    .btn-icon { display:flex; align-items:center; gap:8px }
    .section-title { display:flex; align-items:center; gap:8px }
    .kbd { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; background:#eef2ff; border:1px solid #c7d2fe; padding:.05rem .35rem; border-radius:6px; font-size:.825rem }
    .focus-ring:focus { outline: 4px solid #a78bfa !important; outline-offset: 2px; }
    .sticky-actions { position: sticky; top: .75rem; z-index: 10; }
    .small-muted { font-size:.925rem; color:#64748b }
    .copyable { cursor: pointer; }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
</head>
<body>
<nav class="navbar navbar-light sticky-top bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php"><i class="bi bi-arrow-left"></i> Quay lại</a>
    <div class="d-flex align-items-center gap-2">
      <span class="badge <?= $is_public ? 'bg-success' : 'bg-secondary' ?>"><?= $is_public ? 'ĐANG CÔNG KHAI' : 'ĐANG ẨN' ?></span>
      <span class="navbar-text text-truncate" title="<?= htmlspecialchars($notebook['title']) ?>">Chia sẻ: <?= htmlspecialchars($notebook['title']) ?></span>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="row justify-content-center g-3">
    <div class="col-12 col-lg-8">
      <div class="card p-4">
        <!-- KHỐI A: Công khai -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="section-title">
            <i class="bi bi-globe2"></i>
            <h3 class="m-0">Công khai & Liên kết</h3>
          </div>
          <form method="post" class="m-0">
            <input type="hidden" name="toggle_public" value="1" />
            <div class="form-check form-switch m-0">
              <input class="form-check-input focus-ring" type="checkbox" id="switchPublic" name="is_public" value="1" <?= $is_public ? 'checked' : '' ?> onchange="this.form.submit();" aria-label="Bật công khai sổ tay">
            </div>
          </form>
        </div>

        <?php if ($is_public && $token): ?>
          <!-- Token & copy -->
          <div class="mb-3">
            <label class="form-label">Token truy cập</label>
            <div class="input-group">
              <input type="text" class="form-control" value="<?= htmlspecialchars($token) ?>" readonly aria-label="Token truy cập">
              <button class="btn btn-outline-secondary btn-icon" type="button" data-copy="<?= htmlspecialchars($token) ?>">
                <i class="bi bi-clipboard"></i> Sao chép
              </button>
              <form method="post">
                <button class="btn btn-outline-danger btn-icon" name="regenerate_token" value="1"
                        onclick="return confirm('Đổi token sẽ làm link cũ không dùng được. Tiếp tục?')">
                  <i class="bi bi-arrow-clockwise"></i> Đổi token
                </button>
              </form>
            </div>
            <div class="small-muted mt-1">Tốt nhất không nên đụng vào nếu không biết nhé :v </div>
          </div>

          <!-- Các chế độ truy cập -->
          <div class="row g-3">
            <?php
              $qrData = [
                ['label' => 'Trang lựa chọn', 'link' => $publicLandingLink, 'icon' => 'bi-columns-gap'],
                ['label' => 'Flashcard',     'link' => $flashcardLink,     'icon' => 'bi-card-text'],
                ['label' => 'Quiz Nghĩa',    'link' => $quizMeaningLink,   'icon' => 'bi-question-circle'],
                ['label' => 'Quiz Giống',    'link' => $quizGenderLink,    'icon' => 'bi-gender-ambiguous'],
              ];
              foreach ($qrData as $item):
            ?>
            <div class="col-12 col-md-6">
              <div class="mode-card h-100 d-flex flex-column">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="bi <?= $item['icon'] ?>"></i>
                  <h5 class="m-0"><?= $item['label'] ?></h5>
                </div>
                <div class="link-box d-flex align-items-center justify-content-between gap-2 mb-2 copyable" title="Nhấn để sao chép">
                  <small class="text-truncate" style="max-width:70%"><?= htmlspecialchars($item['link']) ?></small>
                  <div class="btn-group">  
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-copy="<?= htmlspecialchars($item['link']) ?>" aria-label="Sao chép liên kết <?= $item['label'] ?>"><i class="bi bi-clipboard"></i></button>
                  </div>
                </div>
                <button class="btn btn-outline-dark w-100 mt-auto btn-icon" type="button"
                        data-qr="<?= htmlspecialchars($item['link']) ?>" data-title="<?= htmlspecialchars($item['label']) ?>">
                  <i class="bi bi-qr-code-scan"></i> Hiện QR
                </button>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-info d-flex align-items-start gap-2">
            <i class="bi bi-info-circle mt-1"></i>
            <div>
              <strong>Chưa bật công khai.</strong><br>
              Bật công khai để tạo link chia sẻ và mã QR.
            </div>
          </div>
        <?php endif; ?>

        <hr class="my-4" />

        <!-- KHỐI B: Chia sẻ nhập sổ tay -->
        <div class="section-title mb-2">
          <i class="bi bi-share"></i>
          <h4 class="m-0">Chia sẻ sổ tay</h4>
        </div>
        <p class="text-muted mb-3">Ai có link này có thể sao chép toàn bộ sổ tay vào tài khoản riêng của họ.</p>

        <?php if (!empty($importMessage)): ?>
          <div class="alert alert-success py-2"><?= htmlspecialchars($importMessage) ?></div>
        <?php endif; ?>

        <?php if ($importLink): ?>
          <div class="link-box d-flex align-items-center justify-content-between gap-2 mb-2 copyable" title="Nhấn để sao chép">
            <small class="text-truncate" style="max-width:70%"><?= htmlspecialchars($importLink) ?></small>
            <div class="btn-group">
              <button class="btn btn-sm btn-outline-secondary" type="button" data-copy="<?= htmlspecialchars($importLink) ?>"><i class="bi bi-clipboard"></i></button>
            </div>
          </div>
          <form method="post" class="mt-2">
            <button class="btn btn-outline-danger btn-sm btn-icon" name="regenerate_import_link" value="1"
                    onclick="return confirm('Đổi link nhập sẽ làm link cũ không dùng được. Tiếp tục?')">
              <i class="bi bi-arrow-clockwise"></i> Đổi link nhập
            </button>
          </form>
        <?php else: ?>
          <form method="post" class="mt-2">
            <button class="btn btn-outline-primary btn-sm btn-icon" name="create_import_link" value="1">
              <i class="bi bi-link-45deg"></i> Tạo link nhập sổ tay
            </button>
          </form>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<!-- MODAL QR -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center p-4">
      <h5 class="mb-3" id="qrModalTitle">Mã QR</h5>
      <div id="qrLoading" class="d-none">
        <div class="spinner-border" role="status" aria-hidden="true"></div>
        <div class="mt-2">Đang tạo QR...</div>
      </div>
      <canvas id="qrCanvas" width="256" height="256" class="mx-auto d-none" aria-label="Mã QR"></canvas>
      <div class="d-flex gap-2 justify-content-center mt-3">
        <button type="button" class="btn btn-outline-secondary" id="btnDownloadQR" disabled><i class="bi bi-download"></i> Tải QR</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- Toast copy -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
  <div id="copyToast" class="toast align-items-center text-white bg-dark border-0" role="status" aria-live="polite" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        ✅ Đã sao chép vào clipboard
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Đóng"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const copyToast = new bootstrap.Toast(document.getElementById('copyToast'), { delay: 1200 });

  // 1) Copy helpers (nút và cả click vào hộp link)
  document.querySelectorAll('[data-copy]').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const text = btn.getAttribute('data-copy');
      await doCopy(text);
    });
  });
  document.querySelectorAll('.copyable').forEach(box=>{
    box.addEventListener('click', async (e)=>{
      // tránh click vào nút Mở/Sao chép bên trong
      if (e.target.closest('a,button')) return;
      const small = box.querySelector('small');
      if (small && small.textContent.trim()) await doCopy(small.textContent.trim());
    });
  });

  async function doCopy(text){
    try {
      if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(text);
      } else {
        // fallback cho trình duyệt cũ
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
      }
      copyToast.show();
    } catch(e){
      alert('Không thể sao chép. Vui lòng copy thủ công.');
    }
  }

  // 2) QR modal
  const qrModalEl = document.getElementById('qrModal');
  const qrModal = new bootstrap.Modal(qrModalEl);
  const qrCanvas = document.getElementById('qrCanvas');
  const qrTitle = document.getElementById('qrModalTitle');
  const qrLoading = document.getElementById('qrLoading');
  const btnDownloadQR = document.getElementById('btnDownloadQR');

  document.querySelectorAll('[data-qr]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const link = btn.getAttribute('data-qr');
      const title = btn.getAttribute('data-title') || 'Mã QR';
      qrTitle.textContent = title;
      btnDownloadQR.disabled = true;
      qrCanvas.classList.add('d-none');
      qrLoading.classList.remove('d-none');

      // render QR
      QRCode.toCanvas(qrCanvas, link, { width: 256 }, function (error) {
        qrLoading.classList.add('d-none');
        if (error) {
          console.error(error);
          alert('Không tạo được QR.');
          return;
        }
        qrCanvas.classList.remove('d-none');
        btnDownloadQR.disabled = false;
      });

      qrModal.show();
      // lưu link cho nút tải
      btnDownloadQR.dataset.link = link;
      btnDownloadQR.onclick = ()=>{
        const a = document.createElement('a');
        a.download = 'qr-'+Date.now()+'.png';
        a.href = qrCanvas.toDataURL('image/png');
        a.click();
      };
    });
  });

  // 3) Nhấn phím Enter để copy khi focus vào nút copy (hỗ trợ accessibility)
  document.querySelectorAll('[data-copy]').forEach(el=>{
    el.setAttribute('tabindex','0');
    el.addEventListener('keypress', (e)=>{
      if (e.key === 'Enter') el.click();
    });
  });

})();
</script>
</body>
</html>
