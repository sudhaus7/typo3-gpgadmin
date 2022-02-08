<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Helper;

use SUDHAUS7\Sudhaus7Gpgadmin\Domain\Mime\PgpPart;
use SUDHAUS7\Sudhaus7Gpgadmin\Domain\Service\PgpHandlerFactory;
use Symfony\Component\Mime\Message;

class PgpEncyptor
{

    /**
     * @var string
     */
    protected $publicKey;

    public function __construct(string $publicKey)
    {
        $this->publicKey = $publicKey;
    }

    public function encrypt(Message $message): Message
    {
        $bufferFile = tmpfile();

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
                    if (strpos($messageBuffer, 'Content-Type: multipart/alternative') === false) {
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

    protected function iteratorToBuffer(iterable $iterator): string
    {
        $buffer = '';
        foreach ($iterator as $chunk) {
            $buffer .= $chunk;
        }
        return $buffer;
    }

    protected function iteratorToFile(iterable $iterator, $stream): void
    {
        foreach ($iterator as $chunk) {
            fwrite($stream, $chunk);
        }
    }

    protected function convertMessageToPgpPart($stream, string $type, string $subtype): PgpPart
    {
        rewind($stream);

        $headers = '';

        while (!feof($stream)) {
            $buffer = fread($stream, 78);
            $headers .= $buffer;

            // Detect ending of header list
            if (preg_match('/(\r\n\r\n|\n\n)/', $headers, $match)) {
                $headersPosEnd = strpos($headers, $headerBodySeparator = $match[0]);

                break;
            }
        }

        $headers = $this->getMessageHeaders(trim(substr($headers, 0, $headersPosEnd)));

        fseek($stream, $headersPosEnd + \strlen($headerBodySeparator));

        return new PgpPart($this->getStreamIterator($stream), $type, $subtype, $this->getParametersFromHeader($headers['content-type']));
    }

    protected function getStreamIterator($stream): iterable
    {
        while (!feof($stream)) {
            yield str_replace("\n", "\r\n", str_replace("\r\n", "\n", fread($stream, 16372)));
        }
    }
    private function getParametersFromHeader(string $header): array
    {
        $params = [];

        preg_match_all('/(?P<name>[a-z-0-9]+)=(?P<value>"[^"]+"|(?:[^\s;]+|$))(?:\s+;)?/i', $header, $matches);

        foreach ($matches['value'] as $pos => $paramValue) {
            $params[$matches['name'][$pos]] = trim($paramValue, '"');
        }

        return $params;
    }
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
