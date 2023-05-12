<?php

use SUDHAUS7\Sudhaus7Gpgadmin\Form\FieldControl\GpgKeyInfo;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1643392458] = [
    'nodeName' => 'gpgKeyInfo',
    'priority' => 30,
    'class' => GpgKeyInfo::class
];
call_user_func(function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
        trim('
             module.tx_form {
			    settings {
			        yamlConfigurations {
			            1607698321 = EXT:sudhaus7_gpgadmin/Configuration/Yaml/BaseSetup.yaml
			          
			        }
			    }
             }
         ')
    );
});
