<?php

declare(strict_types=1);

/**
 * Простая строка пунктов (аналог header-menu-view2) — Randee BEM.
 *
 * @var array $arResult
 * @var array $arParams
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(true);

$docRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
$relCss = '/local/components/randee.menu/menu/templates/menu_new_2/style.css';
if ($docRoot !== '' && is_file($docRoot . $relCss) && method_exists($this, 'addExternalCss')) {
    $this->addExternalCss($relCss);
}

$items = $arResult['ITEMS'] ?? [];
if (!is_array($items) || $items === []) {
    return;
}

$arTheme = (isset($GLOBALS['arTheme']) && is_array($GLOBALS['arTheme'])) ? $GLOBALS['arTheme'] : [];
$iVisible = (int)($arTheme['MAX_VISIBLE_ITEMS_MENU']['VALUE'] ?? 10);
if ($iVisible < 1) {
    $iVisible = 10;
}
$items = array_slice($items, 0, $iVisible);

$normalizeLink = static function (array $item): string {
    $linkRaw = (string)($item['LINK'] ?? '');
    $linkType = (string)($item['LINK_TYPE'] ?? 'inner');
    if ($linkRaw === '') {
        return '#';
    }
    if ($linkType === 'inner' && $linkRaw !== '#' && strpos($linkRaw, 'http') !== 0) {
        return $linkRaw[0] === '/' ? $linkRaw : '/' . $linkRaw;
    }
    return $linkRaw;
};

$renderAttrs = static function (array $params): string {
    $attrs = '';
    foreach ($params as $k => $v) {
        if (is_scalar($v)) {
            $attrs .= ' ' . htmlspecialcharsbx((string)$k) . '="' . htmlspecialcharsbx((string)$v) . '"';
        }
    }
    return $attrs;
};

global $APPLICATION;
$curUrl = (is_object($APPLICATION) && method_exists($APPLICATION, 'GetCurPage'))
    ? (string)$APPLICATION->GetCurPage(true)
    : '';

$isMatch = static function (string $link, string $curUrl): bool {
    $link = trim($link);
    $curUrl = trim($curUrl);
    if ($link === '' || $link === '#' || $curUrl === '') {
        return false;
    }
    if ($link === $curUrl) {
        return true;
    }
    $linkDir = rtrim($link, '/') . '/';
    $curDir = rtrim($curUrl, '/') . '/';
    return str_starts_with($curDir, $linkDir);
};

?>
<div class="randee-menu randee-menu--view2">
    <div class="randee-menu__v2-wrap">
        <?php foreach ($items as $item):
            if (!is_array($item)) {
                continue;
            }
            $params = is_array($item['PARAMS'] ?? null) ? ($item['PARAMS'] ?? []) : [];
            $bWideMenu = (($params['WIDE_MENU'] ?? '') === 'Y');
            $link = $normalizeLink($item);
            $attrs = $renderAttrs($params);
            $sel = $isMatch($link, $curUrl);
            $ic = 'randee-menu__v2-item';
            if ($bWideMenu) {
                $ic .= ' randee-menu__v2-item--wide';
            }
            if ($sel) {
                $ic .= ' randee-menu__v2-item--current';
            }
            ?>
            <div class="<?= $ic ?>">
                <a class="randee-menu__v2-link"
                    href="<?= htmlspecialcharsbx($link) ?>"
                    <?= !empty($item['TARGET']) ? ' target="' . htmlspecialcharsbx((string)$item['TARGET']) . '"' : '' ?>
                    <?= $attrs ?>
                >
                    <span class="randee-menu__v2-text"><?= htmlspecialcharsbx((string)($item['NAME'] ?? '')) ?></span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
