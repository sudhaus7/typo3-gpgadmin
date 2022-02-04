<?php


return [
	"ctrl" => [
		"title" => "LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:record.title",
		"label" => "email",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"default_sortby"=>'tstamp desc',
		"cruser_id" => "cruser_id",
		'enablecolumns' => [
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		],
		'searchFields' => 'email',
		"iconfile" => 'EXT:sudhaus7_gpgadmin/Resources/Public/Icons/Extension.svg',
	],
	"feInterface" => [
		"fe_admin_fieldList" => "email,pgp_public_key,pgp_private_key",
	],
	"types" => [
		"0" => [
			"showitem" => "email,pgp_public_key,--palette--;;access"
		]
	],
	"palettes" => [
		'access'=>[
			'showitem'=>'hidden,starttime,endtime',
			'canNotCollapse' => 1,
		]
	],
	"columns" => [
		'starttime' => [
			'exclude' => true,
			'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
			'config' => [
				'type' => 'input',
				'renderType' => 'inputDateTime',
				'eval' => 'datetime,int',
				'default' => 0
			]
		],
		'endtime' => [
			'exclude' => true,
			'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
			'config' => [
				'type' => 'input',
				'renderType' => 'inputDateTime',
				'eval' => 'datetime,int',
				'default' => 0
			]
		],
		'hidden' => [
			'exclude' => true,
			'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:pages.hidden_toggle',
			'config' => [
				'type' => 'check',
				'renderType' => 'checkboxToggle',
				'default' => 1,
				'items' => [
					[
						0 => '',
						1 => '',
						'invertStateDisplay' => true
					]
				],
			]
		],
		"email" => [
			"label" => "LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:record.email",
			"config" => [
				"type" => "input",
				"size" => "30",
			]
		],
		"pgp_public_key" => [
			"label" => "LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:record.pgp_public_key",
			"config" => [
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'default'=>'',
				'eval' => 'trim',
				'renderType'=>'gpgKeyInfo',
			]
		],

	],
];
