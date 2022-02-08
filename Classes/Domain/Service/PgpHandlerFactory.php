<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Domain\Service;

use UnexpectedValueException;

class PgpHandlerFactory
{
    public static function getHandler(): ?PgpHandlerInterface
    {
        try {
            return new PgpExtensionHandler();
        } catch (UnexpectedValueException $e) {
        }

        try {
            return new PgpBinaryHandler();
        } catch (UnexpectedValueException $e) {
        }
        return null;
    }
}
