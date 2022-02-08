<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Domain\Model;

use DateTimeImmutable;
use Exception;
use TYPO3\CMS\Core\Utility\MathUtility;

class KeyInformationImmutable
{
    /**
     * @var string
     */
    private $uid;
    /**
     * @var \DateTimeInterface
     */
    private $start;
    /**
     * @var \DateTimeInterface
     */
    private $end;
    /**
     * @var int
     */
    private $length;
    /**
     * @var string
     */
    private $email;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $fingerprint;
    /**
     * @var string
     */
    private $key;

    /**
     * @param string $uid
     * @param string $fingerprint
     * @param string|int|\DateTimeInterface $start
     * @param string|int|\DateTimeInterface $end
     * @param int $length
     * @param string $email
     * @param string $name
     * @param string $key
     *
     * @throws Exception
     */
    public function __construct(string $uid, string $fingerprint, $start, $end, int $length, string $email, string $name, string $key)
    {
        $this->key = $key;
        $this->email = $email;
        $this->fingerprint = $fingerprint;
        $this->length = $length;
        $this->name = $name;
        $this->uid = $uid;
        if ($start instanceof \DateTimeInterface) {
            $this->start = $start;
        } elseif (MathUtility::canBeInterpretedAsInteger($start)) {
            $this->start = new DateTimeImmutable('@'.$start);
        } else {
            $this->start = new DateTimeImmutable((string)$start);
        }
        if ($end instanceof \DateTimeInterface) {
            $this->end = $end;
        } elseif (MathUtility::canBeInterpretedAsInteger($end)) {
            $this->start = new DateTimeImmutable('@'.$end);
        } else {
            $this->end = new DateTimeImmutable((string)$end);
        }
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return $this->uid;
    }

	/**
	 * @return \DateTimeInterface
	 */
    public function getStart(): \DateTimeInterface
    {
        return $this->start;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getEnd(): \DateTimeInterface
    {
        return $this->end;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }

	/**
	 * @return string
	 */
    public function getKey():string
    {
        return $this->key;
    }
}
