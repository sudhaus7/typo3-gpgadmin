<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Domain\Mime;

use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Header\ParameterizedHeader;
use Symfony\Component\Mime\Part\AbstractPart;

class PgpPart extends AbstractPart
{
    protected $_headers;
    /**
     * @var iterable|string
     */
    private $body;
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $subtype;
    /**
     * @var array
     */
    private $parameters;

    /**
     * @param iterable|string $body
     */
    public function __construct($body, string $type, string $subtype, array $parameters)
    {
        $parameters['protocol'] = 'application/pgp-encrypted';
        unset($this->_headers);
        parent::__construct();
        if (!\is_string($body) && !is_iterable($body)) {
            throw new \TypeError(sprintf('The body of "%s" must be a string or a iterable (got "%s").', self::class, get_debug_type($body)));
        }

        $this->body = $body;
        $this->type = $type;
        $this->subtype = $subtype;
        $this->parameters = $parameters;
    }

    public function setBody($body)
    {
        if (!\is_string($body) && !is_iterable($body)) {
            throw new \TypeError(sprintf('The body of "%s" must be a string or a iterable (got "%s").', self::class, get_debug_type($body)));
        }
        $this->body = $body;
    }

    public function bodyToString(): string
    {
        if (\is_string($this->body)) {
            return $this->body;
        }

        $body = '';
        foreach ($this->body as $chunk) {
            $body .= $chunk;
        }
        $this->body = $body;

        return $body;
    }
    public function getMediaType(): string
    {
        return $this->type;
    }

    public function getMediaSubtype(): string
    {
        return $this->subtype;
    }

    public function bodyToIterable(): iterable
    {
        if (\is_string($this->body)) {
            yield $this->body;

            return;
        }

        $body = '';
        foreach ($this->body as $chunk) {
            $body .= $chunk;
            yield $chunk;
        }
        $this->body = $body;
    }

    public function getBoundary(): ?string
    {
        return $this->parameters['boundary'];
    }

    public function setBoundary($boundary)
    {
        $this->parameters['boundary'] = $boundary;
    }
    public function getPreparedHeaders(): Headers
    {
        $headers = clone parent::getHeaders();

        $headers->setHeaderBody('Parameterized', 'Content-Type', $this->getMediaType().'/'.$this->getMediaSubtype());

        foreach ($this->parameters as $name => $value) {
            $headers->setHeaderParameter('Content-Type', $name, $value);
        }

        return $headers;
    }

    public function __sleep(): array
    {
        // convert iterables to strings for serialization
        if (is_iterable($this->body)) {
            $this->body = $this->bodyToString();
        }

        $this->_headers = $this->getHeaders();

        return ['_headers', 'body', 'type', 'subtype', 'parameters'];
    }

    public function __wakeup(): void
    {
        $r = new \ReflectionProperty(AbstractPart::class, 'headers');
        $r->setAccessible(true);
        $r->setValue($this, $this->_headers);
        unset($this->_headers);
    }
}
