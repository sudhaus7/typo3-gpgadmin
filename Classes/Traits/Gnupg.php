<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: markus
 * Date: 16.08.18
 * Time: 21:39
 */

namespace SUDHAUS7\Sudhaus7Gpgadmin\Traits;

use RuntimeException;
use Swift_SwiftException;
use ExtensionConfiguration;
use GeneralUtility;
/**
 * Trait Gnupg
 * @package SUDHAUS7\Sudhaus7Gpgadmin\Traits
 */
trait Gnupg
{
    /**
     * @var \gnupg
     */
    protected $gnupg;
    /**
     * Specifies the home directory for the GnuPG keyrings. By default this is the user's home
     * directory + /.gnupg, however when running on a web server (eg: Apache) the home directory
     * will likely not exist and/or not be writable. Set this by calling setGPGHome before calling
     * any other encryption/signing methods.
     *
     * @var string
     */
    protected $gnupgHome = null;
    /**
     * An associative array of identifier=>keyFingerprint for the recipients we'll encrypt the email
     * to, where identifier is usually the email address, but could be anything used to look up a
     * key (including the fingerprint itself). This is populated either by autoAddRecipients or by
     * calling addRecipient.
     *
     * @var array
     */
    protected $recipientKeys = array();
    /**
     * The fingerprint of the key that will be used to sign the email. Populated either with
     * autoAddSignature or addSignature.
     *
     * @var string
     */
    protected $signingKey;
    /**
     * @param string $signingKey
     * @param array $recipientKeys
     * @param string $gnupgHome
     * @throws Swift_SwiftException
     */
    public function initGnu($signingKey = null, $recipientKeys = [], $gnupgHome = null)
    {
        $confArr = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('sudhaus7_gpgadmin');
        if (!$gnupgHome && !empty($confArr['gnupghome'])) {
            $gnupgHome = $confArr['gnupghome'];
        }
        $this->gnupgHome = $gnupgHome;
        $this->initGNUPG();
        $this->signingKey = $signingKey;
        $this->recipientKeys = $recipientKeys;
    }
    /**
     * @throws Swift_SwiftException
     */
    protected function initGNUPG()
    {
        if (!class_exists('gnupg')) {
            throw new RuntimeException('PHPMailerPGP requires the GnuPG class', 1607691506);
        }
        if (!$this->gnupgHome && isset($_SERVER['HOME'])) {
            $this->gnupgHome = $_SERVER['HOME'] . '/.gnupg';
        }
        if (!$this->gnupgHome && getenv('HOME')) {
            $this->gnupgHome = getenv('HOME') . '/.gnupg';
        }
        if (!$this->gnupgHome) {
            throw new RuntimeException('Unable to detect GnuPG home path, please call PHPMailerPGP::setGPGHome()', 1607691564);
        }
        if (!file_exists($this->gnupgHome)) {
            throw new RuntimeException('GnuPG home path does not exist');
        }
        putenv("GNUPGHOME=" . escapeshellcmd($this->gnupgHome));
        if (!$this->gnupg) {
            $this->gnupg = new \gnupg();
        }
        $this->gnupg->seterrormode(\gnupg::ERROR_EXCEPTION);
    }
}
