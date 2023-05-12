<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Domain\Service;

use SUDHAUS7\Sudhaus7Gpgadmin\Domain\Model\KeyInformationImmutable;
use Symfony\Component\Finder\Finder;
use UnexpectedValueException;

class PgpExtensionHandler implements PgpHandlerInterface
{
    /**
     * @var string
     */
    protected $keyringDirectory;

    /**
     * @var \gnupg
     */
    protected $gnupg;

    public function __construct()
    {
        if (!class_exists('gnupg')) {
            throw new UnexpectedValueException('gpg extension not available', 1644233261);
        }
        $keyringDirectoryName = uniqid('krg');
        $this->keyringDirectory     = sys_get_temp_dir().'/'.$keyringDirectoryName;
        @mkdir($this->keyringDirectory, 0700);
        putenv('GNUPGHOME='.$this->keyringDirectory);
        $this->gnupg = new \gnupg();
        $this->gnupg->seterrormode(\GNUPG_ERROR_EXCEPTION);
    }

    /**
     * @inheritDoc
     */
    public function encode(string $message, KeyInformationImmutable $recipientKey): string
    {
        $this->gnupg->import($recipientKey->getKey());
        $this->gnupg->addencryptkey($recipientKey->getFingerprint());
        $this->gnupg->setarmor(1);
        return $this->gnupg->encrypt($message);
    }

    /**
     * @inheritDoc
     */
    public function sign(string $message, string $signerEmail): string
    {
        //@TODO: implement function
        return $message;
    }

    /**
     * @inheritDoc
     */
    public function keyInformation(string $key): KeyInformationImmutable
    {
        $keyarray = $this->gnupg->import($key);
        $keyconfig = $this->gnupg->keyinfo($keyarray['fingerprint'] ?? '');

        return new KeyInformationImmutable(
            $kyconfig[0]['uids'][0]['uid'] ?? 0,
            $keyarray['fingerprint'] ?? '',
            new \DateTimeImmutable('@' . ($keyconfig[0]['subkeys'][0]['timestamp'] ?? 0)),
            new \DateTimeImmutable('@' . ($keyconfig[0]['subkeys'][0]['expires'] ?? 0)),
            $keyconfig[0]['subkeys'][0]['length'] ?? 0,
            $keyconfig[0]['uids'][0]['email'] ?? '',
            $keyconfig[0]['uids'][0]['name'] ?? '',
            $key
        );
    }


    public function __destruct()
    {
        if (!empty($this->keyringDirectory)) {
            $finder = new Finder();
            $files = $finder->files()->in($this->keyringDirectory);
            foreach ($files as $file) {
                if ($file->getRealPath()!==false) {
                    unlink($file->getRealPath());
                }
            }
            try {
                $finder      = new Finder();
                $directories = $finder->directories()->in($this->keyringDirectory);
                foreach ($directories as $directory) {
                    if ($directory->getRealPath()!==false) {
                        \rmdir($directory->getRealPath());
                    }
                }
            } catch (\Exception $e) {
            }
            \rmdir($this->keyringDirectory);
        }
    }
}
