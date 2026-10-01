# Анализ архитектуры ICMS2 v2.18.2

## Версия
- **Major**: 2, **Minor**: 18, **Build**: 2 (дата: 20260605)

---

## Структура корня проекта

```
/
├── bootstrap.php        — точка инициализации окружения
├── index.php            — точка входа HTTP
├── cron.php             — точка входа для cron-задач
├── system/              — ядро CMS
├── templates/           — шаблоны (default, modern, admincoreui)
├── static/              — статика
├── upload/              — загружаемые файлы
├── cache/               — кеш
└── install/ / update/   — установка и обновление
```

---

## Ядро (`system/core/`)

| Файл | Назначение |
|---|---|
| `core.php` (cmsCore) | Синглтон. URI-роутинг, загрузка контроллеров, БД |
| `controller.php` (cmsController) | Базовый класс всех контроллеров |
| `model.php` (cmsModel) | Базовый класс всех моделей. Query Builder |
| `user.php` (cmsUser) | Синглтон. Авторизация, сессии, права |
| `eventsmanager.php` (cmsEventsManager) | Система хуков/событий |
| `template.php` (cmsTemplate) | Шаблонизатор |
| `form.php` (cmsForm) | Построитель форм |
| `grid.php` (cmsGrid) | Построитель таблиц (backend) |
| `database.php` (cmsDatabase) | Обёртка над MySQL/PDO |
| `permissions.php` (cmsPermissions) | Права доступа |
| `cache.php` (cmsCache) | Кеширование |
| `request.php` (cmsRequest) | HTTP-запросы, валидация |
| `response.php` (cmsResponse) | HTTP-ответы |
| `images.php` (cmsImages) | Работа с изображениями |
| `uploader.php` (cmsUploader) | Загрузка файлов |
| `mailer.php` (cmsMailer) | Отправка email |
| `paginator.php` (cmsPaginator) | Пагинация |
| `backend.php` (cmsBackend) | Базовый класс backend-контроллеров |

---

## Структура контроллеров (`system/controllers/`)

Каждый контроллер — папка вида:
```
controllers/{name}/
├── frontend.php     — фронтенд-класс (extends cmsFrontend)
├── backend.php      — бэкенд-класс (extends cmsBackend)
├── model.php        — модель (extends cmsModel)
├── actions/         — экшены фронтенда (отдельные PHP-файлы)
├── backend/
│   ├── model.php    — бэкенд-модель (иногда)
│   └── actions/     — экшены бэкенда
├── forms/           — объекты форм
├── hooks/           — обработчики событий (по одному файлу = одно событие)
└── widgets/         — виджеты
```

### Ключевые контроллеры
| Контроллер | Назначение |
|---|---|
| `users` | Профили, друзья, подписки, статус, карма |
| `content` | Типы контента, категории, записи |
| `admin` | Панель администратора |
| `auth` | Авторизация, регистрация |
| `comments` | Комментарии |
| `messages` | Личные сообщения |
| `wall` | Стена/лента |
| `search` | Поиск |
| `tags` | Теги |
| `rating` | Рейтинг |
| `photos` | Фото-галерея |
| `files` | Файловый менеджер |
| `menu` | Навигационные меню |
| `groups` | Группы пользователей |
| `geo` | Геолокация |
| `billing` | Биллинг |
| `subscriptions` | Подписки на контент |
| `moderation` | Модерация |
| `rss` | RSS-ленты |
| `sitemap` | Карта сайта |
| `widgets` | Управление виджетами |
| `activity` | Активность пользователей |
| `forms` | Пользовательские формы |
| `languages` | Языки и локализация |

---

## Система событий (`cmsEventsManager`)

### Методы
- `cmsEventsManager::hook($event, $data)` — запустить событие, получить изменённые данные
- `cmsEventsManager::hookAll($event, $data)` — запустить, получить ответы всех слушателей

### Хуки регистрируются в `hooks/` контроллера
Файл именуется по имени события: `hooks/{event_name}.php`

### Примеры событий
- `core_start` — старт CMS
- `auth_login` — авторизация
- `user_preloaded` — пользователь загружен
- `user_login` — вход пользователя
- `user_delete` — удаление пользователя
- `user_notify_types` — типы уведомлений
- `user_privacy_types` — типы приватности
- `user_tab_info` — вкладки профиля
- `content_before_childs` — перед дочерними записями
- `admin_dashboard_block` — блок дашборда
- `admin_dashboard_chart` — график дашборда
- `menu_users` / `menu_content` / `menu_admin` — пункты меню
- `cron_*` — cron-задачи
- `sitemap_sources` / `sitemap_urls` — источники карты сайта
- `fulltext_search` — полнотекстовый поиск
- `wall_after_add` / `wall_after_delete` — стена

---

## Модель (`cmsModel`) — Query Builder

```php
// Основные методы
$model->filterBy('field', 'value')        // WHERE
$model->orderBy('field', 'ASC')           // ORDER BY
$model->limitBy($n)                       // LIMIT
$model->join($table, $alias, $on)         // JOIN
$model->get($table, $callback)            // SELECT многие
$model->getOne($table, $callback)         // SELECT один
$model->add($table, $data)               // INSERT
$model->update($table, $id, $data)       // UPDATE
$model->delete($table, $id)             // DELETE
$model->getCount($table, $field)         // COUNT
$model->useCache($key)                   // кешировать запрос
```

### Соглашения об именах таблиц
- `{table}` — подстановка префикса БД (например `{users}` → `cms_users`)
- Контентные таблицы: `con_{ctype_name}` (prefix) + `_cats` (categories)

---

## Шаблоны (`templates/`)

```
templates/
├── default/         — стандартный фронтенд шаблон
│   ├── main.tpl.php — основной layout
│   ├── admin.tpl.php
│   ├── controllers/ — переопределения шаблонов контроллеров
│   ├── profiles/    — шаблоны профилей
│   ├── widgets/     — шаблоны виджетов
│   ├── css/ js/     — статика темы
│   └── manifest.php — метаданные шаблона
├── modern/          — современный шаблон
└── admincoreui/     — шаблон панели администратора
```

### Шаблоны контроллеров
```
controllers/{name}/
├── index.tpl.php
├── view.tpl.php
└── ...
```

---

## Именование классов

| Тип | Шаблон | Пример |
|---|---|---|
| Контроллер (frontend) | `{name}` | `users`, `content` |
| Контроллер (backend) | `backend{Name}` | `backendUsers` |
| Модель | `model{Name}` | `modelUsers`, `modelContent` |
| Виджет | `widget{Name}` | `widgetUsersList` |

---

## Правила работы с БД

1. Используй методы cmsModel (Query Builder), не raw SQL
2. Таблицы через `{table_name}` (с фигурными скобками)
3. Фильтрация — через `filterBy()`, `filterAvailableOnly()` и т.п.
4. Кеш — через `useCache('ключ')`
5. Безопасность — используй `$db->escape()` если нужен ручной SQL

---

## Ключевые принципы ICMS2

1. **Событийная модель**: расширение через хуки в `hooks/`
2. **Контроллер = фича**: каждая фича — отдельный контроллер
3. **Минимум изменений**: расширяй через хуки, не правь ядро
4. **Экшены**: отдельные файлы в `actions/`
5. **Формы**: объекты в `forms/`, рендеринг через `cmsForm`
6. **Backend**: отдельный контроллер, CRUD через `cmsGrid` + `cmsForm`
