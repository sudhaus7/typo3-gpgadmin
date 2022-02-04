<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Helper;

use OpenPGP;
use OpenPGP_CompressedDataPacket;
use OpenPGP_Crypt_Symmetric;
use OpenPGP_Message;
use Symfony\Component\Mime\Message;

class PgpEncyptor {

	/**
	 * @var string
	 */
	protected $publicKey;

	public function __construct(string $publicKey) {
		$this->publicKey = $publicKey;
	}

	public function encrypt(Message $message): Message
	{
		$bufferFile = tmpfile();
		$outputFile = tmpfile();

		$messageBuffer = $this->iteratorToBuffer($message->toIterable());

		$recipientPublicKey = OpenPGP_Message::parse( OpenPGP::unarmor($this->publicKey, 'PGP PUBLIC KEY BLOCK'));

		$compressed = new OpenPGP_CompressedDataPacket($messageBuffer);
		$encrypted = OpenPGP_Crypt_Symmetric::encrypt([$recipientPublicKey], new OpenPGP_Message([$compressed]));
		$encrypted = OpenPGP::enarmor($encrypted->to_bytes(), 'PGP MESSAGE');

		return $message;
		//return new Message($message->getHeaders(), $encrypted);
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
}
