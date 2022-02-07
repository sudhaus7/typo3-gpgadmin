<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Domain\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use UnexpectedValueException;
use function function_exists;

class PgpHandlerFactory
{
    public static function getHandler(): ?PgpHandlerInterface
    {
        if (function_exists('gnupg_init')) {
            try {
                return GeneralUtility::makeInstance(PgpExtensionHandler::class);
            } catch (UnexpectedValueException $e) {
            }
        }
        try {
            return GeneralUtility::makeInstance(PgpBinaryHandler::class);
        } catch (UnexpectedValueException $e) {
        }
        return null;
    }
}
