-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Ноя 15 2025 г., 07:10
-- Версия сервера: 8.2.0
-- Версия PHP: 8.3.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `autoservice`
--

-- --------------------------------------------------------

--
-- Структура таблицы `appointments`
--

CREATE TABLE `appointments` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `service_id` int NOT NULL,
  `mechanic_id` int NOT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `client_phone` varchar(50) DEFAULT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `appointments`
--

INSERT INTO `appointments` (`id`, `date`, `start_time`, `end_time`, `service_id`, `mechanic_id`, `client_name`, `client_phone`, `client_email`, `status`) VALUES
(1, '2025-06-27', '09:00:00', '09:00:00', 1, 0, 'Петренко Олег Николаевич', '89114849422', 'example@email.rt', 'pending'),
(2, '2025-06-27', '09:00:00', '09:00:00', 2, 0, 'Петренко Олег Николаевич', '89114849422', 'example@email.rt', 'pending'),
(3, '2025-06-27', '13:00:00', '13:00:00', 1, 0, 'S K', '89527982329', 'spk188@mail.ru', 'pending'),
(4, '2025-06-27', '14:00:00', '14:00:00', 2, 0, 'S K', '89527982329', 'spk188@mail.ru', 'pending'),
(5, '2025-06-27', '10:00:00', '10:30:00', 12, 0, 'Петренко Олег Николаевич', '89114849422', 'example@email.rt', 'pending'),
(6, '2025-06-27', '14:00:00', '14:00:00', 2, 1, 'Петренко Олег Николаевич', '89114849422', 'example@email.rt', 'pending'),
(7, '2025-06-28', '09:00:00', '09:00:00', 1, 2, 'Петренко Олег Николаевич', '89114849422', 'example@email.rt', 'pending'),
(8, '2025-06-27', '13:30:00', '13:30:00', 2, 1, 'Петренко Олег Николаевич', '89114849422', 'example@email.rt', 'pending'),
(9, '2025-06-27', '16:00:00', '16:00:00', 1, 2, 'Петренко Олег Николаевич', '89114849422', 'example@email.rt', 'pending'),
(10, '2025-07-08', '12:45:00', '12:45:00', 3, 2, 'Тихомирова Наталья Николаевна', '89024162963', '', 'pending');

-- --------------------------------------------------------

--
-- Структура таблицы `available_times`
--

CREATE TABLE `available_times` (
  `id` int NOT NULL,
  `service_id` int NOT NULL,
  `date` date NOT NULL,
  `time` varchar(5) NOT NULL,
  `booked` tinyint(1) DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `available_times`
--

INSERT INTO `available_times` (`id`, `service_id`, `date`, `time`, `booked`) VALUES
(1, 1, '2024-04-27', '09:00', 0),
(2, 1, '2024-04-27', '10:00', 0),
(3, 2, '2024-04-27', '11:00', 1),
(4, 1, '2024-04-27', '09:00', 0),
(5, 1, '2024-04-27', '10:00', 0),
(6, 1, '2024-04-27', '11:00', 0),
(7, 1, '2024-04-27', '12:00', 0),
(8, 1, '2024-04-27', '13:00', 0),
(9, 1, '2024-04-27', '14:00', 0),
(10, 1, '2024-04-27', '15:00', 0),
(11, 1, '2024-04-27', '16:00', 0),
(12, 1, '2024-04-27', '17:00', 0),
(13, 1, '2024-04-27', '18:00', 0),
(14, 2, '2024-04-27', '09:00', 0),
(15, 2, '2024-04-27', '10:00', 0),
(16, 2, '2024-04-27', '11:00', 0),
(17, 1, '2024-04-28', '09:00', 0),
(18, 1, '2024-04-28', '10:00', 0),
(19, 1, '2024-04-28', '11:00', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `bookings`
--

CREATE TABLE `bookings` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `service_id` int NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `time` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `mechanic_id` int DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `user_id` int DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `bookings`
--

INSERT INTO `bookings` (`id`, `name`, `phone`, `service_id`, `service_name`, `date`, `time`, `created_at`, `mechanic_id`, `status`, `user_id`) VALUES
(1, 'Иван Иванов', '+7 900 123-45-67', 1, 'Массаж', '2024-04-27', '10:00', '2025-06-26 11:53:08', NULL, 'pending', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `cars`
--

CREATE TABLE `cars` (
  `id` int NOT NULL,
  `client_id` int DEFAULT NULL,
  `make` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `year` year DEFAULT NULL,
  `vin` varchar(17) DEFAULT NULL,
  `license_plate` varchar(15) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `cars`
--

INSERT INTO `cars` (`id`, `client_id`, `make`, `model`, `year`, `vin`, `license_plate`) VALUES
(1, 1, 'Toyota', 'Camry', '2018', 'JTDKB20U187678901', 'А123БВ77'),
(2, 1, 'Honda', 'CR-V', '2020', '5J6RE4H45BL012345', 'В456ГД78'),
(3, 2, 'BMW', 'X5', '2019', 'WBADR634XGEL78901', 'Е789ЖЗ79'),
(4, 3, 'Volkswagen', 'Tiguan', '2021', 'WVGZZZ5NZMW543210', 'К012ЛМ80'),
(5, 4, 'Skoda', 'Octavia', '2017', 'TMBJG7NU3J3123456', 'М345НО81'),
(6, 5, 'Hyundai', 'Solaris', '2020', 'Z94CB41BAER123456', 'П678РС82'),
(7, 6, 'Kia', 'Rio', '2019', 'Z94K241B9LR765432', 'С901ТУ83'),
(8, 7, 'Lada', 'Vesta', '2022', 'XTA219120K1234567', 'У234ФХ84'),
(9, 8, 'Renault', 'Duster', '2021', 'VF1HSR5N5M6543210', 'Х567ЦЧ85'),
(10, 2, 'Audi', 'A4', '2018', 'WAUZZZ8K9HA123456', 'Ц890ШЩ86');

-- --------------------------------------------------------

--
-- Структура таблицы `change_history`
--

CREATE TABLE `change_history` (
  `id` int NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(20) NOT NULL,
  `description` text,
  `changed_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `clients`
--

CREATE TABLE `clients` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `inn` varchar(20) DEFAULT NULL,
  `kpp` varchar(20) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contract_number` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `client_type` enum('individual','legal') DEFAULT 'individual'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `clients`
--

INSERT INTO `clients` (`id`, `name`, `company_name`, `inn`, `kpp`, `contact_person`, `contract_number`, `phone`, `email`, `client_type`) VALUES
(1, 'Иванов Петр Сергеевич', NULL, NULL, NULL, NULL, NULL, '+79161234567', NULL, 'individual'),
(2, 'Сидорова Анна Владимировна', NULL, NULL, NULL, NULL, NULL, '+79162345678', NULL, 'individual'),
(3, 'Козлов Дмитрий Игоревич', NULL, NULL, NULL, NULL, NULL, '+79163456789', NULL, 'individual'),
(4, 'Петрова Мария Александровна', NULL, NULL, NULL, NULL, NULL, '+79164567890', NULL, 'individual'),
(5, 'Смирнов Алексей Викторович', NULL, NULL, NULL, NULL, NULL, '+79165678901', NULL, 'individual'),
(6, 'Федорова Елена Сергеевна', NULL, NULL, NULL, NULL, NULL, '+79166789012', NULL, 'individual'),
(7, 'Николаев Игорь Петрович', NULL, NULL, NULL, NULL, NULL, '+79167890123', NULL, 'individual'),
(8, 'Орлова Ольга Дмитриевна', NULL, NULL, NULL, NULL, NULL, '+79168901234', NULL, 'individual'),
(9, 'Балалайкин', NULL, NULL, NULL, NULL, NULL, '+7 (952) 798-23-29', NULL, 'individual'),
(10, 'Трататайкин Ива', NULL, NULL, NULL, NULL, NULL, '+78962531412', NULL, 'individual');

-- --------------------------------------------------------

--
-- Структура таблицы `companies`
--

CREATE TABLE `companies` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(50) NOT NULL,
  `director_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `companies`
--

INSERT INTO `companies` (`id`, `name`, `address`, `phone`, `director_name`) VALUES
(1, 'ООО \"Автосервис\"', 'г. Москва, ул. Автозаводская, д. 10', '+7 (495) 123-45-67', 'Иванов Иван Иванович'),
(2, 'Автосервис \"Ремонт+\"', 'г. Москва, ул. Автозаводская, 15', '+7 (495) 111-22-33', 'Петров Иван Сергеевич');

-- --------------------------------------------------------

--
-- Структура таблицы `company_details`
--

CREATE TABLE `company_details` (
  `id` int NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `legal_name` varchar(255) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `inn` varchar(20) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `kpp` varchar(20) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `ogrn` varchar(20) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `legal_address` text COLLATE utf8mb3_unicode_520_ci,
  `actual_address` text COLLATE utf8mb3_unicode_520_ci,
  `phone` varchar(50) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `bank_name` varchar(255) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `bank_account` varchar(50) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `corr_account` varchar(50) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `bic` varchar(20) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `director_name` varchar(255) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `accountant_name` varchar(255) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Дамп данных таблицы `company_details`
--

INSERT INTO `company_details` (`id`, `company_name`, `legal_name`, `inn`, `kpp`, `ogrn`, `legal_address`, `actual_address`, `phone`, `email`, `website`, `bank_name`, `bank_account`, `corr_account`, `bic`, `director_name`, `accountant_name`, `created_at`, `updated_at`) VALUES
(1, 'Зеленый коридор', 'ООО \"Зеленый коридор\"', '3906152544', '390701001', '1063906086530', '236004, Калининградская область, город Калининград, Черниговская ул., д. 16 литер I из а, кабинет 5 ', '236004, Калининградская область, город Калининград, Черниговская ул., д. 16 литер I из а, кабинет 5 ', '', '', '', '', '', '', '', '', '', '2025-11-13 06:33:15', '2025-11-13 06:33:15');

-- --------------------------------------------------------

--
-- Структура таблицы `employees`
--

CREATE TABLE `employees` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `specialization` enum('front_axis','rear_axis','all') DEFAULT 'all',
  `work_hours` varchar(50) DEFAULT '9:00-18:00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `faq`
--

CREATE TABLE `faq` (
  `id` int NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int DEFAULT '0',
  `pdf_references` text,
  `views` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `faq`
--

INSERT INTO `faq` (`id`, `question`, `answer`, `sort_order`, `pdf_references`, `views`, `is_active`) VALUES
(1, 'GSP Automotive Group', 'GSP Automotive Group — ведущий производитель автомобильных запчастей на мировом рынке послепродажного обслуживания. С 1985 года GSP производит шарниры равных угловых скоростей, приводные валы, комплекты колесных ступиц, резино-металлические детали, а также компоненты рулевого управления и подвески. Благодаря компетентной команде профессионалов компания GSP поставляет свою продукцию в более чем 120 стран мира, число которых стабильно растет. Экспертные знания помогают нашей высококвалифицированной команде с многолетним опытом работы на мировом рынке послепродажного обслуживания находить лучшие решения для каждого сегмента.', 0, NULL, 0, 1),
(2, 'KORSON', 'Продукция под маркой Korson производится компанией Treus Automotive на крупнейших заводах Турции. Обладая огромным опытом и высокой компетенцией, Treus Automotive стремится к постоянному развитию, совершенствованию и продвижению высококачественной и наукоемкой продукции на мировых рынках. География поставок охватывает как внутренний, так и внешний рынок и постоянно расширяется. Адаптация продукции к конкретным условиям эксплуатации дает возможность поставок в регионы с различным климатом и обеспечивает высокую надежность и безотказность автомобильной техники в самых сложных условиях эксплуатации. В производстве используются высококачественные базовые масла мировых брендов и пакеты присадок от ведущих поставщиков (Infineum, Lubrizol, Afton), а также собственные уникальные рецептуры и ингредиенты. Благодаря высокой предпродажной и послепродажной поддержке Treus Automotive обеспечивает высокий уровень удовлетворенности своих деловых партнеров, что позволяет компании постоянно развиваться и открывать новые горизонты. Основополагающие принципы Treus Automotive включают приверженность инновациям и исключительное качество продукции, отвечающее самым строгим международным стандартам, что позволяет производить узкоспециализированные составы масел для удовлетворения конкретных требований производителей. Особая ценность Treus Automotive — это ее сотрудники, и ее забота о своих сотрудниках и окружающей среде является главным приоритетом. Компания неукоснительно соблюдает все требования по охране труда, технике безопасности и охране окружающей среды.', 0, NULL, 0, 1),
(3, 'HENGST FILTER', 'Hengst GmbH & Co. KG - немецкая компания, специализированная на производстве фильтров и фильтрующих модулей для автомобилей и промышленных производств, основанная в 1958 г.\r\nЗаводы компании располагаются в европейских странах. Отдельное внимание компания уделяет разработке передовых систем и внедрению новых технологий в производство.\r\nПродукция Hengst имеет высокое качество и может использоваться в качестве достойной замены оригинальных запчастей. При этом, продукция компании продается в среднем диапазоне цен, что делает её привлекательной в использовании.', 0, NULL, 0, 1),
(4, 'JAPANPARTS', 'JAPANPARTS, фото, продукция, фото продукции, фото запчастей, стикер, фото стикера пример, фотография, образец\r\nJAPANPARTS - Итальянский производитель запасных частей для Японских автомобилей. Компания основана в 1988 году, специализируется на выпуске неоригинальных запасных частей широкого спектра - колодки, детали подевски, подшипники, приводные ремни, электрика, детали двигателя, комплекты сцепления. Запчасти можно характеризовать как имеющие средний срок службы и среднее качество. Запчасти этого производителя имеют повсеместное распространение и популярны среди Российских покупателей, исходя из выгодной цены.', 0, NULL, 0, 1),
(5, 'Замена помпы Chevrolet Aveo Sedan', 'Для замены нужно (запасть терпением и матами) снять правое переднее колесо, подкрылок, правую фару и опору двигателя, корпус фильтра с гофрой. Может всё снимать и не требуется, но места очень мало. На двигателе F14D4 помпа висит не на ремне ГРМ. Снимаем ремень ГУРа и генератора. Сливаем жижу из радиатора.\r\nНасос водяной (помпа)(1.6 без снятия ремня ГРМ)	5460 ₽\r\nНасос водяной (помпа)(в приводе ГРМ)	8736 ₽', 0, NULL, 0, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `faq_pdf_references`
--

CREATE TABLE `faq_pdf_references` (
  `faq_id` int DEFAULT NULL,
  `pdf_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `inspection_categories`
--

CREATE TABLE `inspection_categories` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `inspection_items`
--

CREATE TABLE `inspection_items` (
  `id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `name` varchar(200) COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `default_side` enum('left','right','both','none') COLLATE utf8mb3_unicode_520_ci DEFAULT 'none',
  `default_action` enum('repair','replace') COLLATE utf8mb3_unicode_520_ci DEFAULT 'replace',
  `typical_work_price` decimal(8,2) DEFAULT NULL,
  `typical_part_price` decimal(8,2) DEFAULT NULL,
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `issues`
--

CREATE TABLE `issues` (
  `id` int NOT NULL,
  `part_id` int NOT NULL,
  `quantity` int NOT NULL,
  `issued_to` varchar(255) NOT NULL,
  `issue_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `issues`
--

INSERT INTO `issues` (`id`, `part_id`, `quantity`, `issued_to`, `issue_date`) VALUES
(1, 18, 1, 'Димов Роман', '2025-07-15 17:44:51');

-- --------------------------------------------------------

--
-- Структура таблицы `kb_articles`
--

CREATE TABLE `kb_articles` (
  `id` int NOT NULL,
  `category_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `author_id` int NOT NULL,
  `views` int DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `kb_attachments`
--

CREATE TABLE `kb_attachments` (
  `id` int NOT NULL,
  `article_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `kb_categories`
--

CREATE TABLE `kb_categories` (
  `id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text,
  `parent_id` int DEFAULT '0',
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `knowledge_base`
--

CREATE TABLE `knowledge_base` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `tags` text,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `knowledge_faq`
--

CREATE TABLE `knowledge_faq` (
  `id` int NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `views` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `mechanics`
--

CREATE TABLE `mechanics` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `specialty` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `mechanics`
--

INSERT INTO `mechanics` (`id`, `name`, `phone`, `specialty`) VALUES
(1, 'Иван Иванов', '+7 900 123-45-67', 'Мастер по ремонту'),
(2, 'Петр Петров', '+7 901 234-56-78', 'Диагност'),
(8, 'Стрункин Василий', '+7 (122) 222-22-22', 'Шинка');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `car_id` int DEFAULT NULL,
  `created` date DEFAULT (curdate()),
  `description` text,
  `status` enum('В ожидании','В работе','Готов','Выдан') DEFAULT 'В ожидании',
  `total` decimal(10,2) DEFAULT '0.00',
  `services_data` text COMMENT 'JSON данные об услугах',
  `parts_data` text COMMENT 'JSON данные о запчастях',
  `services_total` decimal(10,2) DEFAULT '0.00',
  `parts_total` decimal(10,2) DEFAULT '0.00'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `car_id`, `created`, `description`, `status`, `total`, `services_data`, `parts_data`, `services_total`, `parts_total`) VALUES
(1, 1, '2024-01-15', 'Замена масла двигателя, замена воздушного фильтра', 'Готов', 0.00, '[]', '[]', 0.00, 0.00),
(2, 2, '2024-01-16', 'Диагностика ходовой части, замена амортизаторов', 'В работе', 12500.00, NULL, NULL, 0.00, 0.00),
(3, 3, '2024-01-17', 'Замена тормозных колодок, прокачка тормозной системы', 'Готов', 9200.00, NULL, NULL, 0.00, 0.00),
(4, 4, '2024-01-18', 'Диагностика двигателя, проверка системы зажигания', 'В ожидании', 3500.00, NULL, NULL, 0.00, 0.00),
(5, 5, '2024-01-19', 'Замена ремня ГРМ, замена роликов', 'В ожидании', 18700.00, NULL, NULL, 0.00, 0.00),
(6, 6, '2024-01-20', 'Регулировка развала-схождения, балансировка колес', 'Готов', 4800.00, NULL, NULL, 0.00, 0.00),
(7, 7, '2024-01-21', 'Замена аккумулятора, диагностика электросистемы', 'В работе', 11200.00, NULL, NULL, 0.00, 0.00),
(8, 8, '2024-01-22', 'Замена свечей зажигания, чистка форсунок', 'Готов', 0.00, '[]', '[]', 0.00, 0.00),
(9, 9, '2024-01-23', 'Диагностика кондиционера, заправка фреоном', 'В ожидании', 3380.00, '[{\"service_id\":16,\"name\":\"Подшипник ступицы замена\",\"quantity\":1,\"price\":2000,\"unit\":\"шт.\"}]', '[{\"part_id\":18,\"name\":\"Рабочий цилиндр, система сцепления\",\"part_number\":\"3540\",\"quantity\":1,\"price\":\"1380.00\"}]', 2000.00, 1380.00),
(10, 10, '2024-01-24', 'Замена масла в АКПП, замена фильтра АКПП', 'Готов', 9677.00, '[{\"service_id\":7,\"name\":\"Развал схождение\",\"quantity\":1,\"price\":2200,\"unit\":\"шт.\"},{\"service_id\":4,\"name\":\" Развал - схождения передней оси на легковом автомобиле 105\",\"quantity\":1,\"price\":1600,\"unit\":\"шт.\"}]', '[{\"part_id\":11,\"quantity\":2,\"name\":\"Тяга рулевая | перед прав\\/лев |\",\"part_number\":\"C2499LR\",\"price\":\"1000.00\"},{\"part_id\":15,\"quantity\":1,\"name\":\"Ступица колеса\",\"part_number\":\"9237002K\",\"price\":\"3327.00\"},{\"part_id\":8,\"name\":\"Пыльник ШРУСа\",\"part_number\":\" PC-2081\",\"quantity\":1,\"price\":\"550.00\"}]', 3800.00, 5877.00),
(11, 10, '2025-11-11', 'Шинка', 'В ожидании', 0.00, '[]', '[]', 0.00, 0.00);

-- --------------------------------------------------------

--
-- Структура таблицы `order_inspection_data`
--

CREATE TABLE `order_inspection_data` (
  `id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `item_type` enum('template','custom') COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `inspection_item_id` int DEFAULT NULL,
  `custom_name` varchar(200) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `side` enum('left','right','both','none') COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `action` enum('repair','replace','diagnostic') COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `work_price` decimal(8,2) DEFAULT NULL,
  `part_price` decimal(8,2) DEFAULT NULL,
  `total_price` decimal(8,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb3_unicode_520_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `order_parts`
--

CREATE TABLE `order_parts` (
  `order_id` int NOT NULL,
  `part_id` int NOT NULL,
  `quantity` int DEFAULT NULL,
  `source_type` enum('service_warehouse','client_provided') DEFAULT 'service_warehouse',
  `issue_status` enum('reserved','issued','used','returned') DEFAULT 'reserved',
  `warehouse_item_id` int DEFAULT NULL,
  `added_by` int NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `order_parts`
--

INSERT INTO `order_parts` (`order_id`, `part_id`, `quantity`, `source_type`, `issue_status`, `warehouse_item_id`, `added_by`) VALUES
(10, 11, 2, 'service_warehouse', 'issued', NULL, 1),
(10, 15, 1, 'service_warehouse', 'issued', NULL, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `order_services`
--

CREATE TABLE `order_services` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `service_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `order_tire_services`
--

CREATE TABLE `order_tire_services` (
  `id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `tire_service_id` int DEFAULT NULL,
  `radius` int NOT NULL,
  `quantity` int DEFAULT '1',
  `total_price` decimal(10,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb3_unicode_520_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `client_name` varchar(100) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `client_phone` varchar(20) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `car_model` varchar(100) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `car_plate` varchar(20) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `tire_type` enum('summer','winter','allseason') COLLATE utf8mb3_unicode_520_ci DEFAULT 'summer',
  `status` enum('new','in_progress','completed','issued') COLLATE utf8mb3_unicode_520_ci DEFAULT 'new',
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `parts`
--

CREATE TABLE `parts` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `part_number` varchar(50) DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `parts`
--

INSERT INTO `parts` (`id`, `name`, `part_number`, `quantity`, `price`) VALUES
(7, 'Ремкомплект тормозного суппорта | перед |', 'BC-0208', 1, 500.00),
(8, 'Пыльник ШРУСа', ' PC-2081', 1, 550.00),
(9, 'Подвеска, рычаг независимой подвески колеса', 'C8015', 2, 420.00),
(10, 'Защитный колпак / пыльник, амортизатор', '22161 01', 1, 400.00),
(11, 'Тяга рулевая | перед прав/лев |', 'C2499LR', 1, 1000.00),
(12, 'Колодки тормозные дисковые | зад |', 'BD-4407', 1, 1100.00),
(13, 'Подвеска, рычаг независимой подвески колеса', '510310', 1, 862.00),
(14, 'Комплект пыльника, рулевое управление', '540329S', 2, 652.00),
(15, 'Ступица колеса', '9237002K', 1, 3327.00),
(16, 'Тяга / стойка, стабилизатор', 'S050077', 2, 1210.00),
(17, 'Масло трансмиссионное Специальное ATF AG-52 1л', '82111Л', 3, 1655.00),
(18, 'Рабочий цилиндр, система сцепления', '3540', 0, 1380.00),
(19, 'Рычаг подвески | зад лев |', 'SH-1910', NULL, 4200.00),
(20, 'Комплект тормозных колодок, дисковый тормоз', 'BD-1413', NULL, 1980.00),
(23, 'Подвеска, корпус колесного подшипника', '821902', 2, 7800.00),
(24, 'Амортизатор', 'G12716LR', 2, 12720.00),
(25, 'Поперечная рулевая тяга', 'C3004L', 1, 3320.00),
(26, 'Сайлентблок заднего рычага подвески | зад прав/лев |', 'C9408', 1, 2450.00),
(27, 'Сайлентблок заднего рычага подвески | зад прав/лев |', 'C9409', 1, 3060.00);

-- --------------------------------------------------------

--
-- Структура таблицы `part_status_log`
--

CREATE TABLE `part_status_log` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `part_id` int NOT NULL,
  `old_status` enum('reserved','issued','used','returned') COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `new_status` enum('reserved','issued','used','returned') COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `changed_by` int NOT NULL,
  `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text COLLATE utf8mb3_unicode_520_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Дамп данных таблицы `part_status_log`
--

INSERT INTO `part_status_log` (`id`, `order_id`, `part_id`, `old_status`, `new_status`, `changed_by`, `changed_at`, `notes`) VALUES
(1, 10, 11, 'reserved', 'issued', 1, '2025-11-13 12:44:43', NULL),
(2, 10, 15, 'reserved', 'issued', 1, '2025-11-13 13:00:15', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `pdf_documents`
--

CREATE TABLE `pdf_documents` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `category` enum('Двигатель','Трансмиссия','Электрика','Кузов','Диагностика') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int NOT NULL,
  `role` varchar(50) NOT NULL,
  `permission` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `services`
--

CREATE TABLE `services` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `unit` varchar(50) NOT NULL DEFAULT 'шт.'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `services`
--

INSERT INTO `services` (`id`, `name`, `code`, `price`, `duration`, `unit`) VALUES
(1, 'Шиномонтаж R13', '13', 800.00, NULL, 'шт.'),
(2, 'Шиномонтаж R14', '14', 900.00, NULL, 'шт.'),
(3, 'Шиномонтаж R15', '15', 1000.00, NULL, 'шт.'),
(4, 'Шиномонтаж R16', '16', 1100.00, NULL, 'шт.'),
(5, 'Шиномонтаж R17', '17', 1200.00, NULL, 'шт.'),
(6, 'Шиномонтаж R18', '18', 1300.00, NULL, 'шт.'),
(7, 'Шиномонтаж R19', '19', 1400.00, NULL, 'шт.'),
(8, 'Шиномонтаж R20', '20', 1500.00, NULL, 'шт.'),
(9, 'Шиномонтаж R21', '21', 1600.00, NULL, 'шт.'),
(10, 'Шиномонтаж R22', '22', 1700.00, NULL, 'шт.'),
(11, 'Замена масла двигателя', '10', 1500.00, NULL, 'шт.'),
(12, 'Замена масляного фильтра', '11', 500.00, NULL, 'шт.'),
(13, 'Замена воздушного фильтра', '12', 400.00, NULL, 'шт.'),
(14, 'Замена салонного фильтра', '23', 600.00, NULL, 'шт.'),
(15, 'Замена свечей зажигания', '24', 800.00, NULL, 'шт.'),
(16, 'Замена антифриза', '25', 1400.00, NULL, 'шт.'),
(17, 'Замена топливного фильтра', '26', 1000.00, NULL, 'шт.'),
(18, 'Замена тормозной жидкости', '27', 900.00, NULL, 'шт.'),
(19, 'Сброс сервисного интервала', '28', 300.00, NULL, 'шт.'),
(20, 'Чтение и сброс ошибок', '29', 600.00, NULL, 'шт.'),
(21, 'Компьютерная диагностика', '30', 1400.00, NULL, 'шт.'),
(22, 'Диагностика перед покупкой', '31', 600.00, NULL, 'шт.'),
(23, 'Диагностика ходовой части', '32', 600.00, NULL, 'шт.'),
(24, 'Диагностика двигателя', '33', 1200.00, NULL, 'шт.'),
(25, 'Диагностика АКПП', '34', 1000.00, NULL, 'шт.'),
(26, 'Диагностика электроники', '35', 800.00, NULL, 'шт.'),
(27, 'Замена амортизаторов передних', '40', 3000.00, NULL, 'шт.'),
(28, 'Замена амортизаторов задних', '41', 2500.00, NULL, 'шт.'),
(29, 'Замена шаровых опор', '42', 1500.00, NULL, 'шт.'),
(30, 'Замена стоек стабилизатора', '43', 800.00, NULL, 'шт.'),
(31, 'Замена подшипников ступиц', '44', 2000.00, NULL, 'шт.'),
(32, 'Замена пружин подвески', '45', 3500.00, NULL, 'шт.'),
(33, 'Замена сайлентблоков', '46', 1200.00, NULL, 'шт.'),
(34, 'Замена тормозных колодок передних', '50', 2000.00, NULL, 'шт.'),
(35, 'Замена тормозных дисков передних', '51', 3500.00, NULL, 'шт.'),
(36, 'Замена тормозных колодок задних', '52', 1800.00, NULL, 'шт.'),
(37, 'Замена тормозных дисков задних', '53', 3000.00, NULL, 'шт.'),
(38, 'Замена тормозного суппорта', '54', 2500.00, NULL, 'шт.'),
(39, 'Прокачка тормозной системы', '55', 900.00, NULL, 'шт.'),
(40, 'Замена рулевых наконечников', '60', 1200.00, NULL, 'шт.'),
(41, 'Замена рулевой тяги', '61', 1500.00, NULL, 'шт.'),
(42, 'Замена рулевой рейки', '62', 6000.00, NULL, 'шт.'),
(43, 'Замена насоса ГУР', '63', 3500.00, NULL, 'шт.'),
(44, 'Замена жидкости ГУР', '64', 800.00, NULL, 'шт.'),
(45, 'Регулировка развала-схождения передних колес', '70', 1500.00, NULL, 'шт.'),
(46, 'Регулировка развала-схождения всех колес', '71', 2000.00, NULL, 'шт.'),
(47, 'Регулировка на 3D стенде', '72', 2500.00, NULL, 'шт.'),
(48, 'Замена радиатора', '80', 2800.00, NULL, 'шт.'),
(49, 'Замена термостата', '81', 1900.00, NULL, 'шт.'),
(50, 'Замена помпы (водяного насоса)', '82', 2000.00, NULL, 'шт.'),
(51, 'Промывка системы охлаждения', '83', 2000.00, NULL, 'шт.'),
(52, 'Замена патрубков', '84', 1200.00, NULL, 'шт.'),
(53, 'Замена бензонасоса', '90', 1800.00, NULL, 'шт.'),
(54, 'Промывка форсунок', '91', 2000.00, NULL, 'шт.'),
(55, 'Чистка дроссельной заслонки', '92', 1000.00, NULL, 'шт.'),
(56, 'Замена топливного насоса', '93', 2200.00, NULL, 'шт.'),
(57, 'Замена топливного фильтра тонкой очистки', '94', 800.00, NULL, 'шт.');

-- --------------------------------------------------------

--
-- Структура таблицы `service_mechanics`
--

CREATE TABLE `service_mechanics` (
  `service_id` int NOT NULL,
  `mechanic_id` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `service_mechanics`
--

INSERT INTO `service_mechanics` (`service_id`, `mechanic_id`) VALUES
(1, 2),
(1, 3),
(2, 1),
(3, 2);

-- --------------------------------------------------------

--
-- Структура таблицы `tasks`
--

CREATE TABLE `tasks` (
  `id` int NOT NULL,
  `client_id` int NOT NULL,
  `car_id` int DEFAULT NULL,
  `description` text NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','done') DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `tasks`
--

INSERT INTO `tasks` (`id`, `client_id`, `car_id`, `description`, `due_date`, `status`, `created_at`) VALUES
(1, 27, 16, 'ВИРТУАЛЬНАЯ ЗАДАЧА', '2025-07-05', 'done', '2025-07-04 15:23:47'),
(2, 27, 21, 'Обслуживание', '2025-07-09', 'pending', '2025-07-07 15:34:23'),
(3, 1, 3, 'проверка', '2025-11-11', 'pending', '2025-11-11 10:45:17'),
(4, 1, 3, 'проверка', '2025-11-11', 'pending', '2025-11-11 10:46:04');

-- --------------------------------------------------------

--
-- Структура таблицы `tire_orders`
--

CREATE TABLE `tire_orders` (
  `id` int NOT NULL,
  `client_id` int NOT NULL,
  `car_id` int NOT NULL,
  `vin` varchar(50) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `license_plate` varchar(20) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `mileage` int DEFAULT NULL,
  `services` text COLLATE utf8mb3_unicode_520_ci,
  `tire_data` json DEFAULT NULL,
  `notes` text COLLATE utf8mb3_unicode_520_ci,
  `status` enum('draft','active','completed','cancelled') COLLATE utf8mb3_unicode_520_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Дамп данных таблицы `tire_orders`
--

INSERT INTO `tire_orders` (`id`, `client_id`, `car_id`, `vin`, `license_plate`, `mileage`, `services`, `tire_data`, `notes`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '', '', 0, '', '{\"fl_size\": \"18\", \"fr_size\": \"\", \"rl_size\": \"\", \"rr_size\": \"\", \"fl_brand\": \"\", \"fr_brand\": \"\", \"rl_brand\": \"\", \"rr_brand\": \"\"}', '', 'draft', 2, '2025-11-14 06:29:30', '2025-11-14 06:29:30');

-- --------------------------------------------------------

--
-- Структура таблицы `tire_prices`
--

CREATE TABLE `tire_prices` (
  `id` int NOT NULL,
  `tire_service_id` int DEFAULT NULL,
  `radius` int NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Дамп данных таблицы `tire_prices`
--

INSERT INTO `tire_prices` (`id`, `tire_service_id`, `radius`, `price`) VALUES
(1, 1, 13, 1500.00),
(2, 1, 14, 1600.00),
(3, 1, 15, 1800.00),
(4, 1, 16, 2000.00),
(5, 1, 17, 2200.00),
(6, 1, 18, 2400.00),
(7, 1, 19, 2600.00),
(8, 1, 20, 3000.00),
(9, 2, 13, 1300.00),
(10, 2, 14, 1400.00),
(11, 2, 15, 1600.00),
(12, 2, 16, 1800.00),
(13, 2, 17, 2000.00),
(14, 2, 18, 2200.00),
(15, 2, 19, 2400.00),
(16, 2, 20, 2800.00),
(17, 3, 13, 200.00),
(18, 3, 14, 200.00),
(19, 3, 15, 200.00),
(20, 3, 16, 200.00),
(21, 3, 17, 200.00),
(22, 3, 18, 200.00),
(23, 3, 19, 200.00),
(24, 3, 20, 200.00),
(25, 4, 13, 100.00),
(26, 4, 14, 100.00),
(27, 4, 15, 100.00),
(28, 4, 16, 100.00),
(29, 4, 17, 100.00),
(30, 4, 18, 100.00),
(31, 4, 19, 100.00),
(32, 4, 20, 100.00);

-- --------------------------------------------------------

--
-- Структура таблицы `tire_services`
--

CREATE TABLE `tire_services` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `description` text COLLATE utf8mb3_unicode_520_ci,
  `is_complex` tinyint(1) DEFAULT '0',
  `is_repair` tinyint(1) DEFAULT '0',
  `base_price` decimal(10,2) DEFAULT NULL,
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Дамп данных таблицы `tire_services`
--

INSERT INTO `tire_services` (`id`, `name`, `description`, `is_complex`, `is_repair`, `base_price`, `sort_order`) VALUES
(1, 'Комплекс шиномонтаж', 'Полный комплекс работ для 4 колес: снятие, мойка, установка, балансировка', 1, 0, 1800.00, 1),
(2, 'Сезонная переобувка', 'Снятие и установка сезонной резины с балансировкой', 1, 0, 1600.00, 2),
(3, 'Снятие-установка', 'Снятие или установка одного колеса', 0, 0, 200.00, 3),
(4, 'Балансировка', 'Балансировка одного колеса', 0, 0, 100.00, 4),
(5, 'Ремонт латкой', 'Ремонт прокола с установкой латки', 0, 1, 300.00, 5),
(6, 'Ремонт жгутом', 'Ремонт прокола жгутом', 0, 1, 200.00, 6),
(7, 'Замена золотника', 'Замена золотника в колесе', 0, 1, 50.00, 7),
(8, 'Замена покрышки', 'Демонтаж и монтаж покрышки без балансировки', 0, 0, 400.00, 8);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','mechanic','manager','reception') NOT NULL DEFAULT 'mechanic',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'admin', '$2y$10$S8rfIjA9QtK3.anV5RCJrumwysJf.bR/ZHfX6k4PAGxJS29OpHCdq', 'admin@autoservice.local', 'Администратор Системы', 'admin', 1, '2025-09-09 08:57:38', '2025-11-12 05:13:02'),
(3, 'manager', '$2y$10$S/TawS3j./lEIAPO73FXj.HRaO8oLlfuoGHQwXmR/Z0WRzU/huLEO', 'manager@autoservice.local', 'Менеджер Сервиса', 'manager', 1, '2025-09-09 08:57:38', '2025-11-12 05:13:01'),
(4, 'mechanic', '$2y$10$FIR1sh1U3NBW1ppKFNj2TeNxO0IDLYOg579LrMCd8R5lqJILwlFVa', 'mechanic@autoservice.local', 'Иванов Иван Иванович', 'mechanic', 1, '2025-09-09 08:57:39', '2025-09-11 06:33:36'),
(5, 'reception', '$2y$10$B.sDWH.tVJcuamXluVgI1.ZoZCHCT/MvWA4bf8g7nHQ8Eqyopoal6', 'reception@autoservice.local', 'Петрова Мария Сергеевна', 'reception', 1, '2025-09-09 08:57:39', '2025-09-11 06:33:36');

-- --------------------------------------------------------

--
-- Структура таблицы `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `warehouse_categories`
--

CREATE TABLE `warehouse_categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Категории для склада запчастей';

--
-- Дамп данных таблицы `warehouse_categories`
--

INSERT INTO `warehouse_categories` (`id`, `name`, `parent_id`, `created_at`, `image`) VALUES
(0, 'Двигатель', NULL, '2025-11-13 06:40:24', NULL),
(0, 'Трансмиссия', NULL, '2025-11-13 06:40:24', NULL),
(0, 'Тормозная система', NULL, '2025-11-13 06:40:24', NULL),
(0, 'Подвеска', NULL, '2025-11-13 06:40:24', NULL),
(0, 'Электрика', NULL, '2025-11-13 06:40:24', NULL),
(0, 'Кузовные детали', NULL, '2025-11-13 06:40:24', NULL),
(0, 'Фильтры', NULL, '2025-11-13 06:40:24', NULL),
(0, 'Масла и жидкости', NULL, '2025-11-13 06:40:24', NULL),
(0, 'Двигатель', NULL, '2025-11-13 06:42:26', NULL),
(0, 'Трансмиссия', NULL, '2025-11-13 06:42:26', NULL),
(0, 'Тормозная система', NULL, '2025-11-13 06:42:26', NULL),
(0, 'Подвеска', NULL, '2025-11-13 06:42:26', NULL),
(0, 'Электрика', NULL, '2025-11-13 06:42:26', NULL),
(0, 'Кузовные детали', NULL, '2025-11-13 06:42:26', NULL),
(0, 'Фильтры', NULL, '2025-11-13 06:42:26', NULL),
(0, 'Масла и жидкости', NULL, '2025-11-13 06:42:26', NULL),
(0, 'Двигатель', NULL, '2025-11-13 06:44:02', NULL),
(0, 'Трансмиссия', NULL, '2025-11-13 06:44:02', NULL),
(0, 'Тормозная система', NULL, '2025-11-13 06:44:02', NULL),
(0, 'Подвеска', NULL, '2025-11-13 06:44:02', NULL),
(0, 'Электрика', NULL, '2025-11-13 06:44:02', NULL),
(0, 'Кузовные детали', NULL, '2025-11-13 06:44:02', NULL),
(0, 'Фильтры', NULL, '2025-11-13 06:44:02', NULL),
(0, 'Масла и жидкости', NULL, '2025-11-13 06:44:02', NULL),
(0, 'Двигатель', NULL, '2025-11-13 06:46:21', NULL),
(0, 'Трансмиссия', NULL, '2025-11-13 06:46:21', NULL),
(0, 'Тормозная система', NULL, '2025-11-13 06:46:21', NULL),
(0, 'Подвеска', NULL, '2025-11-13 06:46:21', NULL),
(0, 'Электрика', NULL, '2025-11-13 06:46:21', NULL),
(0, 'Кузовные детали', NULL, '2025-11-13 06:46:21', NULL),
(0, 'Фильтры', NULL, '2025-11-13 06:46:21', NULL),
(0, 'Масла и жидкости', NULL, '2025-11-13 06:46:21', NULL),
(0, 'Двигатель', NULL, '2025-11-13 06:58:48', NULL),
(0, 'Трансмиссия', NULL, '2025-11-13 06:58:48', NULL),
(0, 'Тормозная система', NULL, '2025-11-13 06:58:48', NULL),
(0, 'Подвеска', NULL, '2025-11-13 06:58:48', NULL),
(0, 'Электрика', NULL, '2025-11-13 06:58:48', NULL),
(0, 'Кузовные детали', NULL, '2025-11-13 06:58:48', NULL),
(0, 'Фильтры', NULL, '2025-11-13 06:58:48', NULL),
(0, 'Масла и жидкости', NULL, '2025-11-13 06:58:48', NULL),
(0, 'Двигатель', NULL, '2025-11-13 07:00:45', NULL),
(0, 'Трансмиссия', NULL, '2025-11-13 07:00:45', NULL),
(0, 'Тормозная система', NULL, '2025-11-13 07:00:45', NULL),
(0, 'Подвеска', NULL, '2025-11-13 07:00:45', NULL),
(0, 'Электрика', NULL, '2025-11-13 07:00:45', NULL),
(0, 'Кузовные детали', NULL, '2025-11-13 07:00:45', NULL),
(0, 'Фильтры', NULL, '2025-11-13 07:00:45', NULL),
(0, 'Масла и жидкости', NULL, '2025-11-13 07:00:45', NULL),
(0, 'Двигатель', NULL, '2025-11-13 07:47:25', NULL),
(0, 'Трансмиссия', NULL, '2025-11-13 07:47:25', NULL),
(0, 'Тормозная система', NULL, '2025-11-13 07:47:25', NULL),
(0, 'Подвеска', NULL, '2025-11-13 07:47:25', NULL),
(0, 'Электрика', NULL, '2025-11-13 07:47:25', NULL),
(0, 'Кузовные детали', NULL, '2025-11-13 07:47:25', NULL),
(0, 'Фильтры', NULL, '2025-11-13 07:47:25', NULL),
(0, 'Масла и жидкости', NULL, '2025-11-13 07:47:25', NULL),
(0, 'Двигатель', NULL, '2025-11-15 04:03:09', NULL),
(0, 'Трансмиссия', NULL, '2025-11-15 04:03:09', NULL),
(0, 'Тормозная система', NULL, '2025-11-15 04:03:09', NULL),
(0, 'Подвеска', NULL, '2025-11-15 04:03:09', NULL),
(0, 'Электрика', NULL, '2025-11-15 04:03:09', NULL),
(0, 'Кузовные детали', NULL, '2025-11-15 04:03:09', NULL),
(0, 'Фильтры', NULL, '2025-11-15 04:03:09', NULL),
(0, 'Масла и жидкости', NULL, '2025-11-15 04:03:09', NULL),
(0, 'Двигатель', NULL, '2025-11-15 04:03:44', NULL),
(0, 'Трансмиссия', NULL, '2025-11-15 04:03:44', NULL),
(0, 'Тормозная система', NULL, '2025-11-15 04:03:44', NULL),
(0, 'Подвеска', NULL, '2025-11-15 04:03:44', NULL),
(0, 'Электрика', NULL, '2025-11-15 04:03:44', NULL),
(0, 'Кузовные детали', NULL, '2025-11-15 04:03:44', NULL),
(0, 'Фильтры', NULL, '2025-11-15 04:03:44', NULL),
(0, 'Масла и жидкости', NULL, '2025-11-15 04:03:44', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `warehouse_income`
--

CREATE TABLE `warehouse_income` (
  `id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `document_number` varchar(50) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Приход товара';

-- --------------------------------------------------------

--
-- Структура таблицы `warehouse_items`
--

CREATE TABLE `warehouse_items` (
  `id` int NOT NULL,
  `sku` varchar(50) NOT NULL COMMENT 'Артикул',
  `name` varchar(200) NOT NULL,
  `description` text,
  `category_id` int DEFAULT NULL,
  `manufacturer_id` int DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int NOT NULL DEFAULT '0' COMMENT 'Текущий остаток',
  `min_quantity` int DEFAULT '5' COMMENT 'Минимальный запас',
  `location` varchar(50) DEFAULT NULL COMMENT 'Место на складе',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `part_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Складские позиции';

--
-- Дамп данных таблицы `warehouse_items`
--

INSERT INTO `warehouse_items` (`id`, `sku`, `name`, `description`, `category_id`, `manufacturer_id`, `price`, `quantity`, `min_quantity`, `location`, `created_at`, `updated_at`, `part_number`) VALUES
(1, 'PART-7', 'Ремкомплект тормозного суппорта | перед |', NULL, NULL, NULL, 500.00, 1, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', 'BC-0208'),
(2, 'PART-8', 'Пыльник ШРУСа', NULL, NULL, NULL, 550.00, 1, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', ' PC-2081'),
(3, 'PART-9', 'Подвеска, рычаг независимой подвески колеса', NULL, NULL, NULL, 420.00, 2, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', 'C8015'),
(4, 'PART-10', 'Защитный колпак / пыльник, амортизатор', NULL, NULL, NULL, 400.00, 1, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', '22161 01'),
(5, 'PART-11', 'Тяга рулевая | перед прав/лев |', NULL, NULL, NULL, 1000.00, 1, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', 'C2499LR'),
(6, 'PART-12', 'Колодки тормозные дисковые | зад |', NULL, NULL, NULL, 1100.00, 1, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', 'BD-4407'),
(7, 'PART-13', 'Подвеска, рычаг независимой подвески колеса', NULL, NULL, NULL, 862.00, 1, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', '510310'),
(8, 'PART-14', 'Комплект пыльника, рулевое управление', NULL, NULL, NULL, 652.00, 2, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', '540329S'),
(9, 'PART-15', 'Ступица колеса', NULL, NULL, NULL, 3327.00, 1, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', '9237002K'),
(10, 'PART-16', 'Тяга / стойка, стабилизатор', NULL, NULL, NULL, 1210.00, 2, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', 'S050077'),
(11, 'PART-17', 'Масло трансмиссионное Специальное ATF AG-52 1л', NULL, NULL, NULL, 1655.00, 3, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', '82111Л'),
(12, 'PART-18', 'Рабочий цилиндр, система сцепления', NULL, NULL, NULL, 1380.00, 0, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', '3540'),
(13, 'PART-19', 'Рычаг подвески | зад лев |', NULL, NULL, NULL, 4200.00, 0, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', 'SH-1910'),
(14, 'PART-20', 'Комплект тормозных колодок, дисковый тормоз', NULL, NULL, NULL, 1980.00, 0, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', 'BD-1413'),
(15, 'PART-23', 'Подвеска, корпус колесного подшипника', NULL, NULL, NULL, 7800.00, 2, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', '821902'),
(16, 'PART-24', 'Амортизатор', NULL, NULL, NULL, 12720.00, 2, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', 'G12716LR'),
(17, 'PART-25', 'Поперечная рулевая тяга', NULL, NULL, NULL, 3320.00, 1, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', 'C3004L'),
(18, 'PART-26', 'Сайлентблок заднего рычага подвески | зад прав/лев |', NULL, NULL, NULL, 2450.00, 1, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', 'C9408'),
(19, 'PART-27', 'Сайлентблок заднего рычага подвески | зад прав/лев |', NULL, NULL, NULL, 3060.00, 1, 5, NULL, '2025-11-13 06:58:13', '2025-11-13 06:58:13', 'C9409');

-- --------------------------------------------------------

--
-- Структура таблицы `warehouse_manufacturers`
--

CREATE TABLE `warehouse_manufacturers` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `country` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Производители запчастей';

-- --------------------------------------------------------

--
-- Структура таблицы `warehouse_outcome`
--

CREATE TABLE `warehouse_outcome` (
  `id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `client_id` int DEFAULT NULL COMMENT 'Ссылка на клиента из основной системы',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Расход товара';

-- --------------------------------------------------------

--
-- Структура таблицы `works`
--

CREATE TABLE `works` (
  `id` int NOT NULL,
  `category` enum('front_axis','rear_axis','other') NOT NULL DEFAULT 'other',
  `name` varchar(255) NOT NULL,
  `description` text,
  `duration` smallint NOT NULL DEFAULT '30' COMMENT 'В минутах',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `wp_actionscheduler_actions`
--

CREATE TABLE `wp_actionscheduler_actions` (
  `action_id` bigint UNSIGNED NOT NULL,
  `hook` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `scheduled_date_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `scheduled_date_local` datetime DEFAULT '0000-00-00 00:00:00',
  `priority` tinyint UNSIGNED NOT NULL DEFAULT '10',
  `args` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `group_id` bigint UNSIGNED NOT NULL DEFAULT '0',
  `attempts` int NOT NULL DEFAULT '0',
  `last_attempt_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `last_attempt_local` datetime DEFAULT '0000-00-00 00:00:00',
  `claim_id` bigint UNSIGNED NOT NULL DEFAULT '0',
  `extended_args` varchar(8000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Дамп данных таблицы `wp_actionscheduler_actions`
--

INSERT INTO `wp_actionscheduler_actions` (`action_id`, `hook`, `status`, `scheduled_date_gmt`, `scheduled_date_local`, `priority`, `args`, `schedule`, `group_id`, `attempts`, `last_attempt_gmt`, `last_attempt_local`, `claim_id`, `extended_args`) VALUES
(135, 'action_scheduler/migration_hook', 'complete', '2025-06-20 10:41:28', '2025-06-20 10:41:28', 10, '[]', 'O:30:\"ActionScheduler_SimpleSchedule\":2:{s:22:\"\0*\0scheduled_timestamp\";i:1750416088;s:41:\"\0ActionScheduler_SimpleSchedule\0timestamp\";i:1750416088;}', 1, 1, '2025-06-20 10:42:09', '2025-06-20 13:42:09', 0, NULL),
(136, 'woocommerce_refresh_order_count_cache', 'pending', '2025-06-20 22:40:32', '2025-06-20 22:40:32', 10, '[\"shop_order\"]', 'O:32:\"ActionScheduler_IntervalSchedule\":5:{s:22:\"\0*\0scheduled_timestamp\";i:1750459232;s:18:\"\0*\0first_timestamp\";i:1750459232;s:13:\"\0*\0recurrence\";i:43200;s:49:\"\0ActionScheduler_IntervalSchedule\0start_timestamp\";i:1750459232;s:53:\"\0ActionScheduler_IntervalSchedule\0interval_in_seconds\";i:43200;}', 2, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, NULL),
(137, 'woocommerce_cleanup_draft_orders', 'complete', '2025-06-20 10:40:37', '2025-06-20 10:40:37', 10, '[]', 'O:32:\"ActionScheduler_IntervalSchedule\":5:{s:22:\"\0*\0scheduled_timestamp\";i:1750416037;s:18:\"\0*\0first_timestamp\";i:1750416037;s:13:\"\0*\0recurrence\";i:86400;s:49:\"\0ActionScheduler_IntervalSchedule\0start_timestamp\";i:1750416037;s:53:\"\0ActionScheduler_IntervalSchedule\0interval_in_seconds\";i:86400;}', 3, 1, '2025-06-20 10:40:39', '2025-06-20 13:40:39', 0, NULL),
(138, 'woocommerce_cleanup_draft_orders', 'pending', '2025-06-21 10:40:39', '2025-06-21 10:40:39', 10, '[]', 'O:32:\"ActionScheduler_IntervalSchedule\":5:{s:22:\"\0*\0scheduled_timestamp\";i:1750502439;s:18:\"\0*\0first_timestamp\";i:1750416037;s:13:\"\0*\0recurrence\";i:86400;s:49:\"\0ActionScheduler_IntervalSchedule\0start_timestamp\";i:1750502439;s:53:\"\0ActionScheduler_IntervalSchedule\0interval_in_seconds\";i:86400;}', 3, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, NULL),
(139, 'woocommerce_install_assembler_fonts', 'complete', '2025-06-20 10:40:50', '2025-06-20 10:40:50', 10, '[]', 'O:30:\"ActionScheduler_SimpleSchedule\":2:{s:22:\"\0*\0scheduled_timestamp\";i:1750416050;s:41:\"\0ActionScheduler_SimpleSchedule\0timestamp\";i:1750416050;}', 3, 1, '2025-06-20 10:41:59', '2025-06-20 13:41:59', 0, NULL),
(140, 'fetch_patterns', 'complete', '2025-06-20 10:40:50', '2025-06-20 10:40:50', 10, '[]', 'O:30:\"ActionScheduler_SimpleSchedule\":2:{s:22:\"\0*\0scheduled_timestamp\";i:1750416050;s:41:\"\0ActionScheduler_SimpleSchedule\0timestamp\";i:1750416050;}', 3, 1, '2025-06-20 10:42:09', '2025-06-20 13:42:09', 0, NULL),
(141, 'action_scheduler/migration_hook', 'pending', '2025-06-20 10:57:37', '2025-06-20 10:57:37', 10, '[]', 'O:30:\"ActionScheduler_SimpleSchedule\":2:{s:22:\"\0*\0scheduled_timestamp\";i:1750417057;s:41:\"\0ActionScheduler_SimpleSchedule\0timestamp\";i:1750417057;}', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `wp_actionscheduler_claims`
--

CREATE TABLE `wp_actionscheduler_claims` (
  `claim_id` bigint UNSIGNED NOT NULL,
  `date_created_gmt` datetime DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `wp_actionscheduler_groups`
--

CREATE TABLE `wp_actionscheduler_groups` (
  `group_id` bigint UNSIGNED NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date` (`date`,`service_id`),
  ADD KEY `fk_mechanic` (`mechanic_id`);

--
-- Индексы таблицы `available_times`
--
ALTER TABLE `available_times`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vin` (`vin`),
  ADD UNIQUE KEY `license_plate` (`license_plate`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `idx_cars_search` (`make`,`model`,`license_plate`);

--
-- Индексы таблицы `change_history`
--
ALTER TABLE `change_history`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `idx_clients_search` (`name`,`phone`);

--
-- Индексы таблицы `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `company_details`
--
ALTER TABLE `company_details`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_faq_active` (`is_active`),
  ADD KEY `idx_faq_order` (`sort_order`);

--
-- Индексы таблицы `faq_pdf_references`
--
ALTER TABLE `faq_pdf_references`
  ADD KEY `faq_id` (`faq_id`),
  ADD KEY `pdf_id` (`pdf_id`);

--
-- Индексы таблицы `inspection_categories`
--
ALTER TABLE `inspection_categories`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `inspection_items`
--
ALTER TABLE `inspection_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Индексы таблицы `issues`
--
ALTER TABLE `issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_part_id` (`part_id`);

--
-- Индексы таблицы `kb_articles`
--
ALTER TABLE `kb_articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Индексы таблицы `kb_attachments`
--
ALTER TABLE `kb_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `article_id` (`article_id`);

--
-- Индексы таблицы `kb_categories`
--
ALTER TABLE `kb_categories`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `knowledge_base`
--
ALTER TABLE `knowledge_base`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `knowledge_faq`
--
ALTER TABLE `knowledge_faq`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `mechanics`
--
ALTER TABLE `mechanics`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`),
  ADD KEY `idx_orders_search` (`id`);

--
-- Индексы таблицы `order_inspection_data`
--
ALTER TABLE `order_inspection_data`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `order_parts`
--
ALTER TABLE `order_parts`
  ADD PRIMARY KEY (`order_id`,`part_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Индексы таблицы `order_services`
--
ALTER TABLE `order_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order_service` (`order_id`,`service_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Индексы таблицы `order_tire_services`
--
ALTER TABLE `order_tire_services`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `parts`
--
ALTER TABLE `parts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `part_number` (`part_number`);

--
-- Индексы таблицы `part_status_log`
--
ALTER TABLE `part_status_log`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `pdf_documents`
--
ALTER TABLE `pdf_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `file_path` (`file_path`);

--
-- Индексы таблицы `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role`,`permission`);

--
-- Индексы таблицы `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `service_mechanics`
--
ALTER TABLE `service_mechanics`
  ADD PRIMARY KEY (`service_id`,`mechanic_id`),
  ADD KEY `mechanic_id` (`mechanic_id`);

--
-- Индексы таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Индексы таблицы `tire_orders`
--
ALTER TABLE `tire_orders`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tire_prices`
--
ALTER TABLE `tire_prices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_service_radius` (`tire_service_id`,`radius`);

--
-- Индексы таблицы `tire_services`
--
ALTER TABLE `tire_services`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Индексы таблицы `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `warehouse_income`
--
ALTER TABLE `warehouse_income`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Индексы таблицы `warehouse_items`
--
ALTER TABLE `warehouse_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `manufacturer_id` (`manufacturer_id`);

--
-- Индексы таблицы `warehouse_manufacturers`
--
ALTER TABLE `warehouse_manufacturers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `warehouse_outcome`
--
ALTER TABLE `warehouse_outcome`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Индексы таблицы `works`
--
ALTER TABLE `works`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `available_times`
--
ALTER TABLE `available_times`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблицы `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `change_history`
--
ALTER TABLE `change_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `company_details`
--
ALTER TABLE `company_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `faq`
--
ALTER TABLE `faq`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `inspection_categories`
--
ALTER TABLE `inspection_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `inspection_items`
--
ALTER TABLE `inspection_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `issues`
--
ALTER TABLE `issues`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `kb_articles`
--
ALTER TABLE `kb_articles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `kb_attachments`
--
ALTER TABLE `kb_attachments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `kb_categories`
--
ALTER TABLE `kb_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `knowledge_base`
--
ALTER TABLE `knowledge_base`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `knowledge_faq`
--
ALTER TABLE `knowledge_faq`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `mechanics`
--
ALTER TABLE `mechanics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `order_inspection_data`
--
ALTER TABLE `order_inspection_data`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `order_services`
--
ALTER TABLE `order_services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `order_tire_services`
--
ALTER TABLE `order_tire_services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `parts`
--
ALTER TABLE `parts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT для таблицы `part_status_log`
--
ALTER TABLE `part_status_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `pdf_documents`
--
ALTER TABLE `pdf_documents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `services`
--
ALTER TABLE `services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT для таблицы `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `tire_orders`
--
ALTER TABLE `tire_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `tire_prices`
--
ALTER TABLE `tire_prices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT для таблицы `tire_services`
--
ALTER TABLE `tire_services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `warehouse_income`
--
ALTER TABLE `warehouse_income`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `warehouse_items`
--
ALTER TABLE `warehouse_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблицы `warehouse_manufacturers`
--
ALTER TABLE `warehouse_manufacturers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `warehouse_outcome`
--
ALTER TABLE `warehouse_outcome`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `works`
--
ALTER TABLE `works`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `faq_pdf_references`
--
ALTER TABLE `faq_pdf_references`
  ADD CONSTRAINT `faq_pdf_references_ibfk_1` FOREIGN KEY (`faq_id`) REFERENCES `knowledge_faq` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `faq_pdf_references_ibfk_2` FOREIGN KEY (`pdf_id`) REFERENCES `pdf_documents` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `inspection_items`
--
ALTER TABLE `inspection_items`
  ADD CONSTRAINT `inspection_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inspection_categories` (`id`);

--
-- Ограничения внешнего ключа таблицы `issues`
--
ALTER TABLE `issues`
  ADD CONSTRAINT `fk_part_id` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `kb_articles`
--
ALTER TABLE `kb_articles`
  ADD CONSTRAINT `kb_articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `kb_categories` (`id`);

--
-- Ограничения внешнего ключа таблицы `kb_attachments`
--
ALTER TABLE `kb_attachments`
  ADD CONSTRAINT `kb_attachments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `kb_articles` (`id`);

--
-- Ограничения внешнего ключа таблицы `tire_prices`
--
ALTER TABLE `tire_prices`
  ADD CONSTRAINT `tire_prices_ibfk_1` FOREIGN KEY (`tire_service_id`) REFERENCES `tire_services` (`id`);

--
-- Ограничения внешнего ключа таблицы `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
