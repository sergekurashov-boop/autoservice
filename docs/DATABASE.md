# 🗃️ Документация базы данных: `autoservice`

## 📊 Таблица: `appointments`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `date` | date | NO | MUL |  |  |
| `start_time` | time | NO |  |  |  |
| `end_time` | time | NO |  |  |  |
| `service_id` | int | NO |  |  |  |
| `mechanic_id` | int | NO | MUL |  |  |
| `client_name` | varchar(255) | YES |  |  |  |
| `client_phone` | varchar(50) | YES |  |  |  |
| `client_email` | varchar(255) | YES |  |  |  |
| `status` | enum('pending','confirmed','cancelled') | NO |  | pending |  |


### 🔑 Индексы таблицы `appointments`

- `PRIMARY` (id) - BTREE
- `date` (date) - BTREE
- `date` (service_id) - BTREE
- `fk_mechanic` (mechanic_id) - BTREE

## 📊 Таблица: `available_times`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `service_id` | int | NO |  |  |  |
| `date` | date | NO |  |  |  |
| `time` | varchar(5) | NO |  |  |  |
| `booked` | tinyint(1) | YES |  | 0 |  |


### 🔑 Индексы таблицы `available_times`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `bookings`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `name` | varchar(100) | NO |  |  |  |
| `phone` | varchar(50) | NO |  |  |  |
| `service_id` | int | NO |  |  |  |
| `service_name` | varchar(255) | NO |  |  |  |
| `date` | date | NO |  |  |  |
| `time` | varchar(10) | NO |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `mechanic_id` | int | YES |  |  |  |
| `status` | varchar(20) | YES |  | pending |  |
| `user_id` | int | YES |  |  |  |


### 🔑 Индексы таблицы `bookings`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `cars`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `client_id` | int | YES | MUL |  |  |
| `make` | varchar(50) | NO | MUL |  |  |
| `model` | varchar(50) | NO |  |  |  |
| `year` | year | YES |  |  |  |
| `vin` | varchar(17) | YES | UNI |  |  |
| `license_plate` | varchar(15) | YES | UNI |  |  |
| `active` | tinyint(1) | YES |  | 1 |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `updated_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |


### 🔑 Индексы таблицы `cars`

- `PRIMARY` (id) - BTREE
- `vin` (vin) - BTREE
- `license_plate` (license_plate) - BTREE
- `client_id` (client_id) - BTREE
- `idx_cars_search` (make) - BTREE
- `idx_cars_search` (model) - BTREE
- `idx_cars_search` (license_plate) - BTREE
- `idx_cars_client_id` (client_id) - BTREE
- `idx_cars_license_plate` (license_plate) - BTREE
- `idx_cars_vin` (vin) - BTREE
- `idx_cars_make_model` (make) - BTREE
- `idx_cars_make_model` (model) - BTREE

## 📊 Таблица: `change_history`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `entity_type` | varchar(50) | NO |  |  |  |
| `entity_id` | int | NO |  |  |  |
| `user_id` | int | NO |  |  |  |
| `action` | varchar(20) | NO |  |  |  |
| `description` | text | YES |  |  |  |
| `changed_at` | datetime | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `change_history`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `clients`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `name` | varchar(100) | NO | MUL |  |  |
| `company_name` | varchar(255) | YES |  |  |  |
| `inn` | varchar(20) | YES |  |  |  |
| `kpp` | varchar(20) | YES |  |  |  |
| `contact_person` | varchar(255) | YES |  |  |  |
| `contract_number` | varchar(100) | YES |  |  |  |
| `phone` | varchar(20) | YES | UNI |  |  |
| `email` | varchar(255) | YES | MUL |  |  |
| `client_type` | enum('individual','legal') | YES |  | individual |  |
| `active` | tinyint(1) | YES |  | 1 |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `updated_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |


### 🔑 Индексы таблицы `clients`

- `PRIMARY` (id) - BTREE
- `phone` (phone) - BTREE
- `idx_clients_search` (name) - BTREE
- `idx_clients_search` (phone) - BTREE
- `idx_clients_phone` (phone) - BTREE
- `idx_clients_name` (name) - BTREE
- `idx_clients_email` (email) - BTREE

## 📊 Таблица: `companies`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `name` | varchar(255) | NO |  |  |  |
| `address` | text | NO |  |  |  |
| `phone` | varchar(50) | NO |  |  |  |
| `director_name` | varchar(255) | NO |  |  |  |


### 🔑 Индексы таблицы `companies`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `company_details`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `company_name` | varchar(255) | NO |  |  |  |
| `legal_name` | varchar(255) | YES |  |  |  |
| `inn` | varchar(20) | YES |  |  |  |
| `kpp` | varchar(20) | YES |  |  |  |
| `ogrn` | varchar(20) | YES |  |  |  |
| `legal_address` | text | YES |  |  |  |
| `actual_address` | text | YES |  |  |  |
| `phone` | varchar(50) | YES |  |  |  |
| `email` | varchar(100) | YES |  |  |  |
| `website` | varchar(255) | YES |  |  |  |
| `bank_name` | varchar(255) | YES |  |  |  |
| `bank_account` | varchar(50) | YES |  |  |  |
| `corr_account` | varchar(50) | YES |  |  |  |
| `bic` | varchar(20) | YES |  |  |  |
| `director_name` | varchar(255) | YES |  |  |  |
| `accountant_name` | varchar(255) | YES |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `updated_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |


### 🔑 Индексы таблицы `company_details`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `defect_items`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `defect_id` | int | NO | MUL |  |  |
| `type` | enum('service','part','customer_part') | YES |  |  |  |
| `item_id` | int | YES |  |  |  |
| `name` | varchar(255) | NO |  |  |  |
| `quantity` | decimal(8,2) | YES |  | 1.00 |  |
| `price` | decimal(10,2) | YES |  | 0.00 |  |
| `total` | decimal(10,2) | YES |  | 0.00 |  |
| `notes` | text | YES |  |  |  |
| `sort_order` | int | YES |  | 0 |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `defect_items`

- `PRIMARY` (id) - BTREE
- `defect_id` (defect_id) - BTREE

## 📊 Таблица: `defects`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `order_id` | int | YES | MUL |  |  |
| `client_id` | int | NO | MUL |  |  |
| `car_id` | int | NO | MUL |  |  |
| `master_id` | int | YES | MUL |  |  |
| `defect_number` | varchar(50) | YES | UNI |  |  |
| `total_services` | decimal(10,2) | YES |  | 0.00 |  |
| `total_parts` | decimal(10,2) | YES |  | 0.00 |  |
| `grand_total` | decimal(10,2) | YES |  | 0.00 |  |
| `status` | enum('draft','approved','rejected') | YES |  | draft |  |
| `notes` | text | YES |  |  |  |
| `client_agreed` | tinyint(1) | YES |  | 0 |  |
| `safety_explained` | tinyint(1) | YES |  | 0 |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `updated_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |


### 🔑 Индексы таблицы `defects`

- `PRIMARY` (id) - BTREE
- `defect_number` (defect_number) - BTREE
- `client_id` (client_id) - BTREE
- `car_id` (car_id) - BTREE
- `order_id` (order_id) - BTREE
- `master_id` (master_id) - BTREE

## 📊 Таблица: `employees`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `name` | varchar(100) | NO |  |  |  |
| `position` | varchar(100) | NO | MUL |  |  |
| `type` | enum('employee','mechanic') | YES |  | employee |  |
| `phone` | varchar(20) | YES |  |  |  |
| `specialty` | varchar(255) | YES |  |  |  |
| `specialization` | enum('front_axis','rear_axis','all') | YES |  | all |  |
| `work_hours` | varchar(50) | YES |  | 9:00-18:00 |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `salary_type` | enum('percentage','sales','fixed') | YES | MUL | fixed |  |
| `base_rate` | decimal(10,2) | YES |  | 0.00 |  |
| `percentage_rate` | decimal(5,2) | YES |  | 0.00 |  |
| `sales_percentage` | decimal(5,2) | YES |  | 0.00 |  |
| `active` | tinyint(1) | YES | MUL | 1 |  |


### 🔑 Индексы таблицы `employees`

- `PRIMARY` (id) - BTREE
- `idx_employees_active` (active) - BTREE
- `idx_employees_salary_type` (salary_type) - BTREE
- `idx_employees_position` (position) - BTREE

## 📊 Таблица: `faq`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `question` | varchar(255) | NO |  |  |  |
| `answer` | text | NO |  |  |  |
| `sort_order` | int | YES | MUL | 0 |  |
| `pdf_references` | text | YES |  |  |  |
| `views` | int | YES |  | 0 |  |
| `is_active` | tinyint(1) | YES | MUL | 1 |  |


### 🔑 Индексы таблицы `faq`

- `PRIMARY` (id) - BTREE
- `idx_active` (is_active) - BTREE
- `idx_faq_active` (is_active) - BTREE
- `idx_faq_order` (sort_order) - BTREE

## 📊 Таблица: `faq_pdf_references`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `faq_id` | int | YES | MUL |  |  |
| `pdf_id` | int | YES | MUL |  |  |


### 🔑 Индексы таблицы `faq_pdf_references`

- `faq_id` (faq_id) - BTREE
- `pdf_id` (pdf_id) - BTREE

## 📊 Таблица: `inspection_categories`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `name` | varchar(100) | NO |  |  |  |
| `sort_order` | int | YES |  | 0 |  |


### 🔑 Индексы таблицы `inspection_categories`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `inspection_items`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `category_id` | int | YES | MUL |  |  |
| `name` | varchar(200) | NO |  |  |  |
| `default_side` | enum('left','right','both','none') | YES |  | none |  |
| `default_action` | enum('repair','replace') | YES |  | replace |  |
| `typical_work_price` | decimal(8,2) | YES |  |  |  |
| `typical_part_price` | decimal(8,2) | YES |  |  |  |
| `sort_order` | int | YES |  | 0 |  |


### 🔑 Индексы таблицы `inspection_items`

- `PRIMARY` (id) - BTREE
- `category_id` (category_id) - BTREE

## 📊 Таблица: `issues`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `part_id` | int | NO | MUL |  |  |
| `quantity` | int | NO |  |  |  |
| `issued_to` | varchar(255) | NO |  |  |  |
| `issue_date` | datetime | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `issues`

- `PRIMARY` (id) - BTREE
- `fk_part_id` (part_id) - BTREE

## 📊 Таблица: `kb_articles`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `category_id` | int | NO | MUL |  |  |
| `title` | varchar(255) | NO |  |  |  |
| `content` | longtext | NO |  |  |  |
| `author_id` | int | NO |  |  |  |
| `views` | int | YES |  | 0 |  |
| `is_featured` | tinyint(1) | YES |  | 0 |  |
| `is_active` | tinyint(1) | YES |  | 1 |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `updated_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |


### 🔑 Индексы таблицы `kb_articles`

- `PRIMARY` (id) - BTREE
- `category_id` (category_id) - BTREE

## 📊 Таблица: `kb_attachments`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `article_id` | int | NO | MUL |  |  |
| `file_name` | varchar(255) | NO |  |  |  |
| `file_path` | varchar(255) | NO |  |  |  |
| `file_size` | int | NO |  |  |  |
| `uploaded_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `kb_attachments`

- `PRIMARY` (id) - BTREE
- `article_id` (article_id) - BTREE

## 📊 Таблица: `kb_categories`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `title` | varchar(100) | NO |  |  |  |
| `description` | text | YES |  |  |  |
| `parent_id` | int | YES |  | 0 |  |
| `sort_order` | int | YES |  | 0 |  |
| `is_active` | tinyint(1) | YES |  | 1 |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `kb_categories`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `knowledge_base`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `title` | varchar(255) | NO |  |  |  |
| `file_path` | varchar(255) | NO |  |  |  |
| `category` | varchar(100) | NO |  |  |  |
| `tags` | text | YES |  |  |  |
| `description` | text | YES |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `knowledge_base`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `knowledge_faq`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `question` | varchar(255) | NO |  |  |  |
| `answer` | text | NO |  |  |  |
| `views` | int | YES |  | 0 |  |


### 🔑 Индексы таблицы `knowledge_faq`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `order_inspection_data`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `order_id` | int | YES |  |  |  |
| `item_type` | enum('template','custom') | YES |  |  |  |
| `inspection_item_id` | int | YES |  |  |  |
| `custom_name` | varchar(200) | YES |  |  |  |
| `side` | enum('left','right','both','none') | YES |  |  |  |
| `action` | enum('repair','replace','diagnostic') | YES |  |  |  |
| `work_price` | decimal(8,2) | YES |  |  |  |
| `part_price` | decimal(8,2) | YES |  |  |  |
| `total_price` | decimal(8,2) | YES |  |  |  |
| `notes` | text | YES |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `order_inspection_data`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `order_parts`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `order_id` | int | NO | PRI |  |  |
| `part_id` | int | NO | PRI |  |  |
| `quantity` | int | YES |  |  |  |
| `source_type` | enum('service_warehouse','client_provided') | YES |  | service_warehouse |  |
| `issue_status` | enum('reserved','issued','used','returned') | YES |  | reserved |  |
| `warehouse_item_id` | int | YES |  |  |  |
| `added_by` | int | NO |  | 1 |  |


### 🔑 Индексы таблицы `order_parts`

- `PRIMARY` (order_id) - BTREE
- `PRIMARY` (part_id) - BTREE
- `part_id` (part_id) - BTREE

## 📊 Таблица: `order_services`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `order_id` | int | NO | MUL |  |  |
| `service_id` | int | NO | MUL |  |  |
| `service_name` | varchar(255) | YES |  |  |  |
| `quantity` | int | YES |  | 1 |  |
| `price` | decimal(10,2) | NO |  | 0.00 |  |


### 🔑 Индексы таблицы `order_services`

- `PRIMARY` (id) - BTREE
- `unique_order_service` (order_id) - BTREE
- `unique_order_service` (service_id) - BTREE
- `service_id` (service_id) - BTREE

## 📊 Таблица: `order_tire_services`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `order_id` | int | YES |  |  |  |
| `tire_service_id` | int | YES |  |  |  |
| `radius` | int | NO |  |  |  |
| `quantity` | int | YES |  | 1 |  |
| `total_price` | decimal(10,2) | YES |  |  |  |
| `notes` | text | YES |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `client_name` | varchar(100) | YES |  |  |  |
| `client_phone` | varchar(20) | YES |  |  |  |
| `car_model` | varchar(100) | YES |  |  |  |
| `car_plate` | varchar(20) | YES |  |  |  |
| `tire_type` | enum('summer','winter','allseason') | YES |  | summer |  |
| `status` | enum('new','in_progress','completed','issued') | YES |  | new |  |
| `completed_at` | datetime | YES |  |  |  |


### 🔑 Индексы таблицы `order_tire_services`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `orders`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `order_number` | varchar(50) | YES |  |  |  |
| `client_id` | int | YES | MUL |  |  |
| `car_id` | int | YES | MUL |  |  |
| `created` | date | YES | MUL | curdate() | DEFAULT_GENERATED |
| `description` | text | YES |  |  |  |
| `status` | enum('В ожидании','В работе','Готов','Выдан') | YES | MUL | В ожидании |  |
| `total` | decimal(10,2) | YES |  | 0.00 |  |
| `services_data` | text | YES |  |  |  |
| `parts_data` | text | YES |  |  |  |
| `services_total` | decimal(10,2) | YES |  | 0.00 |  |
| `parts_total` | decimal(10,2) | YES |  | 0.00 |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `order_type` | enum('repair','maintenance','diagnostics','tire','other') | YES |  | repair |  |


### 🔑 Индексы таблицы `orders`

- `PRIMARY` (id) - BTREE
- `car_id` (car_id) - BTREE
- `idx_orders_search` (id) - BTREE
- `idx_orders_status` (status) - BTREE
- `idx_orders_created` (created) - BTREE
- `idx_orders_car_id` (car_id) - BTREE
- `idx_orders_client_id` (client_id) - BTREE

## 📊 Таблица: `part_status_log`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `order_id` | int | NO |  |  |  |
| `part_id` | int | NO |  |  |  |
| `old_status` | enum('reserved','issued','used','returned') | NO |  |  |  |
| `new_status` | enum('reserved','issued','used','returned') | NO |  |  |  |
| `changed_by` | int | NO |  |  |  |
| `changed_at` | datetime | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `notes` | text | YES |  |  |  |


### 🔑 Индексы таблицы `part_status_log`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `parts`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `code` | varchar(20) | YES | MUL |  |  |
| `name` | varchar(100) | YES |  |  |  |
| `part_number` | varchar(50) | YES | UNI |  |  |
| `quantity` | int | YES |  |  |  |
| `price` | decimal(10,2) | YES |  |  |  |


### 🔑 Индексы таблицы `parts`

- `PRIMARY` (id) - BTREE
- `part_number` (part_number) - BTREE
- `idx_parts_code` (code) - BTREE

## 📊 Таблица: `pdf_documents`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `title` | varchar(255) | NO |  |  |  |
| `file_path` | varchar(255) | NO | UNI |  |  |
| `category` | enum('Двигатель','Трансмиссия','Электрика','Кузов','Диагностика') | NO |  |  |  |


### 🔑 Индексы таблицы `pdf_documents`

- `PRIMARY` (id) - BTREE
- `file_path` (file_path) - BTREE

## 📊 Таблица: `repair_task_items`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `task_id` | int | NO | MUL |  |  |
| `defect_item_id` | int | YES |  |  |  |
| `type` | enum('service','part') | YES |  |  |  |
| `name` | varchar(255) | NO |  |  |  |
| `quantity` | decimal(8,2) | YES |  | 1.00 |  |
| `planned_time` | decimal(5,2) | YES |  |  |  |
| `actual_time` | decimal(5,2) | YES |  |  |  |
| `mechanic_id` | int | YES | MUL |  |  |
| `is_completed` | tinyint(1) | YES |  | 0 |  |
| `completion_notes` | text | YES |  |  |  |
| `mechanic_signature` | varchar(255) | YES |  |  |  |
| `sort_order` | int | YES |  | 0 |  |


### 🔑 Индексы таблицы `repair_task_items`

- `PRIMARY` (id) - BTREE
- `task_id` (task_id) - BTREE
- `mechanic_id` (mechanic_id) - BTREE

## 📊 Таблица: `repair_tasks`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `defect_id` | int | NO | MUL |  |  |
| `task_number` | varchar(50) | YES | UNI |  |  |
| `master_id` | int | YES |  |  |  |
| `mechanic_id` | int | YES | MUL |  |  |
| `workstation` | varchar(100) | YES |  |  |  |
| `planned_hours` | decimal(5,2) | YES |  |  |  |
| `actual_hours` | decimal(5,2) | YES |  |  |  |
| `status` | enum('assigned','in_progress','completed','quality_check') | YES |  | assigned |  |
| `notes` | text | YES |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `repair_tasks`

- `PRIMARY` (id) - BTREE
- `task_number` (task_number) - BTREE
- `defect_id` (defect_id) - BTREE
- `mechanic_id` (mechanic_id) - BTREE

## 📊 Таблица: `role_permissions`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `role` | varchar(50) | NO | MUL |  |  |
| `permission` | varchar(100) | NO |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `role_permissions`

- `PRIMARY` (id) - BTREE
- `unique_role_permission` (role) - BTREE
- `unique_role_permission` (permission) - BTREE

## 📊 Таблица: `salary_calculations`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `employee_id` | int | YES |  |  |  |
| `period` | date | YES |  |  |  |
| `hours_worked` | int | YES |  | 0 |  |
| `orders_total` | decimal(10,2) | YES |  | 0.00 |  |
| `parts_sales` | decimal(10,2) | YES |  | 0.00 |  |
| `bonus` | decimal(10,2) | YES |  | 0.00 |  |
| `penalty` | decimal(10,2) | YES |  | 0.00 |  |
| `calculated_salary` | decimal(10,2) | YES |  | 0.00 |  |
| `status` | enum('draft','calculated','paid') | YES |  | draft |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `salary_calculations`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `salary_payments`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `employee_id` | int | NO | MUL |  |  |
| `month` | date | NO | MUL |  |  |
| `work_amount` | decimal(10,2) | YES |  | 0.00 |  |
| `sales_amount` | decimal(10,2) | YES |  | 0.00 |  |
| `base_salary` | decimal(10,2) | NO |  |  |  |
| `bonus_amount` | decimal(10,2) | YES |  | 0.00 |  |
| `total_salary` | decimal(10,2) | NO |  |  |  |
| `payment_date` | date | NO | MUL |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `salary_payments`

- `PRIMARY` (id) - BTREE
- `idx_month` (month) - BTREE
- `idx_employee` (employee_id) - BTREE
- `idx_payment_date` (payment_date) - BTREE

## 📊 Таблица: `service_mechanics`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `service_id` | int | NO | PRI |  |  |
| `mechanic_id` | int | NO | PRI |  |  |


### 🔑 Индексы таблицы `service_mechanics`

- `PRIMARY` (service_id) - BTREE
- `PRIMARY` (mechanic_id) - BTREE
- `mechanic_id` (mechanic_id) - BTREE

## 📊 Таблица: `services`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `name` | varchar(100) | YES | UNI |  |  |
| `code` | varchar(20) | YES |  |  |  |
| `price` | decimal(10,2) | YES |  |  |  |
| `duration` | int | YES |  |  |  |
| `unit` | varchar(50) | NO |  | шт. |  |
| `active` | tinyint(1) | YES |  | 1 |  |


### 🔑 Индексы таблицы `services`

- `PRIMARY` (id) - BTREE
- `name` (name) - BTREE

## 📊 Таблица: `superuser_logs`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `username` | varchar(50) | YES |  |  |  |
| `ip` | varchar(45) | YES |  |  |  |
| `user_agent` | text | YES |  |  |  |
| `status` | varchar(20) | YES |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `superuser_logs`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `superusers`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `username` | varchar(50) | NO | UNI |  |  |
| `password_hash` | varchar(255) | NO |  |  |  |
| `email` | varchar(100) | YES |  |  |  |
| `full_name` | varchar(100) | YES |  |  |  |
| `is_active` | tinyint(1) | YES |  | 1 |  |
| `two_factor_secret` | varchar(32) | YES |  |  |  |
| `last_login` | datetime | YES |  |  |  |
| `login_attempts` | int | YES |  | 0 |  |
| `locked_until` | datetime | YES |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `updated_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |


### 🔑 Индексы таблицы `superusers`

- `PRIMARY` (id) - BTREE
- `username` (username) - BTREE

## 📊 Таблица: `tasks`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `client_id` | int | NO | MUL |  |  |
| `car_id` | int | YES | MUL |  |  |
| `description` | text | NO |  |  |  |
| `due_date` | date | NO |  |  |  |
| `status` | enum('pending','done') | YES |  | pending |  |
| `created_at` | datetime | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `tasks`

- `PRIMARY` (id) - BTREE
- `client_id` (client_id) - BTREE
- `car_id` (car_id) - BTREE

## 📊 Таблица: `tire_orders`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `client_id` | int | NO |  |  |  |
| `car_id` | int | NO |  |  |  |
| `vin` | varchar(50) | YES |  |  |  |
| `license_plate` | varchar(20) | YES |  |  |  |
| `mileage` | int | YES |  |  |  |
| `services` | text | YES |  |  |  |
| `tire_data` | json | YES |  |  |  |
| `notes` | text | YES |  |  |  |
| `status` | enum('draft','active','completed','cancelled') | YES |  | draft |  |
| `created_by` | int | YES |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `updated_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |


### 🔑 Индексы таблицы `tire_orders`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `tire_prices`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `tire_service_id` | int | YES | MUL |  |  |
| `radius` | int | NO |  |  |  |
| `price` | decimal(10,2) | NO |  |  |  |


### 🔑 Индексы таблицы `tire_prices`

- `PRIMARY` (id) - BTREE
- `unique_service_radius` (tire_service_id) - BTREE
- `unique_service_radius` (radius) - BTREE

## 📊 Таблица: `tire_services`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `name` | varchar(100) | NO |  |  |  |
| `description` | text | YES |  |  |  |
| `is_complex` | tinyint(1) | YES |  | 0 |  |
| `is_repair` | tinyint(1) | YES |  | 0 |  |
| `base_price` | decimal(10,2) | YES |  |  |  |
| `sort_order` | int | YES |  | 0 |  |


### 🔑 Индексы таблицы `tire_services`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `user_activity_logs`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `user_id` | int | YES | MUL |  |  |
| `username` | varchar(100) | YES |  |  |  |
| `action` | varchar(255) | YES | MUL |  |  |
| `module` | varchar(100) | YES | MUL |  |  |
| `record_id` | int | YES |  |  |  |
| `ip_address` | varchar(45) | YES |  |  |  |
| `user_agent` | text | YES |  |  |  |
| `additional_data` | json | YES |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `user_activity_logs`

- `PRIMARY` (id) - BTREE
- `idx_logs_user_date` (user_id) - BTREE
- `idx_logs_user_date` (created_at) - BTREE
- `idx_logs_action_date` (action) - BTREE
- `idx_logs_action_date` (created_at) - BTREE
- `idx_logs_module_date` (module) - BTREE
- `idx_logs_module_date` (created_at) - BTREE

## 📊 Таблица: `user_sessions`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `user_id` | int | NO | MUL |  |  |
| `session_token` | varchar(255) | NO |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `expires_at` | timestamp | NO |  |  |  |


### 🔑 Индексы таблицы `user_sessions`

- `PRIMARY` (id) - BTREE
- `user_id` (user_id) - BTREE

## 📊 Таблица: `users`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `username` | varchar(50) | NO | UNI |  |  |
| `password` | varchar(255) | NO |  |  |  |
| `email` | varchar(100) | NO | UNI |  |  |
| `full_name` | varchar(100) | NO |  |  |  |
| `role` | enum('admin','mechanic','manager','reception') | NO |  | mechanic |  |
| `is_active` | tinyint(1) | YES |  | 1 |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `updated_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |


### 🔑 Индексы таблицы `users`

- `PRIMARY` (id) - BTREE
- `username` (username) - BTREE
- `email` (email) - BTREE

## 📊 Таблица: `warehouse_categories`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO |  |  |  |
| `name` | varchar(100) | NO |  |  |  |
| `parent_id` | int | YES |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `image` | varchar(255) | YES |  |  |  |


## 📊 Таблица: `warehouse_income`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `item_id` | int | NO | MUL |  |  |
| `quantity` | int | NO |  |  |  |
| `supplier` | varchar(100) | YES |  |  |  |
| `document_number` | varchar(50) | YES |  |  |  |
| `notes` | text | YES |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `warehouse_income`

- `PRIMARY` (id) - BTREE
- `item_id` (item_id) - BTREE

## 📊 Таблица: `warehouse_items`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `sku` | varchar(50) | NO | UNI |  |  |
| `name` | varchar(200) | NO |  |  |  |
| `description` | text | YES |  |  |  |
| `category_id` | int | YES | MUL |  |  |
| `manufacturer_id` | int | YES | MUL |  |  |
| `price` | decimal(10,2) | NO |  |  |  |
| `quantity` | int | NO |  | 0 |  |
| `min_quantity` | int | YES |  | 5 |  |
| `location` | varchar(50) | YES |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| `updated_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |
| `part_number` | varchar(100) | YES |  |  |  |


### 🔑 Индексы таблицы `warehouse_items`

- `PRIMARY` (id) - BTREE
- `sku` (sku) - BTREE
- `category_id` (category_id) - BTREE
- `manufacturer_id` (manufacturer_id) - BTREE

## 📊 Таблица: `warehouse_manufacturers`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `name` | varchar(100) | NO | UNI |  |  |
| `country` | varchar(50) | YES |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `warehouse_manufacturers`

- `PRIMARY` (id) - BTREE
- `name` (name) - BTREE

## 📊 Таблица: `warehouse_outcome`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `item_id` | int | NO | MUL |  |  |
| `quantity` | int | NO |  |  |  |
| `order_number` | varchar(50) | YES |  |  |  |
| `client_id` | int | YES |  |  |  |
| `notes` | text | YES |  |  |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `warehouse_outcome`

- `PRIMARY` (id) - BTREE
- `item_id` (item_id) - BTREE

## 📊 Таблица: `works`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `id` | int | NO | PRI |  | auto_increment |
| `category` | enum('front_axis','rear_axis','other') | NO |  | other |  |
| `name` | varchar(255) | NO |  |  |  |
| `description` | text | YES |  |  |  |
| `duration` | smallint | NO |  | 30 |  |
| `price` | decimal(10,2) | NO |  | 0.00 |  |
| `created_at` | timestamp | YES |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |


### 🔑 Индексы таблицы `works`

- `PRIMARY` (id) - BTREE

## 📊 Таблица: `wp_actionscheduler_actions`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `action_id` | bigint unsigned | NO |  |  |  |
| `hook` | varchar(191) | NO |  |  |  |
| `status` | varchar(20) | NO |  |  |  |
| `scheduled_date_gmt` | datetime | YES |  | 0000-00-00 00:00:00 |  |
| `scheduled_date_local` | datetime | YES |  | 0000-00-00 00:00:00 |  |
| `priority` | tinyint unsigned | NO |  | 10 |  |
| `args` | varchar(191) | YES |  |  |  |
| `schedule` | longtext | YES |  |  |  |
| `group_id` | bigint unsigned | NO |  | 0 |  |
| `attempts` | int | NO |  | 0 |  |
| `last_attempt_gmt` | datetime | YES |  | 0000-00-00 00:00:00 |  |
| `last_attempt_local` | datetime | YES |  | 0000-00-00 00:00:00 |  |
| `claim_id` | bigint unsigned | NO |  | 0 |  |
| `extended_args` | varchar(8000) | YES |  |  |  |


## 📊 Таблица: `wp_actionscheduler_claims`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `claim_id` | bigint unsigned | NO |  |  |  |
| `date_created_gmt` | datetime | YES |  | 0000-00-00 00:00:00 |  |


## 📊 Таблица: `wp_actionscheduler_groups`

### Структура таблицы

| Столбец | Тип | Null | Ключ | По умолчанию | Дополнительно |
|---------|-----|------|------|--------------|---------------|
| `group_id` | bigint unsigned | NO |  |  |  |
| `slug` | varchar(255) | NO |  |  |  |


