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
use Randee\Menu\MenuItemTable;

Loader::includeModule('randee.menu');
Loc::loadMessages(__FILE__);

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

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

$id       = $__getInt($request, 'ID');
$menuId   = $__getInt($request, 'MENU_ID');
$parentId = $__getInt($request, 'PARENT_ID');

if (!$menuId && !$id) {
    LocalRedirect('randee_menu_menus.php?lang=' . LANGUAGE_ID);
}

$item = null;
if ($id > 0) {
    $item = MenuItemTable::getList([
        'filter' => ['=ID' => $id],
        'select' => [
            'ID',
            'MENU_ID',
            'PARENT_ID',
            'NAME',
            'LINK',
            'LINK_TYPE',
            'SORT',
            'ACTIVE',
            'PARAMS',
            'TARGET',
        ],
        'limit'  => 1,
    ])->fetch();
    if ($item) {
        $menuId   = (int)$item['MENU_ID'];
        $parentId = (int)$item['PARENT_ID'];
    }
}

$menu = MenuTable::getList([
    'filter' => ['=ID' => $menuId],
    'select' => ['ID', 'CODE', 'NAME'],
    'limit'  => 1,
])->fetch();
if (!$menu) {
    LocalRedirect('randee_menu_menus.php?lang=' . LANGUAGE_ID);
}

$APPLICATION->SetTitle($id ? 'Редактирование пункта меню' : 'Добавление пункта меню');
$APPLICATION->AddChainItem('Меню сайта', 'randee_menu_menus.php?lang=' . LANGUAGE_ID);
$APPLICATION->AddChainItem($menu['NAME'], 'randee_menu_edit.php?ID=' . $menuId . '&lang=' . LANGUAGE_ID);
$APPLICATION->AddChainItem('Пункты', 'randee_menu_items.php?MENU_ID=' . $menuId . '&lang=' . LANGUAGE_ID);

$message = '';
$error   = '';

if ($request->isPost() && check_bitrix_sessid()) {
    $postId = $__getInt($request, 'ID');
    if ($postId > 0) {
        $id = $postId;
    }
    $postMenu = $__getInt($request, 'MENU_ID');
    if ($postMenu > 0) {
        $menuId = $postMenu;
    }

    $targetRaw = (string)($request->getPost('TARGET') ?? '_self');
    $targetVal = in_array($targetRaw, ['_blank', '_parent', '_top'], true) ? $targetRaw : '_self';

    $fields = [
        'MENU_ID'   => $menuId,
        'PARENT_ID' => (int)($request->getPost('PARENT_ID') ?? 0),
        'NAME'      => trim((string)($request->getPost('NAME') ?? '')),
        'LINK'      => trim((string)($request->getPost('LINK') ?? '')),
        'LINK_TYPE' => $request->getPost('LINK_TYPE') === 'outer' ? 'outer' : 'inner',
        'SORT'      => (int)($request->getPost('SORT') ?? 100),
        'ACTIVE'    => $request->getPost('ACTIVE') === 'Y' ? 'Y' : 'N',
        'TARGET'    => $targetVal,
        'PARAMS'    => (string)($request->getPost('PARAMS') ?? ''),
    ];

    if ($fields['NAME'] === '') {
        $error = 'Укажите название пункта';
    } else {
        if ($fields['PARENT_ID'] > 0) {
            $parentItem = MenuItemTable::getList([
                'filter' => ['=ID' => $fields['PARENT_ID']],
                'select' => ['ID', 'MENU_ID'],
                'limit'  => 1,
            ])->fetch();
            if (!$parentItem || (int)$parentItem['MENU_ID'] !== $menuId) {
                $error = 'Неверный родительский пункт';
                $fields['PARENT_ID'] = $parentId;
            }
        }
        if ($id > 0 && $fields['PARENT_ID'] === $id) {
            $error = 'Пункт не может быть родителем самого себя';
            $fields['PARENT_ID'] = $parentId;
        }
    }

    if (!$error) {
        if ($id > 0) {
            $upd = MenuItemTable::update($id, $fields);
            if (!$upd->isSuccess()) {
                $error = implode(', ', $upd->getErrorMessages());
            } else {
                $message = 'Пункт обновлён';
            }
        } else {
            $add = MenuItemTable::add($fields);
            if (!$add->isSuccess()) {
                $error = implode(', ', $add->getErrorMessages());
            } else {
                $message = 'Пункт добавлен';
            }
        }
        if (!$error) {
            $item = array_merge($item ?: [], $fields);
            // Keep standard Bitrix menu (used by Aspro) in sync.
            try {
                $menuCode = (string)(MenuTable::getList([
                    'filter' => ['=ID' => $menuId],
                    'select' => ['CODE'],
                    'limit'  => 1,
                ])->fetch()['CODE'] ?? '');
                if ($menuCode !== '') {
                    BitrixMenuSync::syncToBitrixMenu($menuCode);
                }
            } catch (\Throwable $e) {
                // Non-blocking: admin edit should not fail if filesystem write is not allowed.
            }
        }
    }

    if ($error !== '') {
        $item = $item ?: [];
        $item = array_merge($item, $fields);
    }
}

if ($id > 0 && (!$item || !isset($item['ID']))) {
    $item = MenuItemTable::getList([
        'filter' => ['=ID' => $id],
        'select' => ['ID', 'MENU_ID', 'PARENT_ID', 'NAME', 'LINK', 'LINK_TYPE', 'SORT', 'ACTIVE', 'PARAMS', 'TARGET'],
        'limit'  => 1,
    ])->fetch() ?: null;
    if ($item) {
        $menuId   = (int)$item['MENU_ID'];
        $parentId = (int)$item['PARENT_ID'];
    }
}

$parentItems = [];
$rs = MenuItemTable::getList([
    'filter' => ['MENU_ID' => $menuId],
    'order'  => ['SORT' => 'ASC', 'ID' => 'ASC'],
]);
while ($row = $rs->fetch()) {
    if ((int)$row['ID'] !== $id) {
        $parentItems[$row['ID']] = str_repeat('· ', max(0, (int)$row['DEPTH_LEVEL'] - 1)) . $row['NAME'];
    }
}

$aTabs = [
    ['DIV' => 'edit1', 'TAB' => 'Пункт меню', 'ICON' => 'main_settings', 'TITLE' => 'Параметры'],
];

$tabControl = new CAdminTabControl('randee_menu_item_edit', $aTabs);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

if ($message) {
    CAdminMessage::ShowMessage($message);
}
if ($error) {
    CAdminMessage::ShowMessage(['MESSAGE' => $error, 'TYPE' => 'ERROR']);
}
?>

<form method="post" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>?ID=<?= (int)$id ?>&MENU_ID=<?= (int)$menuId ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="MENU_ID" value="<?= (int)$menuId ?>">
    <?php if ($id > 0): ?>
        <input type="hidden" name="ID" value="<?= (int)$id ?>">
    <?php endif; ?>
    <?php $tabControl->Begin(); ?>
    <?php $tabControl->BeginNextTab(); ?>

    <tr>
        <td width="40%">Родительский пункт:</td>
        <td>
            <select name="PARENT_ID">
                <option value="0">— Корневой уровень —</option>
                <?php foreach ($parentItems as $pid => $pname): ?>
                    <option value="<?= (int)$pid ?>" <?= $parentId == (int)$pid ? 'selected' : '' ?>><?= htmlspecialcharsbx($pname) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td>Название <span class="required">*</span>:</td>
        <td><input type="text" name="NAME" value="<?= htmlspecialcharsbx((string)($item['NAME'] ?? '')) ?>" size="50"></td>
    </tr>
    <tr>
        <td>Ссылка:</td>
        <td>
            <input type="text" name="LINK" value="<?= htmlspecialcharsbx((string)($item['LINK'] ?? '')) ?>" size="50" placeholder="/catalog/ или https://...">
            <small>Относительная (/) или абсолютная (https://)</small>
        </td>
    </tr>
    <tr>
        <td>Тип ссылки:</td>
        <td>
            <select name="LINK_TYPE">
                <option value="inner" <?= ($item['LINK_TYPE'] ?? 'inner') === 'inner' ? 'selected' : '' ?>>Внутренняя</option>
                <option value="outer" <?= ($item['LINK_TYPE'] ?? '') === 'outer' ? 'selected' : '' ?>>Внешняя</option>
            </select>
        </td>
    </tr>
    <tr>
        <td>Открывать в:</td>
        <td>
            <select name="TARGET">
                <option value="_self" <?= ($item['TARGET'] ?? '_self') === '_self' ? 'selected' : '' ?>>Текущее окно</option>
                <option value="_blank" <?= ($item['TARGET'] ?? '') === '_blank' ? 'selected' : '' ?>>Новое окно</option>
                <option value="_parent" <?= ($item['TARGET'] ?? '') === '_parent' ? 'selected' : '' ?>>Родитель</option>
                <option value="_top" <?= ($item['TARGET'] ?? '') === '_top' ? 'selected' : '' ?>>Верхний уровень</option>
            </select>
        </td>
    </tr>
    <tr>
        <td>Сортировка:</td>
        <td><input type="number" name="SORT" value="<?= (int)($item['SORT'] ?? 100) ?>" size="10"></td>
    </tr>
    <tr>
        <td>Активность:</td>
        <td>
            <input type="checkbox" name="ACTIVE" value="Y" <?= ($item['ACTIVE'] ?? 'Y') === 'Y' ? 'checked' : '' ?>>
        </td>
    </tr>
    <tr>
        <td>Доп. параметры (JSON):</td>
        <td>
            <textarea name="PARAMS" rows="4" cols="60" placeholder='{"class":"nav-link","data-id":"1"}'><?= htmlspecialcharsbx((string)($item['PARAMS'] ?? '')) ?></textarea>
            <small>Произвольные атрибуты для ссылки в формате JSON</small>
        </td>
    </tr>

    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="apply" value="Сохранить" class="adm-btn-save">
    <input type="button" value="К списку" onclick="location.href='randee_menu_items.php?MENU_ID=<?= (int)$menuId ?>&lang=<?= LANGUAGE_ID ?>'">
    <?php $tabControl->End(); ?>
</form>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'; ?>
