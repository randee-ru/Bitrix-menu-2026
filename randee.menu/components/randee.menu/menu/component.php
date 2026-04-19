<?php
/**
 * @author Randee
 * @copyright 2025
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Randee\Menu\MenuManager;

if (!Loader::includeModule('randee.menu')) {
    return;
}

$arParams['MENU_CODE']   = trim($arParams['MENU_CODE'] ?? 'main');
$arParams['ACTIVE_ONLY'] = ($arParams['ACTIVE_ONLY'] ?? 'Y') === 'Y';
$arParams['CACHE_TIME']  = (int)($arParams['CACHE_TIME'] ?? 3600);
$arParams['CACHE_GROUPS'] = ($arParams['CACHE_GROUPS'] ?? 'N') === 'Y';

$cacheId = 'randee_menu_' . $arParams['MENU_CODE'];
$cache   = Application::getInstance()->getManagedCache();
$cacheDir = '/randee.menu/' . $arParams['MENU_CODE'];

if ($arParams['CACHE_TIME'] > 0 && $cache->initCache($arParams['CACHE_TIME'], $cacheId, $cacheDir)) {
    $arResult = $cache->getVars();
} elseif ($cache->startDataCache()) {
    $arResult['ITEMS'] = MenuManager::getMenuTree($arParams['MENU_CODE'], $arParams['ACTIVE_ONLY']);

    if ($arParams['CACHE_GROUPS'] && $GLOBALS['USER']->IsAuthorized()) {
        $cache->abortDataCache();
    } else {
        $cache->endDataCache($arResult);
    }
}

$this->IncludeComponentTemplate();
