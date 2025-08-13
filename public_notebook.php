<?php
session_start();
require 'db.php';

$token = $_GET['token'] ?? '';
if ($token === '') { die('Thiếu token.'); }

$stmt = $pdo->prepare('SELECT * FROM notebooks WHERE public_token = ? AND is_public = 1');
$stmt->execute([$token]);
$notebook = $stmt->fetch();
if (!$notebook) { die('Link không hợp lệ hoặc sổ tay không công khai!'); }

$title = $notebook['title'] ?? 'Sổ tay';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title) ?> - Học công khai</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea, #764ba2);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Inter', system-ui, sans-serif;
    }
    .card-custom {
      border: none;
      border-radius: 20px;
      background: #fff;
      box-shadow: 0 10px 40px rgba(0,0,0,0.15);
      padding: 40px 30px;
      text-align: center;
    }
    .card-custom h3 {
      font-weight: bold;
    }
    .card-custom p {
      color: #6c757d;
    }
    .mode-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 14px 18px;
      font-weight: 600;
      font-size: 1.1rem;
      border-radius: 12px;
      transition: all 0.2s ease;
    }
    .mode-btn i {
      font-size: 1.3rem;
    }
    .mode-btn:hover {
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
      transform: translateY(-1px);
    }
    @media (max-width: 576px) {
      .card-custom {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>
  <div class="container px-3">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6">
        <div class="card-custom">
          <h3 class="mb-2"><?= htmlspecialchars($title) ?></h3>
          <p class="mb-4">Chọn chế độ học phù hợp</p>
          <div class="d-grid gap-3">
            <a class="btn btn-warning mode-btn" href="study_flashcard.php?token=<?= urlencode($token) ?>">
              <i class="bi bi-journal-richtext"></i> Flashcard
            </a>
            <a class="btn btn-primary mode-btn text-white" href="study_quiz.php?token=<?= urlencode($token) ?>">
              <i class="bi bi-question-circle"></i> Quiz nghĩa
            </a>
            <a class="btn btn-info mode-btn text-white" href="study_gender.php?token=<?= urlencode($token) ?>">
              <i class="bi bi-gender-ambiguous"></i> Quiz giống
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
