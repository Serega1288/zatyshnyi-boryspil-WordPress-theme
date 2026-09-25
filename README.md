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

## Заявки та сповіщення

Форма спочатку зберігає звернення у приватному розділі `Заявки` в адмінці WordPress. Там доступні повні дані, статус опрацювання та окремий стан доставки через кожен канал.

Канали налаштовуються в `Налаштування теми → Сповіщення заявок`:

- для email вкажіть адресу, налаштуйте SMTP на сервері або окремим плагіном і ввімкніть перемикач; тема використовує стандартний `wp_mail()`;
- для Telegram вкажіть токен бота, Chat ID групи, за потреби ID теми форуму, додайте бота до групи та ввімкніть перемикач;
- збережений Telegram-токен повторно не показується; порожнє поле його не змінює, а окремий перемикач дозволяє видалити;
- сповіщення надсилаються фоновою чергою після збереження заявки. Помилка каналу не видаляє заявку, а ненадіслані канали можна повторити з її картки.

Безпечна інтеграційна перевірка не робить реальних зовнішніх запитів — email і Telegram повністю підміняються тестовими транспортами:

```bash
docker compose run --rm --no-deps --entrypoint wp wpcli eval-file wp-content/themes/project-theme/inc/migrations/site/check-leads.php --path=/var/www/html --allow-root
```

## Перевірка JavaScript

```bash
npm test
```

Стара тема та пов’язані з нею перевірочні скрипти збережені в папці `old`.

## Деплой теми через Deployer for Git

Гілка `theme-deploy` містить лише вміст `project-theme` у корені. Після коміту змін у `main` її потрібно зібрати й опублікувати так:

```powershell
$sourceCommit = git rev-parse main
$deployCommit = git subtree split --prefix=project-theme $sourceCommit
git diff --exit-code "${sourceCommit}:project-theme" "${deployCommit}^{tree}"
git push origin "${deployCommit}:refs/heads/theme-deploy"
```

Налаштування пакета в плагіні:

- тип пакета: `Theme`;
- провайдер: `GitHub`;
- репозиторій: `https://github.com/Serega1288/zatyshnyi-boryspil-WordPress-theme` без `.git`;
- гілка: `theme-deploy`;
- `Miscellaneous → Flush cache`: увімкнено;
- для автоматичного деплою додати `Push-to-Deploy/Webhook URL` плагіна до GitHub Webhooks з типом `application/json` і подією `push`.

Deployer for Git формує папку теми з назви репозиторію: `zatyshnyi-boryspil-WordPress-theme`. Після першого встановлення цю тему потрібно активувати та перевірити призначення меню. Версійні зміни ACF-контенту застосовуються окремо командою імпорту з фактичної папки активної теми.
