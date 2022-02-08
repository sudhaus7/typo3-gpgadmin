<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Form\FieldControl;

use InvalidArgumentException;
use SUDHAUS7\Sudhaus7Gpgadmin\Domain\Service\PgpHandlerFactory;
use SUDHAUS7\Sudhaus7Gpgadmin\Domain\Service\PgpHandlerInterface;
use TYPO3\CMS\Backend\Form\Element\TextElement;
use function htmlentities;

class GpgKeyInfo extends TextElement
{
	/**
	 * @return array<string,mixed>
	 */
    public function render(): array
    {
        $row = $this->data['databaseRow'];
        $pgpHandler = PgpHandlerFactory::getHandler();
        if ($pgpHandler instanceof PgpHandlerInterface && !empty($row['pgp_public_key'])) {
            try {
                $key = $pgpHandler->keyInformation($row['pgp_public_key']);

                $result = sprintf('%s<br/>', htmlentities($key->getUid()));
                $result .= sprintf('Fingerprint: %s<br/>', $key->getFingerprint());
                $result .= sprintf('Valid %s - %s', $key->getStart()->format('Y-m-d'), $key->getEnd()->format('Y-m-d'));
            } catch (InvalidArgumentException $e) {
                $result = 'Can not read Key';
            }
        } else {
            $result = 'For detailed Information about this Key, please install and configure the pgp/gpg binary (optional)';
        }

        $return = parent::render();

        $return['html'].='<div class="pgp_key_info">'.$result.'</div>';

        return $return;
    }
}
