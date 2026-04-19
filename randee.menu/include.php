<?php
/**
 * @author Randee
 * @copyright 2025
 */

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
    'randee.menu',
    [
        'Randee\Menu\MenuTable'      => 'lib/MenuTable.php',
        'Randee\Menu\MenuItemTable'  => 'lib/MenuItemTable.php',
        'Randee\Menu\MenuManager'    => 'lib/MenuManager.php',
    ]
);
