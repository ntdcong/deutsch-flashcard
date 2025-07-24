<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Học Biến Cách Chi Tiết - DeutschGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(to right, rgb(90, 97, 229), rgb(123, 244, 224));
            --nominativ-color: #3498db;
            --akkusativ-color: #e74c3c;
            --dativ-color: #2ecc71;
            --genitiv-color: #f39c12;
            --card-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .navbar { 
            background: var(--primary-gradient); 
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); 
        }
        
        .navbar-brand { 
            font-weight: bold; 
            font-size: 1.5rem; 
            color: rgb(255, 255, 255); 
        }
        
        .case-header {
            padding: 20px;
            border-radius: 10px;
            color: white;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .nominativ-bg { background-color: var(--nominativ-color); }
        .akkusativ-bg { background-color: var(--akkusativ-color); }
        .dativ-bg { background-color: var(--dativ-color); }
        .genitiv-bg { background-color: var(--genitiv-color); }
        
        .case-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            border-radius: 15px;
        }
        
        .case-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        
        .example-box {
            background: #f8f9fa;
            border-left: 4px solid;
            padding: 15px;
            margin: 10px 0;
            border-radius: 0 8px 8px 0;
        }
        
        .border-nominativ { border-left-color: var(--nominativ-color); }
        .border-akkusativ { border-left-color: var(--akkusativ-color); }
        .border-dativ { border-left-color: var(--dativ-color); }
        .border-genitiv { border-left-color: var(--genitiv-color); }
        
        .verb-list, .preposition-list {
            background: #e8f4fc;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        .sticky-sidebar {
            position: sticky;
            top: 20px;
        }
        
        .progress-container {
            margin: 20px 0;
        }
        
        .usage-rule {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 0 8px 8px 0;
            margin: 15px 0;
        }
        
        @media (max-width: 768px) {
            .case-header {
                font-size: 1.2rem;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="home.php">
                </i> GERMANLY
            </a>
            <div class="d-flex">
                <a href="logout.php" class="btn btn-outline-light">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-lg-3 mb-4">
                <div class="sticky-sidebar">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <i class="bi bi-list"></i> Mục lục
                        </div>
                        <div class="list-group list-group-flush">
                            <a href="#overview" class="list-group-item list-group-item-action">
                                <i class="bi bi-eye"></i> Tổng quan
                            </a>
                            <a href="#nominativ-detail" class="list-group-item list-group-item-action">
                                <i class="bi bi-1-circle"></i> Nominativ
                            </a>
                            <a href="#akkusativ-detail" class="list-group-item list-group-item-action">
                                <i class="bi bi-2-circle"></i> Akkusativ
                            </a>
                            <a href="#dativ-detail" class="list-group-item list-group-item-action">
                                <i class="bi bi-3-circle"></i> Dativ
                            </a>
                            <a href="#genitiv-detail" class="list-group-item list-group-item-action">
                                <i class="bi bi-4-circle"></i> Genitiv
                            </a>
                            <a href="#practice" class="list-group-item list-group-item-action">
                                <i class="bi bi-search"></i> Mẹo & Kinh nghiệm
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Tổng quan -->
                <div class="card case-card mb-4">
                    <div class="card-header bg-info text-white">
                        <h3><i class="bi bi-eye"></i> Tổng quan về Biến cách tiếng Đức</h3>
                    </div>
                    <div class="card-body">
                        <p>Biến cách (Deklination) là sự thay đổi hình thức của danh từ, tính từ, đại từ theo chức năng ngữ pháp trong câu.</p>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 bg-primary text-white rounded">
                                    <h4>4</h4>
                                    <small>Biến cách</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 bg-success text-white rounded">
                                    <h4>3</h4>
                                    <small>Giống tính</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 bg-warning text-white rounded">
                                    <h4>2</h4>
                                    <small>Số lượng</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 bg-danger text-white rounded">
                                    <h4>∞</h4>
                                    <small>Ứng dụng</small>
                                </div>
                            </div>
                        </div>

                        <div class="usage-rule">
                            <h5><i class="bi bi-lightbulb"></i> Mẹo nhớ:</h5>
                            <p><strong>N - A - D - G</strong>: Người - Việc - Với ai - Của ai</p>
                            <ul>
                                <li><strong>Nominativ</strong>: Ai làm gì?</li>
                                <li><strong>Akkusativ</strong>: Làm gì?</li>
                                <li><strong>Dativ</strong>: Cho ai? Với ai?</li>
                                <li><strong>Genitiv</strong>: Của ai?</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- NOMINATIV CHI TIẾT -->
                <div id="nominativ-detail">
                    <div class="case-header nominativ-bg">
                        <h2><i class="bi bi-1-circle"></i> NOMINATIV - CHỦ NGỮ</h2>
                    </div>
                    
                    <div class="card case-card">
                        <div class="card-body">
                            <h4><i class="bi bi-check-circle"></i> Khi nào dùng Nominativ?</h4>
                            <ul>
                                <li>Là <strong>chủ ngữ</strong> trong câu</li>
                                <li>Trả lời câu hỏi: <strong>Wer? Was?</strong> (Ai? Cái gì?)</li>
                                <li>Sau động từ liên hệ: <strong>sein, werden, bleiben...</strong></li>
                            </ul>

                            <div class="usage-rule">
                                <h5><i class="bi bi-exclamation-triangle"></i> Lưu ý:</h5>
                                <p>Mạo từ <strong>không thay đổi</strong> trong Nominativ</p>
                            </div>

                            <h4><i class="bi bi-table"></i> Bảng mạo từ Nominativ:</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered text-center">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>Giống</th>
                                            <th>Xác định</th>
                                            <th>Không xác định</th>
                                            <th>Ví dụ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Đực (der)</td>
                                            <td>der Mann</td>
                                            <td>ein Mann</td>
                                            <td><strong>Der</strong> Mann liest.</td>
                                        </tr>
                                        <tr>
                                            <td>Cái (die)</td>
                                            <td>die Frau</td>
                                            <td>eine Frau</td>
                                            <td><strong>Die</strong> Frau arbeitet.</td>
                                        </tr>
                                        <tr>
                                            <td>Trung (das)</td>
                                            <td>das Kind</td>
                                            <td>ein Kind</td>
                                            <td><strong>Das</strong> Kind schläft.</td>
                                        </tr>
                                        <tr>
                                            <td>Số nhiều</td>
                                            <td>die Kinder</td>
                                            <td>keine Kinder</td>
                                            <td><strong>Die</strong> Kinder spielen.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h4><i class="bi bi-chat"></i> Ví dụ thực tế:</h4>
                            <div class="example-box border-nominativ">
                                <p><strong>Der</strong> Lehrer unterrichtet die Klasse.</p>
                                <small class="text-muted">Giáo viên giảng bài cho lớp. (Der Lehrer = chủ ngữ)</small>
                            </div>
                            <div class="example-box border-nominativ">
                                <p><strong>Was</strong> kostet das Buch?</p>
                                <small class="text-muted">Cuốn sách giá bao nhiêu? (Was = chủ ngữ)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AKKUSATIV CHI TIẾT -->
                <div id="akkusativ-detail">
                    <div class="case-header akkusativ-bg">
                        <h2><i class="bi bi-2-circle"></i> AKKUSATIV - TÂN NGỮ TRỰC TIẾP</h2>
                    </div>
                    
                    <div class="card case-card">
                        <div class="card-body">
                            <h4><i class="bi bi-check-circle"></i> Khi nào dùng Akkusativ?</h4>
                            <ul>
                                <li>Là <strong>tân ngữ trực tiếp</strong></li>
                                <li>Trả lời câu hỏi: <strong>Wen? Was?</strong> (Ai? Cái gì?)</li>
                                <li>Sau các động từ: sehen, kaufen, hören, lesen, essen...</li>
                            </ul>

                            <div class="usage-rule">
                                <h5><i class="bi bi-exclamation-triangle"></i> Lưu ý quan trọng:</h5>
                                <p>Chỉ có <strong>der → den</strong> thay đổi trong số ít nam tính!</p>
                            </div>

                            <h4><i class="bi bi-table"></i> Bảng mạo từ Akkusativ:</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered text-center">
                                    <thead class="table-danger">
                                        <tr>
                                            <th>Giống</th>
                                            <th>Xác định</th>
                                            <th>Không xác định</th>
                                            <th>Ví dụ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Đực (der)</td>
                                            <td><strong>den</strong> Mann</td>
                                            <td>einen Mann</td>
                                            <td>Ich sehe <strong>den</strong> Mann.</td>
                                        </tr>
                                        <tr>
                                            <td>Cái (die)</td>
                                            <td>die Frau</td>
                                            <td>eine Frau</td>
                                            <td>Ich sehe <strong>die</strong> Frau.</td>
                                        </tr>
                                        <tr>
                                            <td>Trung (das)</td>
                                            <td>das Kind</td>
                                            <td>ein Kind</td>
                                            <td>Ich sehe <strong>das</strong> Kind.</td>
                                        </tr>
                                        <tr>
                                            <td>Số nhiều</td>
                                            <td>die Kinder</td>
                                            <td>keine Kinder</td>
                                            <td>Ich sehe <strong>die</strong> Kinder.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h4><i class="bi bi-list"></i> Động từ yêu cầu Akkusativ:</h4>
                            <div class="verb-list">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>sehen</strong> - nhìn thấy<br>
                                        <strong>kaufen</strong> - mua<br>
                                        <strong>hören</strong> - nghe
                                    </div>
                                    <div class="col-md-4">
                                        <strong>lesen</strong> - đọc<br>
                                        <strong>essen</strong> - ăn<br>
                                        <strong>trinken</strong> - uống
                                    </div>
                                    <div class="col-md-4">
                                        <strong>nehmen</strong> - lấy<br>
                                        <strong>haben</strong> - có<br>
                                        <strong>brauchen</strong> - cần
                                    </div>
                                </div>
                            </div>

                            <h4><i class="bi bi-chat"></i> Ví dụ thực tế:</h4>
                            <div class="example-box border-akkusativ">
                                <p>Ich lese <strong>das</strong> Buch jeden Tag.</p>
                                <small class="text-muted">Tôi đọc cuốn sách mỗi ngày. (das Buch = tân ngữ)</small>
                            </div>
                            <div class="example-box border-akkusativ">
                                <p>Er kauft <strong>den</strong> neuen Computer.</p>
                                <small class="text-muted">Anh ấy mua chiếc máy tính mới. (den Computer = tân ngữ)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DATIV CHI TIẾT -->
                <div id="dativ-detail">
                    <div class="case-header dativ-bg">
                        <h2><i class="bi bi-3-circle"></i> DATIV - BỔ NGỮ GIÁN TIẾP</h2>
                    </div>
                    
                    <div class="card case-card">
                        <div class="card-body">
                            <h4><i class="bi bi-check-circle"></i> Khi nào dùng Dativ?</h4>
                            <ul>
                                <li>Là <strong>bổ ngữ gián tiếp</strong></li>
                                <li>Sau giới từ: mit, von, zu, bei, nach, aus, seit...</li>
                                <li>Trả lời câu hỏi: <strong>Wem?</strong> (Cho ai? Với ai?)</li>
                            </ul>

                            <div class="usage-rule">
                                <h5><i class="bi bi-exclamation-triangle"></i> Lưu ý:</h5>
                                <p>Tất cả các mạo từ đều <strong>thay đổi</strong> trong Dativ!</p>
                            </div>

                            <h4><i class="bi bi-table"></i> Bảng mạo từ Dativ:</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered text-center">
                                    <thead class="table-success">
                                        <tr>
                                            <th>Giống</th>
                                            <th>Xác định</th>
                                            <th>Không xác định</th>
                                            <th>Ví dụ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Đực (der)</td>
                                            <td><strong>dem</strong> Mann</td>
                                            <td>einem Mann</td>
                                            <td>Ich gebe <strong>dem</strong> Mann das Buch.</td>
                                        </tr>
                                        <tr>
                                            <td>Cái (die)</td>
                                            <td><strong>der</strong> Frau</td>
                                            <td>einer Frau</td>
                                            <td>Ich helfe <strong>der</strong> Frau.</td>
                                        </tr>
                                        <tr>
                                            <td>Trung (das)</td>
                                            <td><strong>dem</strong> Kind</td>
                                            <td>einem Kind</td>
                                            <td>Ich spreche mit <strong>dem</strong> Kind.</td>
                                        </tr>
                                        <tr>
                                            <td>Số nhiều</td>
                                            <td><strong>denen</strong> Kindern</td>
                                            <td>keinen Kindern</td>
                                            <td>Ich spiele mit <strong>denen</strong> Kindern.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h4><i class="bi bi-list"></i> Giới từ yêu cầu Dativ:</h4>
                            <div class="preposition-list">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>mit</strong> - với<br>
                                        <strong>von</strong> - từ<br>
                                        <strong>zu</strong> - đến
                                    </div>
                                    <div class="col-md-4">
                                        <strong>bei</strong> - tại<br>
                                        <strong>nach</strong> - sau<br>
                                        <strong>aus</strong> - từ (xuất xứ)
                                    </div>
                                    <div class="col-md-4">
                                        <strong>seit</strong> - từ (thời gian)<br>
                                        <strong>gegenüber</strong> - đối diện<br>
                                        <strong>außer</strong> - trừ
                                    </div>
                                </div>
                            </div>

                            <h4><i class="bi bi-chat"></i> Ví dụ thực tế:</h4>
                            <div class="example-box border-dativ">
                                <p>Ich gebe <strong>dem</strong> Lehrer das Buch.</p>
                                <small class="text-muted">Tôi đưa cuốn sách cho giáo viên. (dem Lehrer = bổ ngữ gián tiếp)</small>
                            </div>
                            <div class="example-box border-dativ">
                                <p>Ich spreche <strong>mit dem</strong> Freund.</p>
                                <small class="text-muted">Tôi nói chuyện với bạn. (mit dem Freund = giới từ + dativ)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GENITIV CHI TIẾT -->
                <div id="genitiv-detail">
                    <div class="case-header genitiv-bg">
                        <h2><i class="bi bi-4-circle"></i> GENITIV - CHỦ SỞ HỮU</h2>
                    </div>
                    
                    <div class="card case-card">
                        <div class="card-body">
                            <h4><i class="bi bi-check-circle"></i> Khi nào dùng Genitiv?</h4>
                            <ul>
                                <li>Biểu thị <strong>sở hữu</strong></li>
                                <li>Sau giới từ: wegen, trotz, während, innerhalb...</li>
                                <li>Trả lời câu hỏi: <strong>Wessen?</strong> (Của ai?)</li>
                            </ul>

                            <div class="usage-rule">
                                <h5><i class="bi bi-exclamation-triangle"></i> Lưu ý:</h5>
                                <p>Genitiv <strong>ít dùng</strong> trong giao tiếp hàng ngày, thường thay bằng "von + Dativ"</p>
                            </div>

                            <h4><i class="bi bi-table"></i> Bảng mạo từ Genitiv:</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered text-center">
                                    <thead class="table-warning">
                                        <tr>
                                            <th>Giống</th>
                                            <th>Xác định</th>
                                            <th>Không xác định</th>
                                            <th>Ví dụ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Đực (der)</td>
                                            <td><strong>des</strong> Mannes</td>
                                            <td>eines Mannes</td>
                                            <td>Das Auto <strong>des</strong> Mannes.</td>
                                        </tr>
                                        <tr>
                                            <td>Cái (die)</td>
                                            <td><strong>der</strong> Frau</td>
                                            <td>einer Frau</td>
                                            <td>Das Buch <strong>der</strong> Frau.</td>
                                        </tr>
                                        <tr>
                                            <td>Trung (das)</td>
                                            <td><strong>des</strong> Kindes</td>
                                            <td>eines Kindes</td>
                                            <td>Das Spielzeug <strong>des</strong> Kindes.</td>
                                        </tr>
                                        <tr>
                                            <td>Số nhiều</td>
                                            <td><strong>der</strong> Kinder</td>
                                            <td>keiner Kinder</td>
                                            <td>Die Bücher <strong>der</strong> Kinder.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h4><i class="bi bi-list"></i> Giới từ yêu cầu Genitiv:</h4>
                            <div class="preposition-list">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>wegen</strong> - vì<br>
                                        <strong>trotz</strong> - mặc dù<br>
                                        <strong>während</strong> - trong khi
                                    </div>
                                    <div class="col-md-6">
                                        <strong>innerhalb</strong> - bên trong<br>
                                        <strong>außerhalb</strong> - bên ngoài<br>
                                        <strong>anstatt</strong> - thay vì
                                    </div>
                                </div>
                            </div>

                            <h4><i class="bi bi-chat"></i> Ví dụ thực tế:</h4>
                            <div class="example-box border-genitiv">
                                <p>Das Auto <strong>des</strong> Lehrers ist neu.</p>
                                <small class="text-muted">Chiếc xe của giáo viên thì mới. (des Lehrers = sở hữu)</small>
                            </div>
                            <div class="example-box border-genitiv">
                                <p><strong>Wegen</strong> des Regens bleiben wir zu Hause.</p>
                                <small class="text-muted">Vì mưa nên chúng tôi ở nhà. (wegen des Regens = giới từ + genitiv)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MẸO & KINH NGHIỆM -->
                <div id="practice">
                    <div class="card case-card">
                        <div class="card-header bg-dark text-white">
                            <h3><i class="bi bi-lightbulb"></i> Mẹo & Kinh nghiệm học biến cách</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- MẸO NHỚ BIẾN CÁCH -->
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-warning text-dark">
                                            <h5><i class="bi bi-brain"></i> Mẹo nhớ biến cách</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="accordion" id="tipsAccordion">
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#tip1">
                                                            <strong>🌟 Câu thần chú N-A-D-G</strong>
                                                        </button>
                                                    </h2>
                                                    <div id="tip1" class="accordion-collapse collapse show">
                                                        <div class="accordion-body">
                                                            <p><strong>N</strong>ominativ - <strong>A</strong>kkusativ - <strong>D</strong>ativ - <strong>G</strong>enitiv</p>
                                                            <p class="mb-1"><strong>Mẹo nhớ:</strong></p>
                                                            <ul class="mb-0">
                                                                <li><strong>N</strong>gười - <strong>A</strong>i làm?</li>
                                                                <li><strong>A</strong>i - <strong>L</strong>àm gì?</li>
                                                                <li><strong>D</strong>ùng - <strong>C</strong>ho ai?</li>
                                                                <li><strong>G</strong>ì - <strong>C</strong>ủa ai?</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tip2">
                                                            <strong>🎯 Mẹo nhớ mạo từ biến cách</strong>
                                                        </button>
                                                    </h2>
                                                    <div id="tip2" class="accordion-collapse collapse">
                                                        <div class="accordion-body">
                                                            <p class="mb-2"><strong>Học theo cụm:</strong></p>
                                                            <div class="row text-center">
                                                                <div class="col-6">
                                                                    <div class="p-2 bg-light rounded mb-2">
                                                                        <small><strong>Đực (der):</strong><br>der-den-dem-des</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="p-2 bg-light rounded mb-2">
                                                                        <small><strong>Cái (die):</strong><br>die-die-der-der</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="p-2 bg-light rounded">
                                                                        <small><strong>Trung (das):</strong><br>das-das-dem-des</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="p-2 bg-light rounded">
                                                                        <small><strong>Số nhiều (die):</strong><br>die-die-denen-der</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tip3">
                                                            <strong>💡 Mẹo chọn biến cách nhanh</strong>
                                                        </button>
                                                    </h2>
                                                    <div id="tip3" class="accordion-collapse collapse">
                                                        <div class="accordion-body">
                                                            <ul>
                                                                <li><strong>Nominativ:</strong> Chủ ngữ - Ai làm gì? → Không đổi mạo từ</li>
                                                                <li><strong>Akkusativ:</strong> Tân ngữ - Làm gì? → Chỉ "der" → "den"</li>
                                                                <li><strong>Dativ:</strong> Cho ai? Với ai? → Tất cả mạo từ đều đổi</li>
                                                                <li><strong>Genitiv:</strong> Của ai? → Ít dùng, có thể thay bằng "von + Dativ"</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- KINH NGHIỆM THỰC TẾ -->
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-info text-white">
                                            <h5><i class="bi bi-stars"></i> Kinh nghiệm thực tế</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="accordion" id="experienceAccordion">
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#exp1">
                                                            <strong>🎯 Khi nói chuyện hàng ngày</strong>
                                                        </button>
                                                    </h2>
                                                    <div id="exp1" class="accordion-collapse collapse show">
                                                        <div class="accordion-body">
                                                            <ul>
                                                                <li><strong>Ưu tiên Akkusativ và Dativ</strong> - Dùng nhiều nhất</li>
                                                                <li><strong>Genitiv ít dùng</strong> - Thay bằng "von + Dativ"</li>
                                                                <li><strong>Nếu không chắc</strong> - Dùng Dativ, thường đúng hơn</li>
                                                                <li><strong>Luyện phản xạ</strong> - Đặt câu hỏi Wer/Was? Wen/Was? Wem? Wessen?</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#exp2">
                                                            <strong>📚 Khi học ngữ pháp</strong>
                                                        </button>
                                                    </h2>
                                                    <div id="exp2" class="accordion-collapse collapse">
                                                        <div class="accordion-body">
                                                            <ul>
                                                                <li><strong>Học động từ theo biến cách</strong> - Nhóm động từ yêu cầu từng biến cách</li>
                                                                <li><strong>Học giới từ theo biến cách</strong> - Nhóm giới từ Dativ, Akkusativ, Genitiv</li>
                                                                <li><strong>Viết câu mẫu</strong> - Mỗi ngày 5 câu với các biến cách khác nhau</li>
                                                                <li><strong>Dùng flashcard</strong> - Ghi động từ + biến cách cần dùng</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#exp3">
                                                            <strong>⚡ Mẹo kiểm tra nhanh</strong>
                                                        </button>
                                                    </h2>
                                                    <div id="exp3" class="accordion-collapse collapse">
                                                        <div class="accordion-body">
                                                            <p><strong>3 bước kiểm tra:</strong></p>
                                                            <ol>
                                                                <li><strong>Đặt câu hỏi:</strong> Wer/Was? → Nominativ</li>
                                                                <li><strong>Đặt câu hỏi:</strong> Wen/Was? → Akkusativ</li>
                                                                <li><strong>Đặt câu hỏi:</strong> Wem? → Dativ</li>
                                                                <li><strong>Đặt câu hỏi:</strong> Wessen? → Genitiv</li>
                                                            </ol>
                                                            <div class="alert alert-success p-2 mt-2 mb-0">
                                                                <small><strong>Ví dụ:</strong> "Ich gebe dem Mann das Buch" → Wem gebe ich das Buch? → dem Mann → Dativ</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- BẢNG TỔNG HỢP NHANH -->
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <h5><i class="bi bi-table"></i> Bảng tổng hợp nhanh</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered text-center">
                                                    <thead class="table-dark">
                                                        <tr>
                                                            <th>Biến cách</th>
                                                            <th>Câu hỏi</th>
                                                            <th>Dùng khi</th>
                                                            <th>Đổi mạo từ?</th>
                                                            <th>Ví dụ động từ</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr class="table-primary">
                                                            <td><strong>Nominativ</strong></td>
                                                            <td>Wer? Was?</td>
                                                            <td>Chủ ngữ</td>
                                                            <td>Không</td>
                                                            <td>sein, werden</td>
                                                        </tr>
                                                        <tr class="table-danger">
                                                            <td><strong>Akkusativ</strong></td>
                                                            <td>Wen? Was?</td>
                                                            <td>Tân ngữ trực tiếp</td>
                                                            <td>Chỉ der → den</td>
                                                            <td>sehen, kaufen, hören</td>
                                                        </tr>
                                                        <tr class="table-success">
                                                            <td><strong>Dativ</strong></td>
                                                            <td>Wem?</td>
                                                            <td>Bổ ngữ gián tiếp</td>
                                                            <td>Tất cả đổi</td>
                                                            <td>geben, helfen, danken</td>
                                                        </tr>
                                                        <tr class="table-warning">
                                                            <td><strong>Genitiv</strong></td>
                                                            <td>Wessen?</td>
                                                            <td>Chủ sở hữu</td>
                                                            <td>Tất cả đổi</td>
                                                            <td>wegen, trotz</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>                           
                                        </div>
                                        <div class="card-footer text-muted text-center">
                                            <small>Mọi công cụ đều không thể giúp bạn tốt hơn nếu bản thân bạn không cố gắng, 
                                                hãy cố gắng hết sức mình để đạt được điều bạn mong muốn.</small>
                                            <big class="text-success"><strong>Viel Erfolg beim Studium!</strong></big>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll for sidebar links
        document.querySelectorAll('.list-group-item').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    targetElement.scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Highlight current section in sidebar
        window.addEventListener('scroll', function() {
            const sections = ['overview', 'nominativ-detail', 'akkusativ-detail', 'dativ-detail', 'genitiv-detail', 'practice'];
            const scrollPosition = window.scrollY + 200;

            sections.forEach(sectionId => {
                const section = document.getElementById(sectionId) || document.querySelector(`#${sectionId}`);
                if (section) {
                    const offsetTop = section.offsetTop;
                    const offsetHeight = section.offsetHeight;
                    
                    if (scrollPosition >= offsetTop && scrollPosition < offsetTop + offsetHeight) {
                        // Remove active class from all links
                        document.querySelectorAll('.list-group-item').forEach(link => {
                            link.classList.remove('active');
                        });
                        // Add active class to current link
                        const currentLink = document.querySelector(`[href="#${sectionId}"]`);
                        if (currentLink) {
                            currentLink.classList.add('active');
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>