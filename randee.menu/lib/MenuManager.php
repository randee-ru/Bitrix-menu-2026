<?php
/**
 * @author Randee
 * @copyright 2025
 */

namespace Randee\Menu;

use Bitrix\Main\Application;
use Bitrix\Main\SystemException;

class MenuManager
{
    /**
     * Получить структуру меню в виде дерева
     *
     * @param string|int $menuCodeOrId Код или ID меню
     * @param bool       $activeOnly   Только активные пункты
     * @return array
     */
    public static function getMenuTree($menuCodeOrId, bool $activeOnly = true): array
    {
        $menu = self::getMenu($menuCodeOrId);
        if (!$menu) {
            return [];
        }

        $filter = ['MENU_ID' => $menu['ID']];
        if ($activeOnly) {
            $filter['ACTIVE'] = 'Y';
        }

        $items = MenuItemTable::getList([
            'filter' => $filter,
            'order'  => ['SORT' => 'ASC', 'ID' => 'ASC'],
        ])->fetchAll();

        return self::buildTree($items, 0);
    }

    /**
     * Дерево пунктов для админки (все пункты, включая неактивные)
     */
    public static function getAdminTree(int $menuId): array
    {
        if ($menuId <= 0) {
            return [];
        }
        $items = MenuItemTable::getList([
            'filter' => ['MENU_ID' => $menuId],
            'order'  => ['SORT' => 'ASC', 'ID' => 'ASC'],
        ])->fetchAll();

        return self::buildAdminBranch($items, 0);
    }

    /**
     * Применить порядок и вложенность из дерева (AJAX drag-and-drop)
     *
     * @param array $nodes [['id'=>int,'children'=>array], ...]
     */
    public static function applyAdminTreeOrder(int $menuId, array $nodes): void
    {
        $validIds = [];
        $rs = MenuItemTable::getList([
            'filter'  => ['MENU_ID' => $menuId],
            'select'  => ['ID'],
        ]);
        while ($r = $rs->fetch()) {
            $validIds[(int)$r['ID']] = true;
        }
        if (!$validIds) {
            return;
        }

        $seen = [];
        $apply = static function (array $nodeList, int $parentId) use (&$apply, &$seen, $validIds) {
            $sort = 0;
            foreach ($nodeList as $node) {
                if (!is_array($node)) {
                    throw new SystemException('Некорректная структура дерева');
                }
                $id = (int)($node['id'] ?? 0);
                if (!$id || !isset($validIds[$id])) {
                    throw new SystemException('Некорректный пункт меню');
                }
                if (isset($seen[$id])) {
                    throw new SystemException('Пункт дублируется в дереве');
                }
                $seen[$id] = true;
                $sort += 10;
                $result = MenuItemTable::update($id, [
                    'PARENT_ID' => $parentId,
                    'SORT'      => $sort,
                ]);
                if (!$result->isSuccess()) {
                    throw new SystemException(implode(', ', $result->getErrorMessages()));
                }
                $children = $node['children'] ?? [];
                if (is_array($children) && $children !== []) {
                    $apply($children, $id);
                }
            }
        };

        $apply($nodes, 0);

        if (count($seen) !== count($validIds)) {
            throw new SystemException('Сохраните порядок: в запросе не хватает пунктов меню');
        }
    }

    /**
     * @param list<array<string,mixed>> $items
     */
    protected static function buildAdminBranch(array $items, int $parentId): array
    {
        $tree = [];
        foreach ($items as $item) {
            if ((int)$item['PARENT_ID'] === $parentId) {
                $item['CHILDREN'] = self::buildAdminBranch($items, (int)$item['ID']);
                $tree[]           = $item;
            }
        }

        return $tree;
    }

    /**
     * Получить плоский список пунктов меню
     */
    public static function getMenuItems($menuCodeOrId, bool $activeOnly = true): array
    {
        $menu = self::getMenu($menuCodeOrId);
        if (!$menu) {
            return [];
        }

        $filter = ['MENU_ID' => $menu['ID']];
        if ($activeOnly) {
            $filter['ACTIVE'] = 'Y';
        }

        return MenuItemTable::getList([
            'filter' => $filter,
            'order'  => ['SORT' => 'ASC', 'ID' => 'ASC'],
        ])->fetchAll();
    }

    /**
     * Построить дерево из плоского списка
     */
    protected static function buildTree(array $items, int $parentId): array
    {
        $tree = [];
        foreach ($items as $item) {
            if ((int)$item['PARENT_ID'] === $parentId) {
                $item['CHILDREN'] = self::buildTree($items, (int)$item['ID']);
                $item['PARAMS']   = $item['PARAMS'] ? json_decode($item['PARAMS'], true) : [];
                $tree[]           = $item;
            }
        }
        return $tree;
    }

    /**
     * Получить меню по коду или ID
     */
    public static function getMenu($codeOrId, bool $activeOnly = false): ?array
    {
        $filter = is_numeric($codeOrId)
            ? ['ID' => (int)$codeOrId]
            : ['CODE' => $codeOrId];

        if ($activeOnly) {
            $filter['ACTIVE'] = 'Y';
        }

        return MenuTable::getList([
            'filter' => $filter,
            'limit'  => 1,
        ])->fetch() ?: null;
    }

    /**
     * Сбросить кеш меню
     */
    public static function clearCache(string $menuCode = ''): void
    {
        $cache = Application::getInstance()->getManagedCache();
        $tableId = $menuCode ? 'randee.menu/' . $menuCode : 'randee.menu';
        $cache->cleanDir($tableId);
    }
}
