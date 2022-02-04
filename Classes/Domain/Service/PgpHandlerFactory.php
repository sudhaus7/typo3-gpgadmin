<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Domain\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use UnexpectedValueException;
use function function_exists;

class PgpHandlerFactory
{
    public static function getHandler(): ?PgpHandlerInterface
    {
        if (function_exists('gnupg_init')) {
            //TODO: implementation
            return null;
        }

        try {
            return GeneralUtility::makeInstance(PgpBinaryHandler::class);
        } catch (UnexpectedValueException $e) {
        }
        return null;
    }
}
