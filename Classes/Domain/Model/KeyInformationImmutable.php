<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Domain\Model;

use DateTimeImmutable;
use Exception;

class KeyInformationImmutable
{
    /**
     * @var string
     */
    private $uid;
    /**
     * @var DateTimeImmutable
     */
    private $start;
    /**
     * @var DateTimeImmutable
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
     * @var
     */
    private $key;

    /**
     * @param string $uid
     * @param string $fingerprint
     * @param $start
     * @param $end
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
        if ($start instanceof DateTimeImmutable) {
            $this->start = $start;
        } else {
            $this->start = new DateTimeImmutable($start);
        }
        if ($end instanceof DateTimeImmutable) {
            $this->end = $end;
        } else {
            $this->end = new DateTimeImmutable($end);
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
     * @return DateTimeImmutable
     */
    public function getStart(): DateTimeImmutable
    {
        return $this->start;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getEnd(): DateTimeImmutable
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
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }
}