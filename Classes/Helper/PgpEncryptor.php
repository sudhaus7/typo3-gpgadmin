<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Helper;

use Symfony\Component\Mime\Email;
use SUDHAUS7\Sudhaus7Gpgadmin\Domain\Mime\PgpPart;
use SUDHAUS7\Sudhaus7Gpgadmin\Domain\Service\PgpHandlerFactory;
use Symfony\Component\Mime\Message;

class PgpEncryptor
{
    /**
     * @var string
     */
    protected $publicKey;

    public function __construct(string $publicKey)
    {
        $this->publicKey = $publicKey;
    }

    public function encrypt(Email $message): Message
    {
        $bufferFile = tmpfile();
        if ($bufferFile === false) {
            throw new \RuntimeException('buffer file can not be created', 1644340308);
        }

        $this->iteratorToFile($message->toIterable(), $bufferFile);

        $mimePart = $this->convertMessageToPgpPart($bufferFile, 'multipart', 'encrypted');
        $messageBuffer = $this->iteratorToBuffer($mimePart->bodyToIterable());

        if ($pgpHandler = PgpHandlerFactory::getHandler()) {
            $recpientKey = $pgpHandler->keyInformation($this->publicKey);

            $innerBoundary = $mimePart->getBoundary();

            $outerBoundary = 's7pgp_'.bin2hex(random_bytes(20));
            $mimePart->setBoundary($outerBoundary);

            if ($innerBoundary === null) {
                $messageBuffer = "Content-Type: text/plain\r\n\r\n\r\n".$messageBuffer;
            } else {
                if (!\is_resource($message->getHtmlBody()) && empty($message->getHtmlBody())) {
                    $messageBuffer = "Content-Type: multipart/mixed; boundary=\"".$innerBoundary."\"\r\n\r\n\r\n".$messageBuffer;
                } else {
                    if (!str_contains($messageBuffer, 'Content-Type: multipart/alternative')) {
                        $messageBuffer = "Content-Type: multipart/alternative; boundary=\"".$innerBoundary."\"\r\n\r\n\r\n".$messageBuffer;
                    } else {
                        $messageBuffer = "Content-Type: multipart/mixed; boundary=\"".$innerBoundary."\"\r\n\r\n\r\n".$messageBuffer;
                    }
                }
            }

            $encoded = $pgpHandler->encode($messageBuffer, $recpientKey);
            $newbody = <<<EOT
This is an OpenPGP/MIME encrypted message (RFC 4880 and 3156)

--{$outerBoundary}
Content-Type: application/pgp-encrypted
Content-Description: PGP/MIME version identification

Version: 1

--{$outerBoundary}
Content-Type: application/octet-stream; name="encrypted.asc"
Content-Description: OpenPGP encrypted message
Content-Disposition: inline; filename="encrypted.asc"

$encoded

--{$outerBoundary}--
EOT;

            $mimePart->setBody($newbody);
            $headers = $message->getHeaders();
            $headers->remove('Content-Transfer-Encoding');
            $headers->remove('Content-Type');

            $message = new Message($headers, $mimePart);
        }

        return $message;
    }

    /**
     * @param string[] $iterator
     *
     * @return string
     */
    protected function iteratorToBuffer(iterable $iterator): string
    {
        $buffer = '';
        foreach ($iterator as $chunk) {
            $buffer .= $chunk;
        }
        return $buffer;
    }

    /**
     * @param string[] $iterator
     * @param resource $stream
     *
     * @return void
     */
    protected function iteratorToFile(iterable $iterator, $stream): void
    {
        foreach ($iterator as $chunk) {
            fwrite($stream, $chunk);
        }
    }

    /**
     * @param resource $stream
     * @param string $type
     * @param string $subtype
     *
     * @return PgpPart
     */
    protected function convertMessageToPgpPart($stream, string $type, string $subtype): PgpPart
    {
        rewind($stream);

        $headers = '';
        $headersPosEnd = 0;
        $headerBodySeparator='';
        while (!feof($stream)) {
            $buffer = fread($stream, 78);
            $headers .= $buffer;

            // Detect ending of header list
            if (preg_match('/(\r\n\r\n|\n\n)/', $headers, $match)) {
                $headersPosEnd = strpos($headers, $headerBodySeparator = $match[0]);

                break;
            }
        }
        if ($headersPosEnd === false) {
            throw new \RuntimeException('End of Headers not found', 1644340440);
        }
        $headers = $this->getMessageHeaders(trim(substr($headers, 0, $headersPosEnd)));

        fseek($stream, $headersPosEnd + \strlen($headerBodySeparator));

        return new PgpPart($this->getStreamIterator($stream), $type, $subtype, $this->getParametersFromHeader($headers['content-type']));
    }

    /**
     * @param resource $stream
     *
     * @return string[]
     */
    protected function getStreamIterator($stream): iterable
    {
        while (!feof($stream)) {
            yield str_replace("\n", "\r\n", str_replace("\r\n", "\n", (string)fread($stream, 16372)));
        }
    }

    /**
     * @param string $header
     *
     * @return array<int|string,string>
     */
    private function getParametersFromHeader(string $header): array
    {
        $params = [];

        preg_match_all('/(?P<name>[a-z-0-9]+)=(?P<value>"[^"]+"|(?:[^\s;]+|$))(?:\s+;)?/i', $header, $matches);

        foreach ($matches['value'] as $pos => $paramValue) {
            $params[$matches['name'][$pos]] = trim($paramValue, '"');
        }

        return $params;
    }

    /**
     * @param string $headerData
     *
     * @return array<string,string>
     */
    private function getMessageHeaders(string $headerData): array
    {
        $headers = [];
        $headerLines = explode("\r\n", str_replace("\n", "\r\n", str_replace("\r\n", "\n", $headerData)));
        $currentHeaderName = '';

        // Transform header lines into an associative array
        foreach ($headerLines as $headerLine) {
            // Empty lines between headers indicate a new mime-entity
            if ('' === $headerLine) {
                break;
            }

            // Handle headers that span multiple lines
            if (!str_contains($headerLine, ':')) {
                $headers[$currentHeaderName] .= ' '.trim($headerLine);
                continue;
            }

            $header = explode(':', $headerLine, 2);
            $currentHeaderName = strtolower($header[0]);
            $headers[$currentHeaderName] = trim($header[1]);
        }

        return $headers;
    }
}
