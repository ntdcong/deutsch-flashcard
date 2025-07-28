<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hướng dẫn khắc phục lỗi phát âm tiếng Đức</title>
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-bg: #ffffff;
            --card-border-radius: 16px;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --text-primary: #1a202c;
            --text-secondary: #4a5568;
            --text-muted: #718096;
            --border-light: #e2e8f0;
            --accent-blue: #3182ce;
            --accent-green: #38a169;
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--primary-gradient);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: var(--text-primary);
            line-height: 1.6;
        }

        .navbar {
            background-color: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 600;
            color: var(--text-secondary) !important;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar-brand i {
            font-size: 1.2em;
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            padding: 20px 15px;
            width: 100%;
        }

        .guide-container {
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            padding: 2.5rem;
            background-color: var(--card-bg);
            border-radius: var(--card-border-radius);
            box-shadow: var(--card-shadow);
        }

        .guide-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .guide-header p {
            font-size: 1.1rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .alert-info {
            border-radius: 12px;
            background-color: #ebf8ff;
            border-left: 4px solid var(--accent-blue);
            padding: 1rem 1.25rem;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .alert-info .bi {
            color: var(--accent-blue);
            font-size: 1.3rem;
            margin-top: 2px;
        }

        .os-section {
            margin-top: 1.75rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-light);
        }

        .os-section:first-child {
            margin-top: 0;
            border-top: none;
            padding-top: 0;
        }

        .os-section h2 {
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .os-section ol,
        .os-section ul {
            padding-left: 1.5rem;
        }

        .os-section li {
            margin-bottom: 0.6rem;
        }

        .os-section li p {
            margin-bottom: 0;
        }

        .step-icon {
            color: var(--accent-blue);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--accent-blue);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .back-link:hover {
            color: #1e40af;
            text-decoration: underline;
        }

        pre {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            overflow-x: auto;
            font-size: 0.9em;
            margin: 1rem 0;
        }

        code {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            background-color: #f1f5f9;
            color: #e53e3e;
            padding: 0.2em 0.4em;
            border-radius: 6px;
            font-size: 0.875em;
        }

        .footer-note {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            color: var(--text-muted);
            font-size: 0.95rem;
            border-top: 1px dashed var(--border-light);
        }

        @media (max-width: 768px) {
            .guide-container {
                padding: 1.75rem;
            }

            .guide-header h1 {
                font-size: 1.75rem;
            }

            .os-section {
                padding-left: 0;
                padding-right: 0;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }

            .navbar .container {
                padding-left: 10px;
                padding-right: 10px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-arrow-left-circle"></i>
                <span>Quay lại</span>
            </a>
            <span class="navbar-text text-truncate fw-medium">
                Hướng dẫn TTS
            </span>
        </div>
    </nav>

    <div class="main-content">
        <div class="guide-container">
            <div class="guide-header text-center">
                <h1>Khắc phục lỗi phát âm tiếng Đức</h1>
                <p>Cài đặt giọng nói hệ thống để chức năng đọc tự động hoạt động chính xác</p>
            </div>

            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    <strong>Thông tin quan trọng:</strong> Chức năng phát âm (Text-to-Speech - TTS) sử dụng <strong>giọng nói được cài đặt trên hệ điều hành</strong>. Nếu gói giọng nói tiếng Đức chưa được tải về, hệ thống sẽ không phát âm được hoặc phát âm sai. Hãy làm theo hướng dẫn dưới đây để cài đặt.
                </div>
            </div>

            <div class="os-section">
                <h2><i class="bi bi-apple step-icon"></i> Trên iPhone (iOS)</h2>
                <ol>
                    <li>Mở ứng dụng <strong>Cài đặt</strong> (Settings).</li>
                    <li>Chọn <strong>Trợ năng</strong> → <strong>Phát âm & Văn bản</strong> (Spoken Content).</li>
                    <li>Vào mục <strong>Giọng nói</strong> (Voices).</li>
                    <li>Tìm kiếm ngôn ngữ <strong>Tiếng Đức (Deutsch)</strong>.</li>
                    <li>Nếu thấy giọng nói nhưng có biểu tượng <strong> đám mây </strong> hoặc nút <strong>Tải xuống</strong>, hãy nhấn để cài đặt.</li>
                    <li>Sau khi hoàn tất, khởi động lại trình duyệt (Safari/Chrome) và thử lại chức năng phát âm.</li>
                </ol>
            </div>

            <div class="os-section">
                <h2><i class="bi bi-apple step-icon"></i> Trên Mac (macOS)</h2>
                <ol>
                    <li>Mở <strong>Cài đặt Hệ thống</strong> (System Settings) hoặc <strong>Tùy chọn Hệ thống</strong> (System Preferences).</li>
                    <li>Chọn <strong>Trợ năng</strong> → <strong>Nội dung được nói</strong> (Spoken Content).</li>
                    <li>Chọn <strong>Giọng nói Hệ thống</strong> → <strong>Quản lý Giọng nói...</strong></li>
                    <li>Tìm giọng nói tiếng Đức (<strong>Deutsch</strong>). Nếu trạng thái là “Không có” hoặc có nút <strong>Cài đặt</strong>, hãy nhấn để tải về.</li>
                    <li>Khởi động lại trình duyệt để cập nhật thay đổi.</li>
                </ol>
            </div>

            <div class="os-section">
                <h2><i class="bi bi-windows step-icon"></i> Trên Windows</h2>
                <ol>
                    <li>Mở <strong>Cài đặt</strong> (Windows + I) → <strong>Thời gian & Ngôn ngữ</strong>.</li>
                    <li>Chọn <strong>Ngôn ngữ & Vùng</strong> → <strong>Ngôn ngữ</strong>.</li>
                    <li>Thêm hoặc đảm bảo ngôn ngữ <strong>Tiếng Đức (Deutsch)</strong> đã được cài đặt.</li>
                    <li>Đi tới <strong>Phát âm</strong> → <strong>Quản lý giọng nói</strong>.</li>
                    <li>Tải xuống một giọng nói tiếng Đức (ví dụ: <code>Microsoft Katja</code>).</li>
                    <li>Khởi động lại trình duyệt (Chrome/Edge) để áp dụng.</li>
                </ol>
            </div>

            <div class="os-section">
                <h2><i class="bi bi-browser-chrome step-icon"></i> Lưu ý về Trình duyệt</h2>
                <ul>
                    <li><strong>Safari</strong> tích hợp sâu với hệ thống TTS của Apple – hoạt động ổn định nhất trên iOS và macOS.</li>
                    <li><strong>Chrome</strong> và <strong>Edge</strong> cũng sử dụng giọng nói hệ thống, nhưng đôi khi cần cấp quyền hoặc khởi động lại.</li>
                    <li>Đảm bảo trình duyệt đã được cập nhật phiên bản mới nhất.</li>
                    <li>Một số trình duyệt di động có thể không hỗ trợ đầy đủ TTS nếu không bật quyền truy cập.</li>
                </ul>
            </div>

            <div class="footer-note">
                <p>Nếu đã thực hiện đầy đủ các bước mà vẫn không phát âm được, có thể do trình duyệt hoặc hệ điều hành không hỗ trợ tiếng Đức. Hãy kiểm tra cập nhật hệ thống hoặc liên hệ hỗ trợ kỹ thuật.</p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (nếu cần tương tác nâng cao sau này) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>