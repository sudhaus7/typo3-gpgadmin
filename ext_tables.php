<?php
if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'SUDHAUS7.Sudhaus7Gpgadmin',
        'web',
        'tx_Sudhaus7Gpgadmin',
        'bottom',
        [
            'Gnupg' => 'index,delete,add,addKey'
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:sudhaus7_gpgadmin/Resources/Public/Icons/Extension.gif',
            'labels' => 'LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf',
        ]
    );
}