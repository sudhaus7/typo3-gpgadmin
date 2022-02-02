<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Gpgkey extends AbstractEntity
{

	protected string $email;
	protected string $pgpPublicKey;

	/**
	 * @return string
	 */
	public function getEmail(): string {
		return $this->email;
	}

	/**
	 * @param string $email
	 */
	public function setEmail( string $email ): void {
		$this->email = $email;
	}

	/**
	 * @return string
	 */
	public function getPgpPublicKey(): string {
		return $this->pgpPublicKey;
	}

	/**
	 * @param string $pgpPublicKey
	 */
	public function setPgpPublicKey( string $pgpPublicKey ): void {
		$this->pgpPublicKey = $pgpPublicKey;
	}

}
