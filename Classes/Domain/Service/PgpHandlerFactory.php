<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Domain\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use UnexpectedValueException;
use function function_exists;

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
