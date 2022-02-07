<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Domain\Service;

use SUDHAUS7\Sudhaus7Gpgadmin\Domain\Model\KeyInformationImmutable;

interface PgpHandlerInterface
{

    /**
     * @param string $message
     * @param KeyInformationImmutable $recpientKey
     *
     * @return string
     */
    public function encode(string $message, KeyInformationImmutable $recpientKey): string;

    /**
     * @param string $message
     * @param string $signerEmail
     *
     * @return string
     */
    public function sign(string $message, string $signerEmail): string;

    /**
     * @param string $key
     *
     * @return KeyInformationImmutable
     */
    public function keyInformation(string $key): KeyInformationImmutable;
}
