<?php
/**
 * @author Randee
 * @copyright 2025
 */

if (!defined('ADMIN_MODULE_NAME')) {
    define('ADMIN_MODULE_NAME', 'randee.menu');
}

// В админке иногда OPcache не успевает инвалидировать изменения. Принудительно инвалидируем текущий файл.
if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__FILE__, true);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Randee\Menu\BitrixMenuSync;
use Randee\Menu\MenuTable;

Loader::includeModule('randee.menu');
Loc::loadMessages(__FILE__);

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

/**
 * Надёжное чтение ID (GET, POST, $_GET/$_POST) — для сайдпанели и отправки формы без query-string
 */
$__getInt = static function ($request, string $key): int {
    $q = $request->getQuery($key);
    if ($q !== null && $q !== '') {
        return (int)$q;
    }
    $p = $request->getPost($key);
    if ($p !== null && $p !== '') {
        return (int)$p;
    }
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        return (int)$_GET[$key];
    }
    if (isset($_POST[$key]) && $_POST[$key] !== '') {
        return (int)$_POST[$key];
    }

    return 0;
};

$id   = $__getInt($request, 'ID');
$menu = null;
$message = '';
$error   = '';

// На некоторых страницах админки Bitrix может по-разному отдавать параметры в HttpRequest.
// Подстрахуемся и при наличии явно возьмем из superglobals.
$id = (int)($_REQUEST['ID'] ?? $id);

if ($request->isPost() && check_bitrix_sessid()) {
    $postId = $__getInt($request, 'ID');
    if ($postId > 0) {
        $id = $postId;
    }

    $fields = [
        'CODE'        => trim((string)($request->getPost('CODE') ?? '')),
        'NAME'        => trim((string)($request->getPost('NAME') ?? '')),
        'DESCRIPTION' => trim((string)($request->getPost('DESCRIPTION') ?? '')),
        'SORT'        => (int)($request->getPost('SORT') ?? 100),
        'ACTIVE'      => $request->getPost('ACTIVE') === 'Y' ? 'Y' : 'N',
    ];

    if ($fields['CODE'] === '') {
        $error = 'Укажите код меню';
    } elseif ($fields['NAME'] === '') {
        $error = 'Укажите название';
    } else {
        $exists = MenuTable::getList([
            'filter' => ['=CODE' => $fields['CODE']],
            'limit'  => 1,
        ])->fetch();

        if ($exists && (int)$exists['ID'] !== $id) {
            $error = 'Меню с таким кодом уже существует';
        }
    }

    if (!$error) {
        if ($id > 0) {
            $upd = MenuTable::update($id, $fields);
            if (!$upd->isSuccess()) {
                $error = implode(', ', $upd->getErrorMessages());
            } else {
                try {
                    if ($fields['CODE'] !== '') {
                        BitrixMenuSync::syncToBitrixMenu($fields['CODE']);
                    }
                } catch (\Throwable $e) {
                    // Non-blocking
                }
                LocalRedirect('randee_menu_edit.php?ID=' . $id . '&lang=' . LANGUAGE_ID . '&saved=Y');
            }
        } else {
            $add = MenuTable::add($fields);
            if (!$add->isSuccess()) {
                $error = implode(', ', $add->getErrorMessages());
            } else {
                try {
                    if ($fields['CODE'] !== '') {
                        BitrixMenuSync::syncToBitrixMenu($fields['CODE']);
                    }
                } catch (\Throwable $e) {
                    // Non-blocking
                }
                LocalRedirect('randee_menu_edit.php?ID=' . (int)$add->getId() . '&lang=' . LANGUAGE_ID . '&saved=Y');
            }
        }
    }
}

if ($id > 0) {
    $menu = MenuTable::getList([
        'filter' => ['=ID' => $id],
        'select' => ['ID', 'CODE', 'NAME', 'DESCRIPTION', 'SORT', 'ACTIVE'],
        'limit'  => 1,
    ])->fetch();
    if (!$menu) {
        $id = 0;
    } elseif ($request->getQuery('saved') === 'Y') {
        $message = 'Меню успешно сохранено';
    }
}

$APPLICATION->SetTitle($id ? 'Редактирование меню' : 'Добавление меню');

$aTabs = [
    ['DIV' => 'edit1', 'TAB' => 'Меню', 'ICON' => 'main_settings', 'TITLE' => 'Параметры меню'],
];

$tabControl = new CAdminTabControl('randee_menu_edit', $aTabs);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

if ($message) {
    CAdminMessage::ShowMessage($message);
}
if ($error) {
    CAdminMessage::ShowMessage(['MESSAGE' => $error, 'TYPE' => 'ERROR']);
}
?>

<?php
// ORM fetch иногда может вернуть ключи в другом регистре — подстрахуемся.
$code        = (string)($menu['CODE'] ?? $menu['code'] ?? '');
$name        = (string)($menu['NAME'] ?? $menu['name'] ?? '');
$description = (string)($menu['DESCRIPTION'] ?? $menu['description'] ?? '');
$sort        = (int)($menu['SORT'] ?? $menu['sort'] ?? 100);
$active      = (string)($menu['ACTIVE'] ?? $menu['active'] ?? 'Y');
?>

<form method="post" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>?ID=<?= (int)$id ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <?php if ($id > 0): ?>
        <input type="hidden" name="ID" value="<?= (int)$id ?>">
    <?php endif; ?>
    <?php $tabControl->Begin(); ?>
    <?php $tabControl->BeginNextTab(); ?>

    <tr>
        <td width="40%">Код <span class="required">*</span>:</td>
        <td>
            <input type="text" name="CODE" value="<?= htmlspecialcharsbx($code) ?>" size="40" maxlength="50" <?= $id ? 'readonly' : '' ?>>
            <small>Уникальный идентификатор (main, mobile, left и т.д.)</small>
        </td>
    </tr>
    <tr>
        <td>Название <span class="required">*</span>:</td>
        <td><input type="text" name="NAME" value="<?= htmlspecialcharsbx($name) ?>" size="40"></td>
    </tr>
    <tr>
        <td>Описание:</td>
        <td><textarea name="DESCRIPTION" rows="3" cols="50"><?= htmlspecialcharsbx($description) ?></textarea></td>
    </tr>
    <tr>
        <td>Сортировка:</td>
        <td><input type="number" name="SORT" value="<?= $sort ?>" size="10"></td>
    </tr>
    <tr>
        <td>Активность:</td>
        <td>
            <input type="checkbox" name="ACTIVE" value="Y" <?= ($active === 'Y') ? 'checked' : '' ?>>
        </td>
    </tr>

    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="apply" value="Сохранить" class="adm-btn-save">
    <?php if ($id): ?>
        <input type="button" value="Пункты меню" onclick="location.href='randee_menu_items.php?MENU_ID=<?= (int)$id ?>&lang=<?= LANGUAGE_ID ?>'">
    <?php endif; ?>
    <input type="button" value="Список" onclick="location.href='randee_menu_menus.php?lang=<?= LANGUAGE_ID ?>'">
    <?php $tabControl->End(); ?>
</form>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'; ?>
