<?php
/**
 * @author Randee
 * @copyright 2025
 */

if (!defined('ADMIN_MODULE_NAME')) {
    define('ADMIN_MODULE_NAME', 'randee.menu');
}

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Randee\Menu\BitrixMenuSync;
use Randee\Menu\MenuTable;
use Randee\Menu\MenuItemTable;
use Randee\Menu\MenuManager;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loader::includeModule('randee.menu');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$menuId  = (int)$request['MENU_ID'];

if (!$menuId) {
    LocalRedirect('randee_menu_menus.php?lang=' . LANGUAGE_ID);
}

$menu = MenuTable::getById($menuId)->fetch();
if (!$menu) {
    LocalRedirect('randee_menu_menus.php?lang=' . LANGUAGE_ID);
}

if ($request->isPost() && check_bitrix_sessid() && $request->getPost('action') === 'reorder_tree') {
    header('Content-Type: application/json; charset=UTF-8');
    try {
        $tree = Json::decode((string)$request->getPost('tree'));
        if (!is_array($tree)) {
            throw new \Bitrix\Main\SystemException('Неверные данные дерева');
        }
        MenuManager::applyAdminTreeOrder($menuId, $tree);
        if (!empty($menu['CODE'])) {
            MenuManager::clearCache($menu['CODE']);
            try {
                BitrixMenuSync::syncToBitrixMenu($menu['CODE']);
            } catch (\Throwable $e) {
                // non-blocking
            }
        } else {
            MenuManager::clearCache('');
        }
        echo Json::encode(['success' => true]);
    } catch (\Throwable $e) {
        echo Json::encode([
            'success' => false,
            'message' => $e->getMessage(),
        ]);
    }
    die();
}

if ($request['action'] === 'delete' && check_bitrix_sessid()) {
    $delId = (int)$request['ID'];
    if ($delId) {
        $delItem = MenuItemTable::getById($delId)->fetch();
        if ($delItem && (int)$delItem['MENU_ID'] === $menuId) {
            MenuItemTable::delete($delId);
            if (!empty($menu['CODE'])) {
                try {
                    BitrixMenuSync::syncToBitrixMenu($menu['CODE']);
                } catch (\Throwable $e) {
                    // non-blocking
                }
            }
        }
        LocalRedirect('randee_menu_items.php?MENU_ID=' . $menuId . '&lang=' . LANGUAGE_ID);
    }
}

$APPLICATION->SetTitle('Пункты меню: ' . $menu['NAME']);
$APPLICATION->AddChainItem('Меню сайта', 'randee_menu_menus.php?lang=' . LANGUAGE_ID);
$APPLICATION->AddChainItem($menu['NAME'], 'randee_menu_edit.php?ID=' . $menuId . '&lang=' . LANGUAGE_ID);
$APPLICATION->AddChainItem('Пункты меню');

$tree = MenuManager::getAdminTree($menuId);

/**
 * @param array<int, array<string,mixed>> $branch
 */
function randee_menu_render_tree_ol(array $branch, int $menuId): string
{
    $html = '<ol class="randee-menu-tree__list nested-sortable">';
    foreach ($branch as $item) {
        $html .= randee_menu_render_li($item, $menuId);
    }
    $html .= '</ol>';

    return $html;
}

/**
 * @param array<string,mixed> $item
 */
function randee_menu_render_li(array $item, int $menuId): string
{
    $id       = (int)$item['ID'];
    $children = $item['CHILDREN'] ?? [];
    $editUrl  = 'randee_menu_item_edit.php?ID=' . $id . '&MENU_ID=' . $menuId . '&lang=' . LANGUAGE_ID;
    $name     = htmlspecialcharsbx($item['NAME']);
    $linkRaw  = (string)($item['LINK'] ?? '');
    $link     = $linkRaw !== '' ? htmlspecialcharsbx($linkRaw) : '—';
    $active   = ($item['ACTIVE'] === 'Y') ? 'Да' : 'Нет';
    $depth    = (int)$item['DEPTH_LEVEL'];
    $delUrl   = 'randee_menu_items.php?action=delete&ID=' . $id . '&MENU_ID=' . $menuId . '&' . bitrix_sessid_get();

    $html  = '<li class="randee-menu-tree__item" data-id="' . $id . '">';
    $html .= '<div class="randee-menu-tree__row">';
    $html .= '<span class="randee-menu-tree__handle" title="Перетащить">⠿</span>';
    $html .= '<span class="randee-menu-tree__id">' . $id . '</span>';
    $html .= '<a class="randee-menu-tree__name" href="' . htmlspecialcharsbx($editUrl) . '">' . $name . '</a>';
    $html .= '<span class="randee-menu-tree__link">' . $link . '</span>';
    $html .= '<span class="randee-menu-tree__meta">ур. ' . $depth . ' · ' . $active . '</span>';
    $html .= '<span class="randee-menu-tree__actions">';
    $html .= '<a href="' . htmlspecialcharsbx($editUrl) . '">Изменить</a>';
    $html .= ' · <a href="' . htmlspecialcharsbx($delUrl) . '" onclick="return confirm(\'Удалить пункт меню?\');">Удалить</a>';
    $html .= '</span>';
    $html .= '</div>';

    if ($children) {
        $html .= randee_menu_render_tree_ol($children, $menuId);
    } else {
        $html .= '<ol class="randee-menu-tree__list nested-sortable"></ol>';
    }
    $html .= '</li>';

    return $html;
}

$addUrl = 'randee_menu_item_edit.php?MENU_ID=' . $menuId . '&PARENT_ID=0&lang=' . LANGUAGE_ID;

$jsBase = '/local/modules/randee.menu/js/';
if (!is_file($_SERVER['DOCUMENT_ROOT'] . $jsBase . 'sortable.min.js')) {
    $jsBase = '/bitrix/modules/randee.menu/js/';
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
?>
<style>
.randee-menu-tree-toolbar { margin: 12px 0 16px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.randee-menu-tree-toolbar .adm-btn-save { margin: 0; }
.randee-menu-tree-hint { color: #d97706; font-size: 13px; display: none; }
#randee-menu-tree-root { margin-top: 8px; padding: 8px 0 24px; }
.randee-menu-tree__list {
	list-style: none;
	margin: 0;
	padding-left: 0;
	min-height: 8px;
}
.randee-menu-tree__list .randee-menu-tree__list { padding-left: 20px; margin-top: 4px; }
.randee-menu-tree__item { margin: 4px 0; }
.randee-menu-tree__row {
	display: grid;
	grid-template-columns: 24px 48px 1fr 1fr 140px 180px;
	align-items: center;
	gap: 8px;
	padding: 8px 10px;
	background: #f9fafb;
	border: 1px solid #e5e7eb;
	border-radius: 4px;
}
.randee-menu-tree__handle {
	cursor: grab;
	user-select: none;
	color: #6b7280;
	font-size: 14px;
	text-align: center;
}
.randee-menu-tree__handle:active { cursor: grabbing; }
.randee-menu-tree__id { color: #6b7280; font-size: 12px; }
.randee-menu-tree__name { font-weight: 600; text-decoration: none; }
.randee-menu-tree__link { font-size: 13px; color: #374151; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.randee-menu-tree__meta { font-size: 12px; color: #6b7280; }
.randee-menu-tree__actions { font-size: 12px; white-space: nowrap; }
.sortable-ghost { opacity: 0.45; }
.sortable-chosen .randee-menu-tree__row { background: #eef2ff; border-color: #c7d2fe; }
</style>

<div class="randee-menu-tree-toolbar">
	<a class="adm-btn adm-btn-save" href="<?= htmlspecialcharsbx($addUrl) ?>">+ Добавить пункт</a>
	<?php if ($tree): ?>
	<input type="button" class="adm-btn-save" id="randee-menu-tree-save" value="Сохранить порядок (вложенность)">
	<span class="randee-menu-tree-hint" id="randee-menu-tree-dirty">Изменения не сохранены — нажмите «Сохранить порядок»</span>
	<?php endif; ?>
</div>

<?php if ($tree): ?>
<div id="randee-menu-tree-root">
	<?= randee_menu_render_tree_ol($tree, $menuId) ?>
</div>
<script src="<?= htmlspecialcharsbx($jsBase) ?>sortable.min.js"></script>
<script src="<?= htmlspecialcharsbx($jsBase) ?>menu_admin_tree.js"></script>
<script>
BX.ready(function () {
	if (window.RandeeMenuAdminTree) {
		RandeeMenuAdminTree.init({
			ajaxUrl: <?= Json::encode($APPLICATION->GetCurPage()) ?>,
			menuId: <?= (int)$menuId ?>,
			lang: <?= Json::encode(LANGUAGE_ID) ?>
		});
	}
});
</script>
<?php else: ?>
<p>Пунктов пока нет. <a href="<?= htmlspecialcharsbx($addUrl) ?>">Добавить первый пункт</a></p>
<?php endif; ?>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
