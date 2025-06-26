-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 26, 2025 lúc 03:36 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `flashcard`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `learning_status`
--

CREATE TABLE `learning_status` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vocab_id` int(11) NOT NULL,
  `status` enum('known','unknown') DEFAULT 'unknown',
  `last_reviewed` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `learning_status`
--

INSERT INTO `learning_status` (`id`, `user_id`, `vocab_id`, `status`, `last_reviewed`) VALUES
(1, 1, 1, 'known', '2025-06-26 18:55:32'),
(2, 1, 2, 'unknown', '2025-06-26 18:55:32'),
(3, 2, 8, 'unknown', '2025-06-26 19:47:18'),
(4, 2, 5, 'known', '2025-06-26 19:47:24');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notebooks`
--

CREATE TABLE `notebooks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `notebooks`
--

INSERT INTO `notebooks` (`id`, `user_id`, `title`, `description`, `created_at`) VALUES
(1, 1, 'Sổ tay mẫu', 'Từ vựng cơ bản', '2025-06-26 18:55:32'),
(3, 2, 'A1', 'Gáng học đê', '2025-06-26 18:58:30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `created_at`) VALUES
(1, 'demo@email.com', '$2y$10$QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiop', '2025-06-26 18:55:32'),
(2, 'duycong2580@gmail.com', '$2y$10$jKKyl3L99dXIvYWzHTPTde505fd40ftych9ZIGZSUzQilcJY7bYlu', '2025-06-26 18:57:04');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vocabularies`
--

CREATE TABLE `vocabularies` (
  `id` int(11) NOT NULL,
  `notebook_id` int(11) NOT NULL,
  `word` varchar(255) NOT NULL,
  `phonetic` varchar(255) DEFAULT NULL,
  `meaning` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `plural` varchar(255) DEFAULT NULL,
  `genus` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `vocabularies`
--

INSERT INTO `vocabularies` (`id`, `notebook_id`, `word`, `phonetic`, `meaning`, `note`, `plural`, `genus`, `created_at`) VALUES
(1, 1, 'Haus', 'haʊs', 'nhà', '', 'Häuser', 'das', '2025-06-26 18:55:32'),
(2, 1, 'Baum', 'baʊm', 'cây', '', 'Bäume', 'der', '2025-06-26 18:55:32'),
(5, 3, 'der Vater', '[ˈfaːtɐ]', 'Cha', '', 'Die Väter', 'der', '2025-06-26 19:08:27'),
(6, 3, 'die Mutter', '[ˈmʊtɐ]', 'Mẹ', '', 'Die Mütter', 'die', '2025-06-26 19:08:27'),
(7, 3, 'die Eltern', '[ˈɛltɐn]', 'Cha mẹ', 'Luôn số nhiều', '-', 'die', '2025-06-26 19:08:27'),
(8, 3, 'der Bruder', '[ˈbruːdɐ]', 'Anh/em trai', '', 'Die Brüder', 'der', '2025-06-26 19:08:27'),
(9, 3, 'die Schwester', '[ˈʃvɛstɐ]', 'Chị/em gái', '', 'Die Schwestern', 'die', '2025-06-26 19:08:27'),
(10, 3, 'die Geschwister', '[ɡəˈʃvɪstɐ]', 'Anh chị em', 'Luôn số nhiều', '-', 'die', '2025-06-26 19:08:27'),
(11, 3, 'der Großvater/Opa', '[ˈɡʁoːsˌfaːtɐ]/[ˈoːpa]', 'Ông', '', 'Die Großväter/Opas', 'der', '2025-06-26 19:08:27'),
(12, 3, 'die Großmutter/Oma', '[ˈɡʁoːsˌmʊtɐ]/[ˈoːma]', 'Bà', '', 'Die Großmütter/Omas', 'die', '2025-06-26 19:08:27'),
(13, 3, 'die Großeltern', '[ˈɡʁoːsˌɛltɐn]', 'Ông bà', 'Luôn số nhiều', '-', 'die', '2025-06-26 19:08:27'),
(14, 3, 'der Onkel', '[ˈɔŋkl̩]', 'Chú/bác/cậu', '', 'Die Onkel', 'der', '2025-06-26 19:08:27'),
(15, 3, 'die Tante', '[ˈtantə]', 'Cô/dì/thím/mợ', '', 'Die Tanten', 'die', '2025-06-26 19:08:27'),
(16, 3, 'der Cousin', '[kuˈzɛ̃ː] hoặc [kuˈzɛŋ]', 'Anh/em họ', '', 'Die Cousins', 'der', '2025-06-26 19:08:27'),
(17, 3, 'die Cousine', '[kuˈziːnə]', 'Chị/em họ', '', 'Die Cousinen', 'die', '2025-06-26 19:08:27'),
(18, 3, 'der Familienstammbaum', '[faˈmiːli̯ənˌʃtamˌbaʊm]', 'Cây gia phả', '', 'Die Familienstammbäume', 'der', '2025-06-26 19:08:27'),
(19, 3, 'Hallo', '[haˈloː]', 'Xin chào', 'Chào thân mật', '-', '-', '2025-06-26 19:08:27'),
(20, 3, 'Guten Morgen', '[ˈɡuːtn̩ ˈmɔʁɡn̩]', 'Chào buổi sáng', '-', '-', '-', '2025-06-26 19:08:27'),
(21, 3, 'Guten Tag', '[ˈɡuːtn̩ taːk]', 'Chào buổi trưa/chiều', '-', '-', '-', '2025-06-26 19:08:27'),
(22, 3, 'Guten Abend', '[ˈɡuːtn̩ ˈaːbənt]', 'Chào buổi tối', '-', '-', '-', '2025-06-26 19:08:27'),
(23, 3, 'Gute Nacht', '[ˈɡuːtə naxt]', 'Chúc ngủ ngon', '-', '-', '-', '2025-06-26 19:08:27'),
(24, 3, 'Tschüss', '[tʃʏs]', 'Tạm biệt', 'Thân mật', '-', '-', '2025-06-26 19:08:27'),
(25, 3, 'Auf Wiedersehen', '[aʊ̯f ˈviːdɐˌzeːən]', 'Tạm biệt (trang trọng)', '-', '-', '-', '2025-06-26 19:08:27'),
(26, 3, 'Wie geht\'s?', '[viː ɡeːts]', 'Bạn khỏe không?', '-', '-', '-', '2025-06-26 19:08:27'),
(27, 3, 'Danke', '[ˈdaŋkə]', 'Cảm ơn', '-', '-', '-', '2025-06-26 19:08:27'),
(28, 3, 'Bitte', '[ˈbɪtə]', 'Làm ơn/Không có gì', '-', '-', '-', '2025-06-26 19:08:27'),
(29, 3, 'Freizeit', '[ˈfʁaɪ̯tsaɪ̯t]', 'Thời gian rảnh', '', 'Die Freizeiten', 'die', '2025-06-26 19:08:27'),
(30, 3, 'Hobby', '[ˈhɔbi]', 'Sở thích', '', 'Die Hobbys', 'das', '2025-06-26 19:08:27'),
(31, 3, 'schwimmen', '[ˈʃvɪmən]', 'Bơi', 'Động từ', '-', '-', '2025-06-26 19:08:27'),
(32, 3, 'lesen', '[ˈleːzn̩]', 'Đọc sách', 'Động từ', '-', '-', '2025-06-26 19:08:27'),
(33, 3, 'Musik hören', '[muˈziːk ˈhøːʁən]', 'Nghe nhạc', 'Động từ', '-', '-', '2025-06-26 19:08:27'),
(34, 3, 'Rad fahren', '[ʁaːt ˈfaːʁən]', 'Đi xe đạp', 'Động từ', '-', '-', '2025-06-26 19:08:27'),
(35, 3, 'kochen', '[ˈkɔxn̩]', 'Nấu ăn', 'Động từ', '-', '-', '2025-06-26 19:08:27'),
(36, 3, 'spielen', '[ˈʃpiːlən]', 'Chơi (thể thao, nhạc cụ,...)', 'Động từ', '-', '-', '2025-06-26 19:08:27'),
(37, 3, 'fotografieren', '[fotoɡʁaˈfiːʁən]', 'Chụp ảnh', 'Động từ', '-', '-', '2025-06-26 19:08:27'),
(38, 3, 'reisen', '[ˈʁaɪ̯zn̩]', 'Du lịch', 'Động từ', '-', '-', '2025-06-26 19:08:27'),
(39, 3, 'Yoga machen', '[ˈjoːɡa ˈmaxən]', 'Tập yoga', 'Động từ', '-', '-', '2025-06-26 19:08:27'),
(40, 3, 'Wie geht\'s?', '[viː ɡeːts]', 'Khỏe không?', 'Chào hỏi, thân mật', '-', '-', '2025-06-26 19:08:27'),
(41, 3, 'Wie geht es Ihnen?', '[viː ɡeːt ɛs ˈiːnən]', 'Ông/bà khỏe không?', 'Trang trọng', '-', '-', '2025-06-26 19:08:27'),
(42, 3, 'Wie geht es dir?', '[viː ɡeːt ɛs diːɐ̯]', 'Bạn khỏe không?', 'Thân mật', '-', '-', '2025-06-26 19:08:27'),
(43, 3, 'Mir geht es gut', '[miːɐ̯ ɡeːt ɛs ɡuːt]', 'Tôi khỏe', 'Trả lời', '-', '-', '2025-06-26 19:08:27'),
(44, 3, 'Mir geht es sehr gut', '[miːɐ̯ ɡeːt ɛs zeːɐ̯ ɡuːt]', 'Tôi rất khỏe', 'Trả lời', '-', '-', '2025-06-26 19:08:27'),
(45, 3, 'Super', '[ˈzuːpɐ]', 'Tuyệt!', 'Trả lời', '-', '-', '2025-06-26 19:08:27'),
(46, 3, 'Wunderbar', '[ˈvʊndɐbaːɐ̯]', 'Rất tuyệt', 'Trả lời', '-', '-', '2025-06-26 19:08:27'),
(47, 3, 'Ausgezeichnet', '[ˈaʊ̯sɡəˌtsaɪ̯çnət]', 'Xuất sắc', 'Trả lời', '-', '-', '2025-06-26 19:08:27'),
(48, 3, 'Toll', '[tɔl]', 'Tuyệt vời', 'Trả lời', '-', '-', '2025-06-26 19:08:27'),
(49, 3, 'Prima', '[ˈpriːma]', 'Rất ổn', 'Trả lời', '-', '-', '2025-06-26 19:08:27'),
(50, 3, 'Fantastisch', '[fanˈtastɪʃ]', 'Tuyệt diệu', 'Trả lời', '-', '-', '2025-06-26 19:08:27'),
(51, 3, 'Es geht', '[ɛs ɡeːt]', 'Cũng được', 'Trả lời', '-', '-', '2025-06-26 19:08:27'),
(52, 3, 'Mir geht es schlecht', '[miːɐ̯ ɡeːt ɛs ʃlɛçt]', 'Tôi không khỏe', 'Trả lời', '-', '-', '2025-06-26 19:08:27'),
(53, 3, 'Mir geht es nicht gut', '[miːɐ̯ ɡeːt ɛs nɪçt ɡuːt]', 'Tôi không khỏe', 'Trả lời', '-', '-', '2025-06-26 19:08:27'),
(54, 3, 'Mir geht es sehr schlecht', '[miːɐ̯ ɡeːt ɛs zeːɐ̯ ʃlɛçt]', 'Tôi rất, rất tệ', 'Trả lời', '-', '-', '2025-06-26 19:08:27'),
(55, 3, 'Miserabel', '[mizəˈʁaːbl̩]', 'Khổ sở', 'Trả lời', '-', '-', '2025-06-26 19:08:27'),
(56, 3, 'Furchtbar', '[ˈfʊʁçtbaːɐ̯]', 'Khủng khiếp', 'Trả lời', '-', '-', '2025-06-26 19:08:27'),
(57, 3, 'Ich bin müde', '[ɪç bɪn ˈmyːdə]', 'Tôi mệt', 'Trạng thái', '-', '-', '2025-06-26 19:08:27'),
(58, 3, 'Ich bin krank', '[ɪç bɪn kraŋk]', 'Tôi ốm', 'Trạng thái', '-', '-', '2025-06-26 19:08:27'),
(59, 3, 'Und Ihnen?', '[ʊnt ˈiːnən]', 'Còn ông/bà?', 'Hỏi lại, trang trọng', '-', '-', '2025-06-26 19:08:27'),
(60, 3, 'Und dir?', '[ʊnt diːɐ̯]', 'Còn bạn?', 'Hỏi lại, thân mật', '-', '-', '2025-06-26 19:08:27'),
(61, 3, 'der Ingenieur', '[ˌɪnʒeˈni̯øːɐ̯]', 'Kỹ sư', 'Nữ: die Ingenieurin', 'die Ingenieure', 'der', '2025-06-26 19:08:27'),
(62, 3, 'der Mechaniker', '[meˈçaːnɪkɐ]', 'Thợ máy', 'Nữ: die Mechanikerin', 'die Mechaniker', 'der', '2025-06-26 19:08:27'),
(63, 3, 'der Schauspieler', '[ˈʃaʊ̯ʃpiːlɐ]', 'Diễn viên (nam)', 'Nữ: die Schauspielerin', 'die Schauspieler', 'der', '2025-06-26 19:08:27'),
(64, 3, 'der Schüler', '[ˈʃyːlɐ]', 'Học sinh (nam)', 'Nữ: die Schülerin', 'die Schüler', 'der', '2025-06-26 19:08:27'),
(65, 3, 'die Schauspielerin', '[ˈʃaʊ̯ʃpiːlɛʁɪn]', 'Diễn viên (nữ)', 'Nam: der Schauspieler', 'die Schauspielerinnen', 'die', '2025-06-26 19:08:27'),
(66, 3, 'der Reporter', '[ʁeˈpɔʁtɐ]', 'Phóng viên (nam)', 'Nữ: die Reporterin', 'die Reporter', 'der', '2025-06-26 19:08:27'),
(67, 3, 'der Friseur', '[fʁiˈzøːɐ̯]', 'Thợ tóc (nam)', 'Nữ: die Friseurin', 'die Friseure', 'der', '2025-06-26 19:08:27'),
(68, 3, 'der Architekt', '[aʁçiˈtɛkt]', 'Kiến trúc sư (nam)', 'Nữ: die Architektin', 'die Architekten', 'der', '2025-06-26 19:08:27'),
(69, 3, 'der Sekretär', '[zekʁeˈtɛːɐ̯]', 'Thư ký (nam)', 'Nữ: die Sekretärin', 'die Sekretäre', 'der', '2025-06-26 19:08:27'),
(70, 3, 'der Lehrer', '[ˈleːʁɐ]', 'Giáo viên (nam)', 'Nữ: die Lehrerin', 'die Lehrer', 'der', '2025-06-26 19:08:27'),
(71, 3, 'der Verkäufer', '[fɛɐ̯ˈkɔɪ̯fɐ]', 'Người bán hàng (nam)', 'Nữ: die Verkäuferin', 'die Verkäufer', 'der', '2025-06-26 19:08:27'),
(72, 3, 'der Arzt', '[aːɐ̯tst]', 'Bác sĩ (nam)', 'Nữ: die Ärztin', 'die Ärzte', 'der', '2025-06-26 19:08:27'),
(73, 3, 'der Kellner', '[ˈkɛl.nɐ]', 'Nhân viên phục vụ (nam)', 'Nữ: die Kellnerin', 'die Kellner', 'der', '2025-06-26 19:08:27'),
(74, 3, 'der Krankenpfleger', '[ˈkʁaŋkn̩ˌfleːɡɐ]', 'Điều dưỡng (nam)', 'Nữ: die Krankenschwester', 'die Krankenpfleger', 'der', '2025-06-26 19:08:27'),
(75, 3, 'der Pilot', '[piˈloːt]', 'Phi công (nam)', 'Nữ: die Pilotin', 'die Piloten', 'der', '2025-06-26 19:08:27'),
(76, 3, 'die Flugbegleiterin', '[ˈfluːk.bəˌɡlaɪ̯.tə.ʁɪn]', 'Tiếp viên (nữ)', 'Nam: der Flugbegleiter', 'die Flugbegleiterinnen', 'die', '2025-06-26 19:08:27'),
(77, 3, 'der Programmierer', '[pʁoɡʁamiˈʁɐ]', 'Lập trình viên (nam)', 'Nữ: die Programmiererin', 'die Programmierer', 'der', '2025-06-26 19:08:27');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `learning_status`
--
ALTER TABLE `learning_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `vocab_id` (`vocab_id`);

--
-- Chỉ mục cho bảng `notebooks`
--
ALTER TABLE `notebooks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `vocabularies`
--
ALTER TABLE `vocabularies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notebook_id` (`notebook_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `learning_status`
--
ALTER TABLE `learning_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `notebooks`
--
ALTER TABLE `notebooks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `vocabularies`
--
ALTER TABLE `vocabularies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `learning_status`
--
ALTER TABLE `learning_status`
  ADD CONSTRAINT `learning_status_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `learning_status_ibfk_2` FOREIGN KEY (`vocab_id`) REFERENCES `vocabularies` (`id`);

--
-- Các ràng buộc cho bảng `notebooks`
--
ALTER TABLE `notebooks`
  ADD CONSTRAINT `notebooks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `vocabularies`
--
ALTER TABLE `vocabularies`
  ADD CONSTRAINT `vocabularies_ibfk_1` FOREIGN KEY (`notebook_id`) REFERENCES `notebooks` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
