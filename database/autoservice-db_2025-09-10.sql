-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Сен 10 2025 г., 04:55
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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `bookings`
--

INSERT INTO `bookings` (`id`, `name`, `phone`, `service_id`, `service_name`, `date`, `time`, `created_at`) VALUES
(1, 'Иван Иванов', '+7 900 123-45-67', 1, 'Массаж', '2024-04-27', '10:00', '2025-06-26 11:53:08'),
(2, 'Петренко Олег Николаевич', '89114849422', 9, 'Название услуги', '2025-06-27', '09:00', '2025-06-26 11:56:25');

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
(5, 5, 'Volkswagen ', 'Jetta', '1990', 'WVWZZZ1GZLW362795', 'Р009ХУ39'),
(4, 4, 'PEUGEOT ', 'BOXER', '0000', 'VF3YABMFB11391717', ' С919ЕР39'),
(6, 6, 'Audi ', '100', '1987', 'WAUZZZ44ZJA009846', 'М244РЕ39'),
(7, 7, 'Mitsubishi ', 'Pajero', '1994', 'JMB0RV460VJ000151', 'О686КР39'),
(8, 8, 'Lexus ', 'RX', '1999', 'JT6HF10U3X0075819', 'Р572ЕР39'),
(9, 9, 'BMW ', '5 серии', '2009', 'WBANX31070C178758', 'С425АО39'),
(10, 10, 'Renault ', 'Kaptur', '2017', 'X7LASREA759042751', 'С127АУ39'),
(11, 11, 'Audi ', 'A6', '1995', 'WAUZZZ4AZTN009631', 'Р109ВА39v'),
(12, 12, 'Ford ', 'Transit', '1993', 'WF0CXXGBVCPE26137', 'Н749КВ39'),
(13, 14, 'Opel', ' Mokka', '2013', 'XUUJC7D51D0003964', 'Р075РВ39'),
(14, 15, 'Mercedes-Benz', 'Sprinter', '2000', 'WDB9036621R105224', 'О996АР39'),
(15, 17, 'Volkswagen ', 'Polo', '2001', 'WVWZZZ6NZ1Y177271', 'О558КО39'),
(16, 19, 'Audi', ' 80', '1989', 'WAUZZZ8AZLA021782', 'О654ХС39'),
(17, 20, 'Mercedes-Benz ', '220 (W187)', '2000', 'WDB2100071B164214', 'Н878СК39'),
(18, 25, 'Audi ', 'A6', '1991', 'WAUZZZ4AZSN003251', 'Т154НЕ39'),
(19, 8, 'Chevrolet ', 'Cruze', '2013', 'XUFJF695JD3034090', 'С432СН39'),
(20, 27, 'Mitsubishi ', 'ASX', '2014', 'JMBXTGA3WFE701193', 'А600ОО39'),
(21, 27, 'Грузовой бортовой ', 'Газель', '2017', 'XU42824BEJ0001281', 'С550АР39'),
(22, 27, 'Honda ', 'CR-V', '2008', 'SHSRE58508U015300', 'А855АА39'),
(23, 36, 'Mercedes-Benz ', 'E250CDI', '2009', 'WDD2120031A061246', 'О606УК39'),
(24, 40, 'Ford ', 'Sierra', '1988', 'WF0FXXGBBFJE28596', 'С627ТЕ39'),
(25, 43, 'Citroen ', 'C4', '2008', 'VF7UD9HZH45179785', 'Р098ВУ39'),
(26, 44, 'Volkswagen ', 'Polo', '2021', 'XW8ZZZCKZNG004914', 'С106РМ39'),
(27, 45, 'Nissan ', 'Primera', '1999', 'SJNTCAP11U0448842', 'О232МА39'),
(28, 46, 'УАЗ ', 'Patriot', '2014', 'XTT316300E0017717', 'Р917ХН39'),
(29, 47, 'BMW ', '3 серии', '1992', 'WBACB11020FC35248', 'К859НА51'),
(30, 1, 'Toyota', 'Camry', NULL, 'ABC123456789DEF01', 'А001АА77'),
(31, 1, 'Toyota', 'Camry', NULL, 'JTDKB20U983456789', 'А123АА77'),
(32, 50, 'BMW', '5 серии', '1997', 'WBADF71030BS26462', 'Р371МР39'),
(33, 25, 'Volkswagen', 'Passat', '1995', 'WVWZZZ3AZSE244747', 'У169АМ39');

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
  `phone` varchar(20) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `clients`
--

INSERT INTO `clients` (`id`, `name`, `phone`) VALUES
(14, 'Анисимова Татьяна Васильевна', '89013901186'),
(25, 'ГРАНД-РЕМОНТ, ООО (Каслин Сергей Викторович)', '+79114539941'),
(4, 'Попов Алексей Александрович', '+7 (952) 799-50-23 '),
(5, 'Вдовина Екатерина Сергеевна', '+79118515714'),
(6, 'Воробьев Даниил Алексеевич', '+79521151522'),
(7, 'Евстифеев Борис Александрович', '+79527988795'),
(8, 'Тихонова Галина Сергеевна', '+79506797805'),
(9, 'Колисниченко Екатерина Александровна', '+7 (911) 459-28-28 '),
(10, 'Маначин Валерий Иванович', '+79218543537'),
(11, 'Рыбалко Николай Федорович', '+79114732538'),
(12, 'Кравченко Мария Алексеевна', '+79013901186'),
(15, 'Тангаев Сенргей Васильевич', '+79062394306'),
(16, 'Петренко Олег Николаевич', '+79114849422'),
(17, 'Дмитриев Александр Иванович', '+79211031482'),
(19, 'Медведик Василий Александрович', '+79005634015'),
(20, 'Каранова Полина Дмитриевна', '+79082902452'),
(21, 'Карпицкая Ульяна Станиславовна', '89527982329'),
(24, 'Нешта Александра Руслановна', '890025897855'),
(26, 'ГРАНД-РЕМОНТ, ООО', '+791145399444'),
(27, 'ВИРТУАЛЬНЫЙ КЛИЕНТ', '89527982389'),
(35, 'Тихомирова Наталья Николаевна', '+79024162963'),
(36, 'Евдокимов Сергей Игоревич', '79114533546'),
(37, 'Мороз Владимир Григорьевич', '79118577297'),
(38, 'Иванов Александр Александрович', '79005690363'),
(39, 'Мужиченко Борис Борисович', '79527959903'),
(40, 'Шапарин Артем Михайлович', '79506783408'),
(41, 'Верховцев Вадим Михайлович', '79062131694'),
(42, 'Туров Кирилл Анатольевич', '79114918610'),
(43, 'Бадиков Руслан Юрьевич', '79602396996'),
(44, 'Порховников Михаил Игоревич', '79097944602'),
(45, 'Брунько Александр Вадимович', '79316023908'),
(46, 'Викторова Ольга Анатольевна', '79114728878'),
(47, 'Захаров Руслан Михайлович', '79052450799'),
(48, 'Петров Петр Петрович', '+7 (999) 888-77-66'),
(49, 'Иванов Алексей Владимирович', '+7 (999) 123-45-67'),
(50, 'Груша Александр Николаевич', '');

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
(3, 'Алексей Смирнов', '+7 902 345-67-89', 'Механик по двигателю');

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
  `total` decimal(10,2) DEFAULT '0.00'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `car_id`, `created`, `description`, `status`, `total`) VALUES
(1, 1, '2025-06-15', 'Замена масла и масляного фильтра', 'В ожидании', 1000.00),
(2, 2, '2025-06-15', 'Диагностика подвески', 'В ожидании', 600.00),
(3, 4, '2025-06-16', 'Развал-схождение', 'Выдан', 2000.00),
(4, 5, '2025-06-16', 'Развал-схождение', 'Выдан', 2200.00),
(5, 6, '2025-06-16', 'Ремонт ходовой части', 'Готов', 7280.00),
(6, 7, '2025-06-16', 'Развал-схождение', 'Выдан', 2000.00),
(7, 8, '2025-06-16', 'Замена масла', 'Выдан', 1000.00),
(8, 9, '2025-06-17', 'Развал-схождение', 'Выдан', 2200.00),
(9, 11, '2025-06-18', 'Ремонт глушителя', 'Выдан', 20000.00),
(14, 13, '2025-06-20', 'Профилактика топливного фильтра со снятием/установкой бензобака', 'Выдан', 1.00),
(12, 12, '2025-06-18', 'Замена подвесного подшипника Н749КВ39', 'В работе', 2000.00),
(13, 6, '2025-06-18', 'Замена с/блоков балки', 'Готов', 4000.00),
(16, 14, '2025-06-24', 'Кузовной ремонт', 'Выдан', 54730.00),
(17, 18, '2025-06-26', 'Развал-схождение', 'В работе', 1600.00),
(19, 19, '2025-07-04', 'Ремонт дисков R16 (2)\r\nШиномонтаж\r\nБалансировка', 'Выдан', 0.00),
(35, 32, '2025-07-19', 'Ремонт подвески', 'В ожидании', 0.00),
(27, 26, '2025-07-11', 'Развал-схождение', 'Выдан', 0.00),
(32, 14, '2025-07-18', 'Замена масла в ДВС', 'В ожидании', 0.00);

-- --------------------------------------------------------

--
-- Структура таблицы `order_parts`
--

CREATE TABLE `order_parts` (
  `order_id` int NOT NULL,
  `part_id` int NOT NULL,
  `quantity` int DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `order_parts`
--

INSERT INTO `order_parts` (`order_id`, `part_id`, `quantity`) VALUES
(13, 3, 1);

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

--
-- Дамп данных таблицы `order_services`
--

INSERT INTO `order_services` (`id`, `order_id`, `service_id`, `quantity`, `price`) VALUES
(1, 3, 5, 1, 2000.00),
(2, 4, 4, 1, 1600.00),
(3, 5, 6, 1, 0.00),
(4, 4, 7, 1, 2200.00),
(5, 6, 5, 1, 2000.00),
(6, 7, 1, 1, 1000.00),
(7, 9, 8, 1, 20000.00),
(8, 12, 9, 1, 2000.00),
(9, 10, 9, 1, 2000.00),
(10, 8, 7, 1, 2200.00),
(11, 14, 10, 1, 1.00),
(12, 16, 11, 1, 54730.00),
(13, 17, 4, 1, 1600.00),
(14, 32, 19, 1, 1000.00),
(16, 27, 11, 1, 54730.00),
(17, 35, 20, 1, 35000.00);

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
  `price` decimal(10,2) DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `unit` varchar(50) NOT NULL DEFAULT 'шт.'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `services`
--

INSERT INTO `services` (`id`, `name`, `price`, `duration`, `unit`) VALUES
(1, 'Замена масла', 1000.00, NULL, 'шт.'),
(2, 'Диагностика', 2000.00, NULL, 'шт.'),
(3, 'Замена тормозных колодок', 3500.00, NULL, 'шт.'),
(4, ' Развал - схождения передней оси на легковом автомобиле 105', 1600.00, NULL, 'шт.'),
(5, 'Развал - схождения передней оси на Джипе, Микроавтобусе', 2000.00, NULL, 'шт.'),
(12, 'Мойка', NULL, 30, 'шт.'),
(7, 'Развал схождение', 2200.00, NULL, 'шт.'),
(8, 'Ремонт глушителя Р109ВА39', 20000.00, NULL, 'шт.'),
(9, 'Замена подвесного подшипника Н749КВ39', 2000.00, NULL, 'шт.'),
(10, 'Р075РВ39 Снятие/установка бензобака, профилактика фильтра', 1.00, NULL, 'шт.'),
(11, 'Ремонт ходовой О996АР39', 54730.00, NULL, 'шт.'),
(13, 'Ремонт', NULL, 60, 'шт.'),
(14, 'Шиномонтаж', 1800.00, NULL, 'шт.'),
(15, 'Амортизатор передней оси правый - замена', 2000.00, NULL, 'шт.'),
(16, 'Подшипник ступицы замена', 2000.00, NULL, 'шт.'),
(17, 'Масло в заднем редукторе - замена', 500.00, NULL, 'шт.'),
(18, 'Масло в МКПП - замена', 1500.00, NULL, 'шт.'),
(19, 'Замена топливного фильтра', 1000.00, NULL, 'шт.'),
(20, 'Ремонт подвески', 35000.00, NULL, 'шт.');

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
(2, 27, 21, 'Обслуживание', '2025-07-09', 'pending', '2025-07-07 15:34:23');

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
(2, 'admin', '$2y$10$6Por7DfNnn4PixoIW7hQHuogQ.RhdK4VEdlfg2444FDXQLTKZTgSq', 'admin@autoservice.local', 'Администратор Системы', 'admin', 1, '2025-09-09 08:57:38', '2025-09-09 10:11:59'),
(3, 'manager', '$2y$10$NAiTukqNrJ/vWPl27bX8..RkfDBsRRuj4HiT5P7bBNA5JUXRBQmO6', 'manager@autoservice.local', 'Менеджер Сервиса', 'manager', 1, '2025-09-09 08:57:38', '2025-09-09 10:11:59'),
(4, 'mechanic', '$2y$10$b58p7HXDJlCbLZ6JEf.l3.NCGRyHYIDG5L5baksZZ6y21gMK5kWKK', 'mechanic@autoservice.local', 'Иванов Иван Иванович', 'mechanic', 1, '2025-09-09 08:57:39', '2025-09-09 10:11:59'),
(5, 'reception', '$2y$10$505US0ZhlVxnam./yI6lse/q3YnrTJdKQ4yMZukWODYLjcmko3PMu', 'reception@autoservice.local', 'Петрова Мария Сергеевна', 'reception', 1, '2025-09-09 08:57:39', '2025-09-09 10:12:00');

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
  `category_id` int NOT NULL,
  `manufacturer_id` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int NOT NULL DEFAULT '0' COMMENT 'Текущий остаток',
  `min_quantity` int DEFAULT '5' COMMENT 'Минимальный запас',
  `location` varchar(50) DEFAULT NULL COMMENT 'Место на складе',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Складские позиции';

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
-- Индексы таблицы `parts`
--
ALTER TABLE `parts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `part_number` (`part_number`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT для таблицы `change_history`
--
ALTER TABLE `change_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT для таблицы `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT для таблицы `order_services`
--
ALTER TABLE `order_services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблицы `parts`
--
ALTER TABLE `parts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT для таблицы `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
-- Ограничения внешнего ключа таблицы `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
