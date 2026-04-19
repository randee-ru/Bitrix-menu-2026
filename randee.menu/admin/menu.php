<?php
/**
 * @author Randee
 * @copyright 2025
 */

IncludeModuleLangFile(__FILE__);

$aMenu = [
    'parent_menu' => 'global_menu_services',
    'sort'        => 100,
    'text'        => GetMessage('RANDEE_MENU_ADMIN_MENU_TEXT'),
    'title'       => GetMessage('RANDEE_MENU_ADMIN_MENU_TITLE'),
    'url'         => 'randee_menu_menus.php?lang=' . LANGUAGE_ID,
    // Чтобы пункт считался активным также на страницах редактирования/пунктов меню
    'more_url'    => [
        'randee_menu_edit.php?lang=' . LANGUAGE_ID,
        'randee_menu_items.php?lang=' . LANGUAGE_ID,
        'randee_menu_item_edit.php?lang=' . LANGUAGE_ID,
    ],
    'icon'        => 'sys_menu_icon',
    'page_icon'   => 'sys_page_icon',
    'items_id'    => 'menu_randee_menu',
];

return $aMenu;
