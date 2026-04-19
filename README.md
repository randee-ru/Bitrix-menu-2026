# Bitrix-menu-2026

Модуль **1С-Битрикс: «Randee Menu»** (`randee.menu`) — управление несколькими многоуровневыми меню из админки (коды меню, дерево пунктов, ссылки, JSON-параметры, кеш). В репозитории лежит **только** этот модуль; шаблон **`mega_menu`** включён (левый список разделов + правая панель подменю в стиле мегаменю).

**English:** Bitrix D7 module for custom hierarchical menus with admin UI, ORM, and `randee.menu:menu` component (templates include `mega_menu`).

---

## Возможности

- Несколько меню по **уникальному коду** (`main`, `footer`, …).
- Любая **вложенность** пунктов, сортировка, drag-and-drop в админке.
- Внутренние и внешние ссылки, `target`, доп. атрибуты в **JSON**.
- Публичный компонент **`randee.menu:menu`** с шаблонами (в т.ч. **`mega_menu`**).

Подробности по API и таблицам БД — в [`randee.menu/README.md`](randee.menu/README.md).

---

## Требования

- **1С-Битрикс** с **D7** (ORM, `Bitrix\Main\Loader`).
- **PHP 8.2+**
- **MySQL 5.7+** / **8.x** или **MariaDB**.

---

## Установка на сайт

### Вариант A — из репозитория (клонирование)

1. Склонируйте репозиторий (или скачайте ZIP с GitHub **Code → Download ZIP**).
2. Скопируйте каталог **`randee.menu`** в корень сайта:
   ```text
   /local/modules/randee.menu/
   ```
3. В админке: **Настройки → Настройки продукта → Модули** → найдите **Randee Menu** (`randee.menu`) → **Установить**.

Установщик создаст таблицы, скопирует файлы в **`/bitrix/admin/`** и компонент в **`/local/components/randee.menu/`**.

4. **Сервисы → Меню сайта** — создайте меню (код латиницей), добавьте пункты.

### Вариант B — архив модуля

В каталоге проекта можно собрать ZIP только модуля:

```bash
chmod +x scripts/build_randee_menu_zip.sh
./scripts/build_randee_menu_zip.sh
```

Появится файл **`dist/randee.menu-<версия>.zip`**. Распакуйте и положите папку **`randee.menu`** в **`/local/modules/`**, затем установите модуль из админки (шаг 3 выше).

Краткая шпаргалка также в **[`randee.menu/INSTALL.txt`](randee.menu/INSTALL.txt)**.

---

## Подключение мегаменю в шаблоне

```php
<?php
$APPLICATION->IncludeComponent(
    'randee.menu:menu',
    'mega_menu',
    [
        'MENU_CODE'    => 'main',
        'ACTIVE_ONLY'  => 'Y',
        'CACHE_TIME'   => 3600,
        'CACHE_GROUPS' => 'N',
    ]
);
?>
```

Код **`main`** замените на код своего меню из админки.

---

## Публикация на GitHub (новый репозиторий)

1. На [github.com/new](https://github.com/new) создайте репозиторий **`Bitrix-menu-2026`** (без README, если уже есть локальный коммит).
2. В терминале на машине, где лежит клон этого репозитория:

```bash
cd bitrix-menu-2026
git remote add origin https://github.com/<ВАШ_ЛОГИН>/Bitrix-menu-2026.git
git branch -M main
git push -u origin main
```

Подставьте свой логин и при необходимости используйте SSH: `git@github.com:<USER>/Bitrix-menu-2026.git`.

---

## Удаление модуля

Через **Настройки → Модули** — удаление `randee.menu`. По желанию вручную удалите **`/local/modules/randee.menu`**, если каталог остался. Подробности — в `randee.menu/README.md`.

---

## Лицензия

Используйте и распространяйте в рамках своей политики; при публикации на GitHub можно оформить отдельный `LICENSE` (MIT и т.д.) по согласованию с правообладателем кода.
