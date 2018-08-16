<?php


$EM_CONF[$_EXTKEY] = array(
    'title'                         => '(Sudhaus7) GNUPG Admins and Helpers',
    'description'                   => 'A helper functioon to send PGP encrypted mails with TYPO3',
    'category'                      => 'be',
    'shy'                           => 0,
    'dependencies'                  => '',
    'conflicts'                     => '',
    'priority'                      => '',
    'loadOrder'                     => '',
    'module'                        => '',
    'state'                         => 'beta',
    'uploadfolder'                  => 0,
    'createDirs'                    => '',
    'modify_tables'                 => '',
    'clearCacheOnLoad'              => 1,
    'lockType'                      => '',
    'author'                        => 'Markus Hofmann',
    'author_email'                  => 'mhofmann@sudhaus7.de',
    'author_company'                => 'Sudhaus 7',
    'CGLcompliance'                 => '',
    'CGLcompliance_note'            => '',
    'version'                       => '1.0.0',
    'constraints'                   => [
        'depends' => [
            'typo3' => '7.6.0-9.5.99'
        ]
    ],
    'suggests'                      => [],
);
