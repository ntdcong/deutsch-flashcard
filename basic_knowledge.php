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
    <title>Kiến Thức Cơ Bản - GERMANLY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(to right, rgb(90, 97, 229), rgb(123, 244, 224));
            --card-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
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
        
        .section-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            border-radius: 15px;
        }
        
        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .section-header {
            background: var(--primary-gradient);
            color: white;
            padding: 15px 20px;
            border-radius: 15px 15px 0 0 !important;
            cursor: pointer;
            user-select: none;
        }
        
        .section-content {
            padding: 20px;
            display: none;
        }
        
        .alphabet-table td, .numbers-table td {
            text-align: center;
            padding: 12px !important;
            font-size: 1.2rem;
            font-weight: 500;
        }
        
        .number-example {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 10px 0;
            border-radius: 0 8px 8px 0;
        }
        
        .sticky-sidebar {
            position: sticky;
            top: 20px;
        }
        
        @media (max-width: 768px) {
            .section-header {
                font-size: 1.1rem;
                padding: 12px 15px;
            }
            
            .alphabet-table td, .numbers-table td {
                font-size: 1rem;
                padding: 8px !important;
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
                            <a href="#alphabet" class="list-group-item list-group-item-action">
                                <i class="bi bi-fonts"></i> Bảng chữ cái
                            </a>
                            <a href="#numbers" class="list-group-item list-group-item-action">
                                <i class="bi bi-123"></i> Các con số
                            </a>
                            <a href="#pronouns" class="list-group-item list-group-item-action">
                                <i class="bi bi-people"></i> Đại từ nhân xưng
                            </a>
                            <a href="#greetings" class="list-group-item list-group-item-action">
                                <i class="bi bi-hand-thumbs-up"></i> Chào hỏi cơ bản
                            </a>
                            <a href="#questions" class="list-group-item list-group-item-action">
                                <i class="bi bi-question"></i> Câu hỏi nghi vấn cơ bản
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="row">
                    <!-- BẢNG CHỮ CÁI -->
                    <div class="col-12 mb-4">
                        <div class="card section-card">
                            <div class="card-header section-header" onclick="toggleSection('alphabet')">
                                <h4 class="mb-0">
                                    <i class="bi bi-fonts"></i> Bảng chữ cái tiếng Đức
                                </h4>
                            </div>
                            <div id="alphabet" class="section-content">
                                <div class="table-responsive">
                                    <table class="table table-bordered alphabet-table">
                                        <thead class="table-primary">
                                            <tr>
                                                <th>Chữ cái</th>
                                                <th>Phát âm</th>
                                                <th>Ví dụ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td>A a</td><td>[aː]</td><td><strong>A</strong>uto</td></tr>
                                            <tr><td>B b</td><td>[beː]</td><td><strong>B</strong>ett</td></tr>
                                            <tr><td>C c</td><td>[tseː]</td><td><strong>C</strong>hemie</td></tr>
                                            <tr><td>D d</td><td>[deː]</td><td><strong>D</strong>ank</td></tr>
                                            <tr><td>E e</td><td>[eː]</td><td><strong>E</strong>sel</td></tr>
                                            <tr><td>F f</td><td>[ɛf]</td><td><strong>F</strong>rau</td></tr>
                                            <tr><td>G g</td><td>[geː]</td><td><strong>G</strong>eld</td></tr>
                                            <tr><td>H h</td><td>[haː]</td><td><strong>H</strong>aus</td></tr>
                                            <tr><td>I i</td><td>[iː]</td><td><strong>I</strong>gel</td></tr>
                                            <tr><td>J j</td><td>[jɔt]</td><td><strong>J</strong>unge</td></tr>
                                            <tr><td>K k</td><td>[kaː]</td><td><strong>K</strong>ind</td></tr>
                                            <tr><td>L l</td><td>[ɛl]</td><td><strong>L</strong>icht</td></tr>
                                            <tr><td>M m</td><td>[ɛm]</td><td><strong>M</strong>ann</td></tr>
                                            <tr><td>N n</td><td>[ɛn]</td><td><strong>N</strong>acht</td></tr>
                                            <tr><td>O o</td><td>[oː]</td><td><strong>O</strong>pa</td></tr>
                                            <tr><td>P p</td><td>[peː]</td><td><strong>P</strong>ilz</td></tr>
                                            <tr><td>Q q</td><td>[kuː]</td><td><strong>Q</strong>uecke</td></tr>
                                            <tr><td>R r</td><td>[ɛʁ]</td><td><strong>R</strong>ad</td></tr>
                                            <tr><td>S s</td><td>[ɛs]</td><td><strong>S</strong>onne</td></tr>
                                            <tr><td>ß</td><td>[ɛs-tset]</td><td>Stra<strong>ß</strong>e</td></tr>
                                            <tr><td>T t</td><td>[teː]</td><td><strong>T</strong>isch</td></tr>
                                            <tr><td>U u</td><td>[uː]</td><td><strong>U</strong>hr</td></tr>
                                            <tr><td>V v</td><td>[faʊ]</td><td><strong>V</strong>ater</td></tr>
                                            <tr><td>W w</td><td>[veː]</td><td><strong>W</strong>agen</td></tr>
                                            <tr><td>X x</td><td>[ɪks]</td><td>Se<strong>x</strong></td></tr>
                                            <tr><td>Y y</td><td>[ʏpsilon]</td><td><strong>Y</strong>oga</td></tr>
                                            <tr><td>Z z</td><td>[tset]</td><td><strong>Z</strong>ug</td></tr>
                                            <tr><td>Ä ä</td><td>[ɛː]</td><td>B<strong>ä</strong>ren</td></tr>
                                            <tr><td>Ö ö</td><td>[øː]</td><td><strong>Ö</strong>l</td></tr>
                                            <tr><td>Ü ü</td><td>[yː]</td><td><strong>Ü</strong>ber</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CÁC CON SỐ -->
                    <div class="col-12 mb-4">
                        <div class="card section-card">
                            <div class="card-header section-header" onclick="toggleSection('numbers')">
                                <h4 class="mb-0">
                                    <i class="bi bi-123"></i> Các con số cơ bản
                                </h4>
                            </div>
                            <div id="numbers" class="section-content">
                                <h5>Số từ 1 đến 30:</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered numbers-table">
                                        <thead class="table-success">
                                            <tr>
                                                <th>Số</th>
                                                <th>Tiếng Đức</th>
                                                <th>Phát âm</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td>1</td><td>eins</td><td>[aɪ̯ns]</td></tr>
                                            <tr><td>2</td><td>zwei</td><td>[t͡svaɪ̯]</td></tr>
                                            <tr><td>3</td><td>drei</td><td>[dʁaɪ̯]</td></tr>
                                            <tr><td>4</td><td>vier</td><td>[fiːɐ̯]</td></tr>
                                            <tr><td>5</td><td>fünf</td><td>[fʏnf]</td></tr>
                                            <tr><td>6</td><td>sechs</td><td>[zɛks]</td></tr>
                                            <tr><td>7</td><td>sieben</td><td>[ˈziːbən]</td></tr>
                                            <tr><td>8</td><td>acht</td><td>[axt]</td></tr>
                                            <tr><td>9</td><td>neun</td><td>[nɔʏn]</td></tr>
                                            <tr><td>10</td><td>zehn</td><td>[t͡seːn]</td></tr>
                                            <tr><td>11</td><td>elf</td><td>[ɛlf]</td></tr>
                                            <tr><td>12</td><td>zwölf</td><td>[t͡svœlf]</td></tr>
                                            <tr><td>13</td><td>dreizehn</td><td>[ˈdʁaɪ̯t͡saɪ̯n]</td></tr>
                                            <tr><td>14</td><td>vierzehn</td><td>[ˈfiːɐ̯t͡saɪ̯n]</td></tr>
                                            <tr><td>15</td><td>fünfzehn</td><td>[ˈfʏnfˌt͡saɪ̯n]</td></tr>
                                            <tr><td>16</td><td>sechzehn</td><td>[ˈzɛkˌt͡saɪ̯n]</td></tr>
                                            <tr><td>17</td><td>siebzehn</td><td>[ˈziːbˌt͡saɪ̯n]</td></tr>
                                            <tr><td>18</td><td>achtzehn</td><td>[ˈaxˌt͡saɪ̯n]</td></tr>
                                            <tr><td>19</td><td>neunzehn</td><td>[ˈnɔʏnˌt͡saɪ̯n]</td></tr>
                                            <tr><td>20</td><td>zwanzig</td><td>[ˈt͡svantsɪç]</td></tr>
                                            <tr><td>21</td><td>einundzwanzig</td><td>[ˈaɪ̯nʊntˈt͡svantsɪç]</td></tr>
                                            <tr><td>22</td><td>zweiundzwanzig</td><td>[ˈt͡svaɪ̯ʊntˈt͡svantsɪç]</td></tr>
                                            <tr><td>23</td><td>dreiundzwanzig</td><td>[ˈdʁaɪ̯ʊntˈt͡svantsɪç]</td></tr>
                                            <tr><td>24</td><td>vierundzwanzig</td><td>[ˈfiːɐ̯ʊntˈt͡svantsɪç]</td></tr>
                                            <tr><td>25</td><td>fünfundzwanzig</td><td>[ˈfʏnfʊntˈt͡svantsɪç]</td></tr>
                                            <tr><td>26</td><td>sechsundzwanzig</td><td>[ˈzɛksʊntˈt͡svantsɪç]</td></tr>
                                            <tr><td>27</td><td>siebenundzwanzig</td><td>[ˈziːbənʊntˈt͡svantsɪç]</td></tr>
                                            <tr><td>28</td><td>achtundzwanzig</td><td>[ˈaxʊntˈt͡svantsɪç]</td></tr>
                                            <tr><td>29</td><td>neunundzwanzig</td><td>[ˈnɔʏnʊntˈt͡svantsɪç]</td></tr>
                                            <tr><td>30</td><td>dreißig</td><td>[ˈdʁaɪ̯sɪç]</td></tr>
                                        </tbody>
                                    </table>
                                </div>

                                <h5 class="mt-4">Số hàng chục:</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="number-example">
                                            <strong>40</strong> - vierzig [ˈfiːɐ̯tsɪç]
                                        </div>
                                        <div class="number-example">
                                            <strong>50</strong> - fünfzig [ˈfʏnf-tsɪç]
                                        </div>
                                        <div class="number-example">
                                            <strong>60</strong> - sechzig [ˈzɛk-tsɪç]
                                        </div>
                                        <div class="number-example">
                                            <strong>70</strong> - siebzig [ˈziːp-tsɪç]
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="number-example">
                                            <strong>80</strong> - achtzig [ˈax-tsɪç]
                                        </div>
                                        <div class="number-example">
                                            <strong>90</strong> - neunzig [ˈnɔʏn-tsɪç]
                                        </div>
                                        <div class="number-example">
                                            <strong>100</strong> - hundert [ˈhʊndɐt]
                                        </div>
                                    </div>
                                </div>

                                <h5 class="mt-4">Số lớn:</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="number-example">
                                            <strong>1.000</strong> - tausend [ˈtaʊ̯zant]
                                        </div>
                                        <div class="number-example">
                                            <strong>10.000</strong> - zehntausend [ˈt͡seːnˌtaʊ̯zant]
                                        </div>
                                        <div class="number-example">
                                            <strong>100.000</strong> - hunderttausend [ˈhʊndɐtˌtaʊ̯zant]
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="number-example">
                                            <strong>1.000.000</strong> - eine Million [ˈmiːli̯oːn]
                                        </div>
                                        <div class="number-example">
                                            <strong>10.000.000</strong> - zehn Millionen [ˈt͡seːn miːli̯oːnən]
                                        </div>
                                        <div class="number-example">
                                            <strong>100.000.000</strong> - hundert Millionen [ˈhʊndɐt miːli̯oːnən]
                                        </div>
                                        <div class="number-example">
                                            <strong>1.000.000.000</strong> - eine Milliarde [miːli̯ˈaːrdə]
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
             
                    <!-- ĐẠI TỪ NHÂN XƯNG -->
                    <div class="col-12 mb-4">
                        <div class="card section-card">
                            <div class="card-header section-header" onclick="toggleSection('pronouns')">
                                <h4 class="mb-0">
                                    <i class="bi bi-people"></i> Đại từ nhân xưng
                                </h4>
                            </div>
                            <div id="pronouns" class="section-content">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Ngôi</th>
                                                <th>Tiếng Đức</th>
                                                <th>Nghĩa</th>
                                                <th>Ví dụ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Tôi</td>
                                                <td>ich</td>
                                                <td>tôi</td>
                                                <td><strong>Ich</strong> lerne Deutsch.</td>
                                            </tr>
                                            <tr>
                                                <td>Bạn/Cậu</td>
                                                <td>du</td>
                                                <td>bạn (thân mật)</td>
                                                <td><strong>Du</strong> bist nett.</td>
                                            </tr>
                                            <tr>
                                                <td>Anh/Chị/Quý khách</td>
                                                <td>Sie</td>
                                                <td>bạn (trang trọng)</td>
                                                <td><strong>Sie</strong> sprechen gut.</td>
                                            </tr>
                                            <tr>
                                                <td>Anh ấy</td>
                                                <td>er</td>
                                                <td>anh ấy</td>
                                                <td><strong>Er</strong> arbeitet.</td>
                                            </tr>
                                            <tr>
                                                <td>Cô ấy</td>
                                                <td>sie</td>
                                                <td>cô ấy</td>
                                                <td><strong>Sie</strong> liest ein Buch.</td>
                                            </tr>
                                            <tr>
                                                <td>Nó</td>
                                                <td>es</td>
                                                <td>nó (vật)</td>
                                                <td><strong>Es</strong> regnet.</td>
                                            </tr>
                                            <tr>
                                                <td>Chúng ta</td>
                                                <td>wir</td>
                                                <td>chúng ta</td>
                                                <td><strong>Wir</strong> sind Freunde.</td>
                                            </tr>
                                            <tr>
                                                <td>Các bạn</td>
                                                <td>ihr</td>
                                                <td>các bạn</td>
                                                <td><strong>Ihr</strong> seid klug.</td>
                                            </tr>
                                            <tr>
                                                <td>Họ</td>
                                                <td>sie</td>
                                                <td>họ</td>
                                                <td><strong>Sie</strong> kommen aus Berlin.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CHÀO HỎI CƠ BẢN -->
                    <div class="col-12 mb-4">
                        <div class="card section-card">
                            <div class="card-header section-header" onclick="toggleSection('greetings')">
                                <h4 class="mb-0">
                                    <i class="bi bi-hand-thumbs-up"></i> Chào hỏi cơ bản
                                </h4>
                            </div>
                            <div id="greetings" class="section-content">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="number-example">
                                            <strong>Guten Morgen!</strong><br>
                                            Chào buổi sáng! [ˈɡuːtən ˈmɔʁɡən]
                                        </div>
                                        <div class="number-example">
                                            <strong>Guten Tag!</strong><br>
                                            Chào buổi ngày! [ˈɡuːtən taːk]
                                        </div>
                                        <div class="number-example">
                                            <strong>Guten Abend!</strong><br>
                                            Chào buổi tối! [ˈɡuːtən ˈaːbənt]
                                        </div>
                                        <div class="number-example">
                                            <strong>Gute Nacht!</strong><br>
                                            Chúc ngủ ngon! [ˈɡuːtə naxt]
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="number-example">
                                            <strong>Hallo!</strong><br>
                                            Xin chào! [ˈhaloː]
                                        </div>
                                        <div class="number-example">
                                            <strong>Tschüss!</strong><br>
                                            Tạm biệt! [tʃʏs]
                                        </div>
                                        <div class="number-example">
                                            <strong>Wie geht's?</strong><br>
                                            Bạn khỏe không? [viː ɡɛt's]
                                        </div>
                                        <div class="number-example">
                                            <strong>Bitte schön!</strong><br>
                                            Không có gì! [ˈbɪtə ʃøːn]
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CÂU HỎI NGHI VẤN -->
                    <div class="col-12 mb-4">
                        <div class="card section-card">
                            <div class="card-header section-header" onclick="toggleSection('questions')">
                                <h4 class="mb-0">
                                    <i class="bi bi-question-circle"></i> Câu hỏi nghi vấn cơ bản
                                </h4>
                            </div>
                            <div id="questions" class="section-content">
                                <p>Các từ để bắt đầu câu hỏi trong tiếng Đức:</p>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-primary">
                                            <tr>
                                                <th>Từ hỏi</th>
                                                <th>Nghĩa</th>
                                                <th>Dùng khi nào</th>
                                                <th>Ví dụ</th>
                                                <th>Phát âm</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>Wer</strong></td>
                                                <td>Ai</td>
                                                <td>Hỏi người (Nominativ)</td>
                                                <td><strong>Wer</strong> ist das? (Ai đó?)</td>
                                                <td>[veːɐ̯]</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Was</strong></td>
                                                <td>Cái gì</td>
                                                <td>Hỏi vật (Nominativ/Akkusativ)</td>
                                                <td><strong>Was</strong> ist das? (Cái gì đây?)</td>
                                                <td>[vas]</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Wo</strong></td>
                                                <td>Ở đâu</td>
                                                <td>Hỏi vị trí (tĩnh)</td>
                                                <td><strong>Wo</strong> bist du? (Bạn ở đâu?)</td>
                                                <td>[voː]</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Wohin</strong></td>
                                                <td>Đi đâu</td>
                                                <td>Hỏi hướng đi (chuyển động)</td>
                                                <td><strong>Wohin</strong> gehst du? (Bạn đi đâu?)</td>
                                                <td>[ˈvoːhɪn]</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Woher</strong></td>
                                                <td>Từ đâu</td>
                                                <td>Hỏi xuất xứ</td>
                                                <td><strong>Woher</strong> kommst du? (Bạn từ đâu đến?)</td>
                                                <td>[ˈvoːheːɐ̯]</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Wann</strong></td>
                                                <td>Khi nào</td>
                                                <td>Hỏi thời gian</td>
                                                <td><strong>Wann</strong> beginnt der Kurs? (Khóa học bắt đầu khi nào?)</td>
                                                <td>[van]</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Warum</strong></td>
                                                <td>Tại sao</td>
                                                <td>Hỏi lý do</td>
                                                <td><strong>Warum</strong> lernst du Deutsch? (Tại sao bạn học tiếng Đức?)</td>
                                                <td>[ˈvaːʁum]</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Wie</strong></td>
                                                <td>Như thế nào</td>
                                                <td>Hỏi cách thức, tình trạng</td>
                                                <td><strong>Wie</strong> geht's? (Bạn khỏe không?)</td>
                                                <td>[viː]</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Wie viel</strong></td>
                                                <td>Bao nhiêu (ít)</td>
                                                <td>Hỏi số lượng (không đếm được)</td>
                                                <td><strong>Wie viel</strong> kostet das? (Cái này giá bao nhiêu?)</td>
                                                <td>[viː ˈfiːl]</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Wie viele</strong></td>
                                                <td>Bao nhiêu (nhiều)</td>
                                                <td>Hỏi số lượng (đếm được)</td>
                                                <td><strong>Wie viele</strong> Bücher hast du? (Bạn có bao nhiêu sách?)</td>
                                                <td>[viː ˈfiːlə]</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Welcher</strong></td>
                                                <td>Cái nào</td>
                                                <td>Hỏi lựa chọn (có biến cách)</td>
                                                <td><strong>Welcher</strong> Stuhl ist neu? (Cái ghế nào mới?)</td>
                                                <td>[ˈvɛlçɐ]</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="usage-rule mt-3">
                                    <h5><i class="bi bi-lightbulb"></i> Mẹo nhớ Wo - Wohin - Woher:</h5>
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <div class="p-2 bg-info text-white rounded">
                                                <strong>Wo</strong> = Ở đâu (tĩnh)<br>
                                                <small>Ich bin <strong>wo</strong>? (Tôi ở đâu?)</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="p-2 bg-success text-white rounded">
                                                <strong>Wohin</strong> = Đi đâu (động)<br>
                                                <small>Ich gehe <strong>wohin</strong>? (Tôi đi đâu?)</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="p-2 bg-warning text-dark rounded">
                                                <strong>Woher</strong> = Từ đâu (xuất xứ)<br>
                                                <small>Ich komme <strong>woher</strong>? (Tôi từ đâu?)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="number-example mt-3">
                                    <h5><i class="bi bi-chat"></i> Ví dụ hội thoại:</h5>
                                    <p><strong>A:</strong> Wo wohnst du? (Bạn sống ở đâu?)</p>
                                    <p><strong>B:</strong> Ich wohne in Berlin. (Tôi sống ở Berlin.)</p>
                                    <p><strong>A:</strong> Woher kommst du? (Bạn từ đâu đến?)</p>
                                    <p><strong>B:</strong> Ich komme aus Vietnam. (Tôi từ Việt Nam đến.)</p>
                                    <p><strong>A:</strong> Wohin gehst du? (Bạn đi đâu?)</p>
                                    <p><strong>B:</strong> Ich gehe in die Schule. (Tôi đi học.)</p>
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
        // Toggle section content
        function toggleSection(sectionId) {
            const content = document.getElementById(sectionId);
            const isVisible = content.style.display === 'block';
            content.style.display = isVisible ? 'none' : 'block';
        }

        // Smooth scroll for sidebar links
        document.querySelectorAll('.list-group-item').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                // Expand section if collapsed
                if (targetElement.style.display !== 'block') {
                    toggleSection(targetId);
                }
                
                // Scroll to section
                targetElement.scrollIntoView({ behavior: 'smooth' });
            });
        });

        // Auto-expand first section on load
        window.addEventListener('load', function() {
            toggleSection('alphabet');
        });
    </script>
</body>
</html>