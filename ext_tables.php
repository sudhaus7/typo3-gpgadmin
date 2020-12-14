<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if ( TYPO3_MODE === 'BE') {
    ExtensionUtility::registerModule(
        'SUDHAUS7.Sudhaus7Gpgadmin',
        'system',
        'tx_Sudhaus7Gpgadmin',
        'bottom',
        [
            'Gnupg' => 'index,delete,add,addKey'
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:sudhaus7_gpgadmin/Resources/Public/Icons/Extension.svg',
            'labels' => 'LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf',
        ]
    );
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:form/Resources/Private/Language/Database.xlf'][] = 'EXT:sudhaus7_gpgadmin/Resources/Private/Language/Backend.xlf';

}
