<?php

declare(strict_types=1);

/**
 * @author Randee
 * @var array $arResult
 * @var array $arParams
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(true);

$docRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
$relCss = '/local/components/randee.menu/menu/templates/.default/style.css';
if ($docRoot !== '' && is_file($docRoot . $relCss) && method_exists($this, 'addExternalCss')) {
    $this->addExternalCss($relCss);
}

$items = $arResult['ITEMS'] ?? [];
if ($items === [] || !is_array($items)) {
    return;
}

$renderItem = function ($item, $level = 0) use (&$renderItem) {
    $link = $item['LINK'] ?: '#';
    if (($item['LINK_TYPE'] ?? '') === 'inner' && $link !== '#' && strpos($link, 'http') !== 0) {
        $link = $link[0] === '/' ? $link : '/' . $link;
    }
    $params = $item['PARAMS'] ?? [];
    $attrs = '';
    foreach ($params as $k => $v) {
        if (is_scalar($v)) {
            $attrs .= ' ' . htmlspecialcharsbx((string)$k) . '="' . htmlspecialcharsbx((string)$v) . '"';
        }
    }
    $hasChildren = !empty($item['CHILDREN']);
    $lic = 'randee-menu__tree-item randee-menu__tree-item--lvl-' . (int)$level;
    if ($hasChildren) {
        $lic .= ' randee-menu__tree-item--branch';
    }
    ?>
    <li class="<?= $lic ?>">
        <a class="randee-menu__tree-link" href="<?= htmlspecialcharsbx($link) ?>"
            target="<?= htmlspecialcharsbx($item['TARGET'] ?? '_self') ?>"<?= $attrs ?>>
            <?= htmlspecialcharsbx((string)($item['NAME'] ?? '')) ?>
        </a>
        <?php if ($hasChildren): ?>
            <ul class="randee-menu__tree-sub randee-menu__tree-sub--lvl-<?= (int)($level + 1) ?>">
                <?php foreach ($item['CHILDREN'] as $child): ?>
                    <?php $renderItem($child, $level + 1); ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </li>
    <?php
};
?>
<ul class="randee-menu randee-menu--tree randee-menu__tree-root">
    <?php foreach ($items as $item): ?>
        <?php $renderItem($item, 0); ?>
    <?php endforeach; ?>
</ul>
