<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Gpgadmin\Helper;

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use SUDHAUS7\Sudhaus7Gpgadmin\Traits\Gnupg;
use Swift_DependencyContainer;
use Swift_DependencyException;
use Swift_Message;
use Swift_Signers_BodySigner;
use Swift_SwiftException;

/**
 * Message Signer used to apply OpenPGP Signature/Encryption to a message.
 *
 * @author Artem Zhuravlev <infzanoza@gmail.com>
 * based on work by ravisorg for PHPMailer, https://github.com/ravisorg/PHPMailer
 * Funded by Mailgarant, https://github.com/mailgarant
 */

/**
 * Class SwiftSignersOpenPGPSigner
 * @package SUDHAUS7\Sudhaus7Gpgadmin\Helper
 */
class SwiftSignersOpenPGPSigner implements Swift_Signers_BodySigner
{
    use Gnupg;

    /**
     * The signing hash algorithm. 'MD5', SHA1, or SHA256. SHA256 (the default) is highly recommended
     * unless you need to deal with an old client that doesn't support it. SHA1 and MD5 are
     * currently considered cryptographically weak.
     *
     * This is apparently not supported by the PHP GnuPG module.
     *
     * @type string
     */
    protected $micalg = 'SHA256';

    /**
     * An associative array of keyFingerprint=>passwords to decrypt secret keys (if needed).
     * Populated by calling addKeyPassphrase. Pointless at the moment because the GnuPG module in
     * PHP doesn't support decrypting keys with passwords. The command line client does, so this
     * method stays for now.
     *
     * @type array
     */
    protected $keyPassphrases = [];

    /**
     * @var bool
     */
    protected $encrypt = true;

    /**
     * SwiftSignersOpenPGPSigner constructor.
     * @param null $signingKey
     * @param array $recipientKeys
     * @param null $gnupgHome
     * @throws Swift_SwiftException
     */
    public function __construct($signingKey = null, $recipientKeys = [], $gnupgHome = null)
    {
        $this->initGnu($signingKey, $recipientKeys, $gnupgHome);
    }

    /**
     * @param null $signingKey
     * @param array $recipientKeys
     * @param null $gnupgHome
     * @return SwiftSignersOpenPGPSigner
     * @throws Swift_SwiftException
     */
    public static function newInstance($signingKey = null, $recipientKeys = [], $gnupgHome = null): SwiftSignersOpenPGPSigner
    {
        return new self($signingKey, $recipientKeys, $gnupgHome);
    }

    /**
     * @return bool
     */
    public function getEncrypt(): bool
    {
        return $this->encrypt;
    }
    /**
     * @param boolean $encrypt
     */
    public function setEncrypt($encrypt)
    {
        $this->encrypt = $encrypt;
    }

    /**
     * @return null|string
     */
    public function getGnupgHome(): ?string
    {
        return $this->gnupgHome;
    }
    /**
     * @param string $gnupgHome
     */
    public function setGnupgHome($gnupgHome)
    {
        $this->gnupgHome = $gnupgHome;
    }

    /**
     * @return string
     */
    public function getMicalg(): string
    {
        return $this->micalg;
    }
    /**
     * @param string $micalg
     */
    public function setMicalg($micalg)
    {
        $this->micalg = $micalg;
    }

    /**
     * @param $identifier
     * @param null $passPhrase
     *
     * @throws Swift_SwiftException
     */
    public function addSignature($identifier, $passPhrase = null)
    {
        $keyFingerprint   = $this->getKey($identifier, 'sign');
        $this->signingKey = $keyFingerprint;

        if ($passPhrase) {
            $this->addKeyPassphrase($keyFingerprint, $passPhrase);
        }
    }

    /**
     * @param $identifier
     * @param $passPhrase
     *
     * @throws Swift_SwiftException
     */
    public function addKeyPassphrase($identifier, $passPhrase)
    {
        $keyFingerprint                        = $this->getKey($identifier, 'sign');
        $this->keyPassphrases[$keyFingerprint] = $passPhrase;
    }

    /**
     * Adds a recipient to encrypt a copy of the email for. If you exclude a key fingerprint, we
     * will try to find a matching key based on the identifier. However if no match is found, or
     * if multiple valid keys are found, this will fail. Specifying a key fingerprint avoids these
     * issues.
     *
     * @param string $identifier
     * an email address, but could be a key fingerprint, key ID, name, etc.
     *
     * @param string $keyFingerprint
     * @throws Swift_SwiftException
     */
    public function addRecipient($identifier, $keyFingerprint = null)
    {
        if (!$keyFingerprint) {
            $keyFingerprint = $this->getKey($identifier, 'encrypt');
        }

        $this->recipientKeys[$identifier] = $keyFingerprint;
    }

    /**
     * @param Swift_Message $message
     *
     * @return SwiftSignersOpenPGPSigner
     *
     * @throws Swift_DependencyException
     * @throws Swift_SwiftException
     */
    public function signMessage(Swift_Message $message): SwiftSignersOpenPGPSigner
    {
        $originalMessage = $this->createMessage($message);

        $message->setChildren([]);

        $message->setEncoder( Swift_DependencyContainer::getInstance()->lookup('mime.rawcontentencoder'));


        try {
            if (! $this->signingKey) {
                foreach ($message->getFrom() as $key => $value) {
                    $this->addSignature($this->getKey($key, 'sign'));
                }
            }
            $dosign = true;
        } catch (Swift_SwiftException $e) {
            // no signing for you then
            $dosign = false;
        }
        $body = $originalMessage->toString();
        $lines = preg_split('/(\r\n|\r|\n)/', rtrim($body));

        for ($i=0; $i<count($lines); $i++) {
            $lines[$i] = rtrim($lines[$i])."\r\n";
        }

        // Remove excess trailing newlines (RFC3156 section 5.4)
        $body = rtrim(implode('', $lines))."\r\n";
        if ($dosign) {
            $type = $message->getHeaders()->get('Content-Type');
            $type->setValue('multipart/signed');
            $type->setParameters([
                'micalg'   => sprintf("pgp-%s", strtolower($this->micalg)),
                'protocol' => 'application/pgp-signature',
                'boundary' => $message->getBoundary()
            ]);

            $signature = $this->pgpSignString($body, $this->signingKey);

            //Swiftmailer is automatically changing content type and this is the hack to prevent it
            $signedBody = <<<EOT
This is an OpenPGP/MIME signed message (RFC 4880 and 3156)

--{$message->getBoundary()}
$body
--{$message->getBoundary()}
Content-Type: application/pgp-signature; name="signature.asc"
Content-Description: OpenPGP digital signature
Content-Disposition: attachment; filename="signature.asc"

$signature

--{$message->getBoundary()}--
EOT;
            $body = $signedBody;
        }
        $message->setBody($body);

        if ($this->encrypt) {
            if (!$dosign) {
                $signed = $body;
            } else {
                $signed = sprintf("%s\r\n%s", $message->getHeaders()->get('Content-Type')->toString(), $body);
            }
            if (!$this->recipientKeys) {
                foreach ($message->getTo() as $key => $value) {
                    if (!isset($this->recipientKeys[$key])) {
                        $this->addRecipient($key);
                    }
                }
            }

            if (!$this->recipientKeys) {
                throw new Swift_SwiftException('Encryption has been enabled, but no recipients have been added. Use autoAddRecipients() or addRecipient()');
            }

            //Create body from signed message
            $encryptedBody = $this->pgpEncryptString($signed, array_keys($this->recipientKeys));

            $type = $message->getHeaders()->get('Content-Type');

            $type->setValue('multipart/encrypted');

            $type->setParameters([
                'protocol' => 'application/pgp-encrypted',
                'boundary' => $message->getBoundary()
            ]);

            $body = <<<EOT
This is an OpenPGP/MIME encrypted message (RFC 4880 and 3156)

--{$message->getBoundary()}
Content-Type: application/pgp-encrypted
Content-Description: PGP/MIME version identification

Version: 1

--{$message->getBoundary()}
Content-Type: application/octet-stream; name="encrypted.asc"
Content-Description: OpenPGP encrypted message
Content-Disposition: inline; filename="encrypted.asc"

$encryptedBody

--{$message->getBoundary()}--
EOT;

            $message->setBody($body);
        }

        $messageHeaders = $message->getHeaders();
        $messageHeaders->removeAll('Content-Transfer-Encoding');

        return $this;
    }

    /**
     * @return array
     */
    public function getAlteredHeaders(): array
    {
        return ['Content-Type', 'Content-Transfer-Encoding', 'Content-Disposition', 'Content-Description'];
    }

    /**
     * @return SwiftSignersOpenPGPSigner
     */
    public function reset(): SwiftSignersOpenPGPSigner
    {
        return $this;
    }

    /**
     * @param Swift_Message $message
     * @return Swift_Message
     */
    protected function createMessage(Swift_Message $message): Swift_Message
    {
        $mimeEntity = new Swift_Message('', $message->getBody(), $message->getContentType(), $message->getCharset());
        $mimeEntity->setChildren($message->getChildren());

        $messageHeaders = $mimeEntity->getHeaders();
        $messageHeaders->remove('Message-ID');
        $messageHeaders->remove('Date');
        $messageHeaders->remove('Subject');
        $messageHeaders->remove('MIME-Version');
        $messageHeaders->remove('To');
        $messageHeaders->remove('From');

        return $mimeEntity;
    }

    /**
     * @param $plaintext
     * @param $keyFingerprint
     *
     * @return string
     *
     * @throws Swift_SwiftException
     */
    protected function pgpSignString($plaintext, $keyFingerprint): string
    {
        if (isset($this->keyPassphrases[$keyFingerprint]) && !$this->keyPassphrases[$keyFingerprint]) {
            $passPhrase = $this->keyPassphrases[$keyFingerprint];
        } else {
            $passPhrase = null;
        }

        $this->gnupg->clearsignkeys();
        $this->gnupg->addsignkey($keyFingerprint, (string)$passPhrase);
        $this->gnupg->setsignmode(\gnupg::SIG_MODE_DETACH);
        $this->gnupg->setarmor(1);

        $signed = $this->gnupg->sign($plaintext);

        if ($signed) {
            return $signed;
        }

        throw new Swift_SwiftException('Unable to sign message (perhaps the secret key is encrypted with a passphrase?)');
    }

    /**
     * @param $plaintext
     * @param $keyFingerprints
     *
     * @return string
     *
     * @throws Swift_SwiftException
     */
    protected function pgpEncryptString($plaintext, $keyFingerprints)
    {
        $this->gnupg->clearencryptkeys();

        foreach ($keyFingerprints as $keyFingerprint) {
            $this->gnupg->addencryptkey($keyFingerprint);
        }

        $this->gnupg->setarmor(1);

        $encrypted = $this->gnupg->encrypt($plaintext);

        if ($encrypted) {
            return $encrypted;
        }

        throw new Swift_SwiftException('Unable to encrypt message');
    }

    /**
     * @param $identifier
     * @param $purpose
     *
     * @return string
     *
     * @throws Swift_SwiftException
     */
    protected function getKey($identifier, $purpose)
    {
        $keys         = $this->gnupg->keyinfo($identifier);
        $fingerprints = [];

        foreach ($keys as $key) {
            if ($this->isValidKey($key, $purpose)) {
                foreach ($key['subkeys'] as $subKey) {
                    if ($this->isValidKey($subKey, $purpose)) {
                        $fingerprints[] = $subKey['fingerprint'];
                    }
                }
            }
        }

        if (count($fingerprints) === 1) {
            return $fingerprints[0];
        }

        if (count($fingerprints) > 1) {
            throw new Swift_SwiftException(sprintf('Found more than one active key for %s use addRecipient() or addSignature()', $identifier));
        }

        throw new Swift_SwiftException(sprintf('Unable to find an active key to %s for %s,try importing keys first', $purpose, $identifier));
    }

    /**
     * @param $key
     * @param $purpose
     * @return bool
     */
    protected function isValidKey($key, $purpose)
    {
        return !($key['disabled'] || $key['expired'] || $key['revoked'] || ($purpose == 'sign' && !$key['can_sign']) || ($purpose == 'encrypt' && !$key['can_encrypt']));
    }
}
