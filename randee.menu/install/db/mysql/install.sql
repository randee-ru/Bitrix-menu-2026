-- Меню сайта
CREATE TABLE IF NOT EXISTS `b_randee_menu` (
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `CODE` varchar(50) NOT NULL,
    `NAME` varchar(255) NOT NULL,
    `DESCRIPTION` text,
    `SORT` int(11) DEFAULT 100,
    `ACTIVE` char(1) DEFAULT 'Y',
    `DATE_CREATE` datetime DEFAULT NULL,
    `DATE_MODIFY` datetime DEFAULT NULL,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `UX_CODE` (`CODE`),
    KEY `IX_ACTIVE` (`ACTIVE`),
    KEY `IX_SORT` (`SORT`)
);

-- Пункты меню
CREATE TABLE IF NOT EXISTS `b_randee_menu_item` (
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `MENU_ID` int(11) NOT NULL,
    `PARENT_ID` int(11) DEFAULT 0,
    `NAME` varchar(255) NOT NULL,
    `LINK` varchar(500) DEFAULT NULL,
    `LINK_TYPE` varchar(20) DEFAULT 'inner',
    `SORT` int(11) DEFAULT 100,
    `ACTIVE` char(1) DEFAULT 'Y',
    `PARAMS` text,
    `TARGET` varchar(20) DEFAULT '_self',
    `DEPTH_LEVEL` int(11) DEFAULT 1,
    `DATE_CREATE` datetime DEFAULT NULL,
    `DATE_MODIFY` datetime DEFAULT NULL,
    PRIMARY KEY (`ID`),
    KEY `IX_MENU_ID` (`MENU_ID`),
    KEY `IX_PARENT_ID` (`PARENT_ID`),
    KEY `IX_ACTIVE` (`ACTIVE`),
    KEY `IX_SORT` (`SORT`),
    KEY `IX_MENU_PARENT` (`MENU_ID`, `PARENT_ID`)
);
