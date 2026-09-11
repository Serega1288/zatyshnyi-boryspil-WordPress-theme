# zatyshnyi-boryspil-WordPress-theme

Односторінкова WordPress-тема ЖК «Затишний Бориспіль», інтегрована з готової HTML-верстки. Контент сторінки редагується через ACF Pro Flexible Content, а header, footer і попап — через глобальні ACF-налаштування.

## Локальний запуск

```bash
npm install
npm run wp:up
```

WordPress: `http://localhost:8080`  
Adminer: `http://localhost:8081`

## Початкове наповнення WordPress

Після першого запуску активуйте тему й один раз виконайте імпорт. Команда є ідемпотентною та не перезаписує вже імпортований редакторський контент.

```bash
docker compose run --rm --no-deps --entrypoint wp wpcli theme activate project-theme --path=/var/www/html --allow-root
docker compose run --rm --no-deps --entrypoint wp wpcli eval-file wp-content/themes/project-theme/inc/migrations/site/import.php --path=/var/www/html --allow-root
```

Імпорт створює сторінку «Головна», призначає шаблон `Constructor`, переносить зображення до медіабібліотеки, заповнює 9 секцій, створює якірні меню та налаштовує статичну головну сторінку.

## Редагування

- `ACF → Групи полів → Конструктор сторінки` — структура секцій.
- `Сторінки → Головна` — порядок і контент секцій.
- `Налаштування теми` — header, footer і попап заявки.
- `Вигляд → Меню` — якірні пункти навігації.

У кожній секції є поле `ID секції (якір)`. У меню вказуйте те саме значення із символом `#`, наприклад `#apartments`.

## Перевірка JavaScript

```bash
npm test
```

Стара тема та пов’язані з нею перевірочні скрипти збережені в папці `old`.
