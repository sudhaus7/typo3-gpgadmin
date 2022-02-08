<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Domain\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use SUDHAUS7\Sudhaus7Gpgadmin\Domain\Model\KeyInformationImmutable;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use UnexpectedValueException;
use function is_resource;
use function proc_close;
use function putenv;
use function stream_get_contents;
use function uniqid;

class PgpBinaryHandler implements PgpHandlerInterface
{

    /**
     * @var string
     */
    private $gpgBinary;

    /**
     * @var string
     */
    protected $keyringDirectory;

    public function __construct()
    {
        /** @var string $gpgbinary */
        $gpgbinary = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('sudhaus7_gpgadmin', 'gpgbinary');
        if (!empty($gpgbinary) && is_executable($gpgbinary)) {
            $this->gpgBinary = $gpgbinary;
        } else {
            throw new UnexpectedValueException('gpg binary not installed or found in "'.$gpgbinary.'"', 1643896746);
        }
    }

    /**
     * @param string $message
     * @param KeyInformationImmutable $keyinformation
     *
     * @return string
     */
    public function encode(string $message, KeyInformationImmutable $keyinformation): string
    {
        $encrypted = $message;


        $keyringDirectoryName = uniqid('krg');
        $this->keyringDirectory     = sys_get_temp_dir().'/'.$keyringDirectoryName;
        @mkdir($this->keyringDirectory, 0700);

        putenv('GNUPGHOME='.$this->keyringDirectory);
        $descriptor = [
            0 => [ 'pipe', 'r' ],
            1 => [ 'file', $this->keyringDirectory.'/proc.log', 'a' ],
            2 => [ 'file', $this->keyringDirectory.'/err.log', 'a' ]
        ];
        $pipes      = [];
        $proc       = proc_open(
            $this->gpgBinary.' --import',
            $descriptor,
            $pipes,
            $this->keyringDirectory,
            [ 'GNUPGHOME' => $this->keyringDirectory ]
        );
        if (is_resource($proc)) {
            fwrite($pipes[0], $keyinformation->getKey());
            fclose($pipes[0]);
            proc_close($proc);
        }

        $descriptor = [
            0 => [ 'pipe', 'r' ],
            1 => [ 'pipe', 'w' ],
            2 => [ 'file', $this->keyringDirectory.'/err.log', 'a' ]
        ];
        $pipes      = [];
        $proc       = proc_open(
            $this->gpgBinary.' --encrypt --armor --trust-model always --batch --yes -r '.$keyinformation->getFingerprint(),
            $descriptor,
            $pipes,
            $this->keyringDirectory,
            [ 'GNUPGHOME' => $this->keyringDirectory ]
        );

        if (is_resource($proc)) {
            fwrite($pipes[0], $message);
            fclose($pipes[0]);
            $encrypted = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            proc_close($proc);
        }

        return $encrypted ? $encrypted : $message;
    }

    /**
     * @param string $message
     * @param string $signer
     *
     * @return string
     */
    public function sign(string $message, string $signer): string
    {
        return $message;
    }

    public function keyInformation(string $key): KeyInformationImmutable
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'k');
        if ($tmpfile === false) {
            throw new \RuntimeException('could not create keyring directory', 1644340003);
        }
        file_put_contents($tmpfile, $key);
        //@TODO: refactor to proc_open ?
        $buf = '';
        if ($fp = popen($this->gpgBinary.' --with-fingerprint --with-colons '.$tmpfile.' 2>/dev/null', 'r')) {
            while ($r = fgets($fp, 256)) {
                $buf .= $r;
            }
            pclose($fp);
        }
        unlink($tmpfile);
        return $this->parse($buf, $key);
    }


    private function parse(string $buf, string $textkey): KeyInformationImmutable
    {
        $buf = trim($buf);
        $key = [];
        $bufArray = preg_split("/((\r?\n)|(\r\n?))/", $buf) ;
        if ($bufArray !== false) {
            foreach ($bufArray as $line) {
                $line = explode(':', trim($line, ': '));
                switch ($line[0]) {
                    case 'pub':
                        $key['length']      = $line[2];
                        $key['fingerprint'] = $line[4];
                        $key['start']       = gmdate('Y-m-d', (int) $line[5]);
                        $key['end']         = gmdate('Y-m-d', (int) $line[6]);
                        break;
                    case 'fpr':
                        $key['fingerprint'] = $line[9];
                        break;
                    case 'uid':
                        $key['uid'] = trim($line[9]);
                        if (preg_match('/(.*)<(\S+)>/', $line[9], $matches)) {
                            $key['name']  = trim($matches[1]);
                            $key['email'] = $matches[2];
                        } else {
                            $key['name']  = '';
                            $key['email'] = trim($line[9]);
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        if (empty($key)) {
            throw new InvalidArgumentException('key can not be parsed', 1643899272);
        }
        return new KeyInformationImmutable(
            $key['uid'],
            $key['fingerprint'],
            new DateTimeImmutable($key['start']),
            new DateTimeImmutable($key['end']),
            (int)$key['length'],
            $key['email'],
            $key['name'],
            $textkey
        );
    }

    public function __destruct()
    {
        if (!empty($this->keyringDirectory)) {
            $finder = new Finder();
            $files = $finder->files()->in($this->keyringDirectory);
            foreach ($files as $file) {
                if ($file->getRealPath() !== false) {
                    @unlink($file->getRealPath());
                }
            }
            try {
                $finder      = new Finder();
                $directories = $finder->directories()->in($this->keyringDirectory);
                foreach ($directories as $directory) {
                    if ($directory->getRealPath() !== false) {
                        @\rmdir($directory->getRealPath());
                    }
                }
            } catch (\Exception $e) {
            }
            @\rmdir($this->keyringDirectory);
        }
    }
}
