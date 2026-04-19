<?php
/**
 * @author Randee
 * @copyright 2025
 */

if (!defined('ADMIN_MODULE_NAME')) {
    define('ADMIN_MODULE_NAME', 'randee.menu');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Loader;
use Randee\Menu\BitrixMenuSync;
use Randee\Menu\MenuTable;

Loader::includeModule('randee.menu');

$APPLICATION->SetTitle('Меню сайта');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

if ($request['action'] === 'delete' && check_bitrix_sessid()) {
    $id = (int)$request['ID'];
    if ($id) {
        $menuCode = (string)(MenuTable::getList([
            'filter' => ['=ID' => $id],
            'select' => ['CODE'],
            'limit'  => 1,
        ])->fetch()['CODE'] ?? '');
        MenuTable::delete($id);
        if ($menuCode !== '') {
            try {
                BitrixMenuSync::syncToBitrixMenu($menuCode);
            } catch (\Throwable $e) {
                // Non-blocking
            }
        }
        LocalRedirect('randee_menu_menus.php?lang=' . LANGUAGE_ID);
    }
}

$list = new CAdminList('randee_menu_list');
$rs = MenuTable::getList([
    'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
]);

$list->AddHeaders([
    ['id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true],
    ['id' => 'CODE', 'content' => 'Код', 'sort' => 'CODE', 'default' => true],
    ['id' => 'NAME', 'content' => 'Название', 'sort' => 'NAME', 'default' => true],
    ['id' => 'SORT', 'content' => 'Сортировка', 'sort' => 'SORT', 'default' => true],
    ['id' => 'ACTIVE', 'content' => 'Активность', 'sort' => 'ACTIVE', 'default' => true],
]);

while ($row = $rs->fetch()) {
    $editUrl = 'randee_menu_edit.php?ID=' . $row['ID'] . '&lang=' . LANGUAGE_ID;
    $itemsUrl = 'randee_menu_items.php?MENU_ID=' . $row['ID'] . '&lang=' . LANGUAGE_ID;

    $row['ACTIVE'] = $row['ACTIVE'] === 'Y' ? 'Да' : 'Нет';

    $actionList = [
        [
            // Иконка карандаша (как было у "Редактировать"), но текст — "Пункты меню"
            'ICON' => 'edit',
            'TEXT' => 'Пункты меню',
            'LINK' => $itemsUrl,
            'DEFAULT' => true,
        ],
        [
            'ICON'   => 'delete',
            'TEXT'   => 'Удалить',
            'ACTION' => "if(confirm('Удалить меню?')) location.href='randee_menu_menus.php?action=delete&ID=" . (int)$row['ID'] . "&lang=" . LANGUAGE_ID . "&" . bitrix_sessid_get() . "';",
        ],
    ];

    // Чтобы при клике/двойном клике открывались "Пункты меню", а не "Редактирование"
    $adminRow = $list->AddRow($row['ID'], $row, $itemsUrl, 'Пункты меню');
    $adminRow->AddActions($actionList);
}

$list->AddAdminContextMenu([
    [
        'TEXT' => 'Добавить меню',
        'LINK' => 'randee_menu_edit.php?lang=' . LANGUAGE_ID,
        'TITLE' => 'Добавить новое меню',
        'ICON'  => 'btn_new',
    ],
]);

$list->CheckListMode();

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
?>
<?php $list->DisplayList(); ?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'; ?>
