<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class GpgkeyRepository extends Repository {
	protected $defaultOrderings = array(
		'tstamp' => QueryInterface::ORDER_DESCENDING
	);
}
