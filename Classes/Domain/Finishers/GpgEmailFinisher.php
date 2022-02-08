<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Domain\Finishers;

use Doctrine\DBAL\Driver\Result;
use SUDHAUS7\Sudhaus7Gpgadmin\Helper\PgpEncyptor;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Form\Domain\Finishers\EmailFinisher;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Service\TranslationService;

class GpgEmailFinisher extends EmailFinisher
{

    /**
     * @inheritDoc
     */
    protected function executeInternal()
    {

		$version = GeneralUtility::makeInstance( Typo3Version::class);
		if ($version->getBranch()==='11.5') {
			$mail = $this->handle115();
		} else {
			$mail = $this->handle104();
		}

		$recipients = $mail->getTo();
		foreach($recipients as $recipient) {

			$mailToEncode = clone $mail;

			/** @var Connection $query */
			$query = GeneralUtility::makeInstance( ConnectionPool::class )
			                       ->getConnectionForTable( 'tx_sudhaus7gpgadmin_domain_model_gpgkey' );
			/** @var Result $res */
			$res    = $query->select(
				[ '*' ],
				'tx_sudhaus7gpgadmin_domain_model_gpgkey',
				[ 'email' => $recipient->getAddress() ]
			);
			/** @var array<string,int|string> $pgprow */
			$pgprow = $res->fetchAssociative();

			$headers = $mailToEncode->getHeaders();
			$headers->remove( 'to');
			$headers->addHeader( 'to', Address::createArray([sprintf('%s <%s>',$recipient->getName(),$recipient->getAddress())]));
			$mailToEncode->setHeaders( $headers);


			if (is_array($pgprow) && !empty($pgprow) && isset($pgprow['pgp_public_key'] )) {
				$encryptor = new PgpEncyptor( (string)$pgprow['pgp_public_key'] );
				$mailToEncode      = $encryptor->encrypt( $mailToEncode );
			}
			GeneralUtility::makeInstance( Mailer::class )->send( $mailToEncode );
		}

		return '';

    }

	/**
	 * @return Email
	 * @throws FinisherException
	 */
	private function handle104() : Email
	{
		$languageBackup = null;
		// Flexform overrides write strings instead of integers so
		// we need to cast the string '0' to false.
		if (
			isset($this->options['addHtmlPart'])
			&& $this->options['addHtmlPart'] === '0'
		) {
			$this->options['addHtmlPart'] = false;
		}

		$subject = $this->parseOption('subject');
		$recipients = $this->getRecipients('recipients', 'recipientAddress', 'recipientName'); /** @phpstan-ignore-line */
		$senderAddress = $this->parseOption('senderAddress');
		$senderAddress = is_string($senderAddress) ? $senderAddress : '';
		$senderName = $this->parseOption('senderName');
		$senderName = is_string($senderName) ? $senderName : '';
		$replyToRecipients = $this->getRecipients('replyToRecipients', 'replyToAddress'); /** @phpstan-ignore-line */
		$carbonCopyRecipients = $this->getRecipients('carbonCopyRecipients', 'carbonCopyAddress'); /** @phpstan-ignore-line */
		$blindCarbonCopyRecipients = $this->getRecipients('blindCarbonCopyRecipients', 'blindCarbonCopyAddress'); /** @phpstan-ignore-line */
		$addHtmlPart = $this->isHtmlPartAdded(); /** @phpstan-ignore-line */
		$attachUploads = $this->parseOption('attachUploads');
		$useFluidEmail = $this->parseOption('useFluidEmail');
		$title = $this->parseOption('title');
		$title = is_string($title) && $title !== '' ? $title : $subject;

		if (empty($subject)) {
			throw new FinisherException('The option "subject" must be set for the EmailFinisher.', 1327060320);
		}
		if (empty($recipients)) {
			throw new FinisherException('The option "recipients" must be set for the EmailFinisher.', 1327060200);
		}
		if (empty($senderAddress)) {
			throw new FinisherException('The option "senderAddress" must be set for the EmailFinisher.', 1327060210);
		}

		$formRuntime = $this->finisherContext->getFormRuntime();

		$translationService = TranslationService::getInstance();
		if (is_string($this->options['translation']['language'] ?? null) && $this->options['translation']['language'] !== '') {
			$languageBackup = $translationService->getLanguage();
			$translationService->setLanguage($this->options['translation']['language']);
		}

		$mail = $useFluidEmail
			? $this
				->initializeFluidEmail($formRuntime)
				->format($addHtmlPart ? FluidEmail::FORMAT_BOTH : FluidEmail::FORMAT_PLAIN)
				->assign('title', $title)
			: GeneralUtility::makeInstance(MailMessage::class);

		$mail
			->from(new Address($senderAddress, $senderName))
			->to(...$recipients)
			->subject($subject);

		if (!empty($replyToRecipients)) {
			$mail->replyTo(...$replyToRecipients);
		}

		if (!empty($carbonCopyRecipients)) {
			$mail->cc(...$carbonCopyRecipients);
		}

		if (!empty($blindCarbonCopyRecipients)) {
			$mail->bcc(...$blindCarbonCopyRecipients);
		}

		if (!$useFluidEmail) {
			$parts = [
				[
					'format' => 'Plaintext',
					'contentType' => 'text/plain',
				],
			];

			if ($addHtmlPart) {
				$parts[] = [
					'format' => 'Html',
					'contentType' => 'text/html',
				];
			}

			foreach ($parts as $i => $part) {
				$standaloneView = $this->initializeStandaloneView($formRuntime, $part['format']);
				$message = $standaloneView->render();

				if ($part['contentType'] === 'text/plain') {
					$mail->text($message);
				} else {
					$mail->html($message);
				}
			}
		}

		if (!empty($languageBackup)) {
			$translationService->setLanguage($languageBackup);
		}

		$elements = $formRuntime->getFormDefinition()->getRenderablesRecursively();

		if ($attachUploads) {
			foreach ($elements as $element) {
				if (!$element instanceof FileUpload) {
					continue;
				}
				$file = $formRuntime[$element->getIdentifier()];
				if ($file) {
					if ($file instanceof FileReference) {
						$file = $file->getOriginalResource();
					}
					/** @var File $file */
					$mail->attach($file->getContents(), $file->getName(), $file->getMimeType());
				}
			}
		}
		return $mail;
	}

	/**
	 * @return Email
	 * @throws FinisherException
	 */
	private function handle115() : Email
	{
		$languageBackup = null;
		// Flexform overrides write strings instead of integers so
		// we need to cast the string '0' to false.
		if (
			isset($this->options['addHtmlPart'])
			&& $this->options['addHtmlPart'] === '0'
		) {
			$this->options['addHtmlPart'] = false;
		}

		$subject = $this->parseOption('subject');
		$recipients = $this->getRecipients('recipients');
		$senderAddress = $this->parseOption('senderAddress');
		$senderAddress = is_string($senderAddress) ? $senderAddress : '';
		$senderName = $this->parseOption('senderName');
		$senderName = is_string($senderName) ? $senderName : '';
		$replyToRecipients = $this->getRecipients('replyToRecipients');
		$carbonCopyRecipients = $this->getRecipients('carbonCopyRecipients');
		$blindCarbonCopyRecipients = $this->getRecipients('blindCarbonCopyRecipients');
		$addHtmlPart = $this->parseOption('addHtmlPart') ? true : false;
		$attachUploads = $this->parseOption('attachUploads');
		$useFluidEmail = $this->parseOption('useFluidEmail');
		$title = $this->parseOption('title');
		$title = is_string($title) && $title !== '' ? $title : $subject;

		if (empty($subject)) {
			throw new FinisherException('The option "subject" must be set for the EmailFinisher.', 1327060320);
		}
		if (empty($recipients)) {
			throw new FinisherException('The option "recipients" must be set for the EmailFinisher.', 1327060200);
		}
		if (empty($senderAddress)) {
			throw new FinisherException('The option "senderAddress" must be set for the EmailFinisher.', 1327060210);
		}

		$formRuntime = $this->finisherContext->getFormRuntime();

		$translationService = GeneralUtility::makeInstance(TranslationService::class);
		if (is_string($this->options['translation']['language'] ?? null) && $this->options['translation']['language'] !== '') {
			$languageBackup = $translationService->getLanguage();
			$translationService->setLanguage($this->options['translation']['language']);
		}

		$mail = $useFluidEmail
			? $this
				->initializeFluidEmail($formRuntime)
				->format($addHtmlPart ? FluidEmail::FORMAT_BOTH : FluidEmail::FORMAT_PLAIN)
				->assign('title', $title)
			: GeneralUtility::makeInstance(MailMessage::class);

		$mail
			->from(new Address($senderAddress, $senderName))
			->to(...$recipients)
			->subject($subject);

		if (!empty($replyToRecipients)) {
			$mail->replyTo(...$replyToRecipients);
		}

		if (!empty($carbonCopyRecipients)) {
			$mail->cc(...$carbonCopyRecipients);
		}

		if (!empty($blindCarbonCopyRecipients)) {
			$mail->bcc(...$blindCarbonCopyRecipients);
		}

		if (!$useFluidEmail) {
			$parts = [
				[
					'format' => 'Plaintext',
					'contentType' => 'text/plain',
				],
			];

			if ($addHtmlPart) {
				$parts[] = [
					'format' => 'Html',
					'contentType' => 'text/html',
				];
			}

			foreach ($parts as $i => $part) {
				$standaloneView = $this->initializeStandaloneView($formRuntime, $part['format']);
				$message = $standaloneView->render();

				if ($part['contentType'] === 'text/plain') {
					$mail->text($message);
				} else {
					$mail->html($message);
				}
			}
		}

		if (!empty($languageBackup)) {
			$translationService->setLanguage($languageBackup);
		}

		if ($attachUploads) {
			foreach ($formRuntime->getFormDefinition()->getRenderablesRecursively() as $element) {
				if (!$element instanceof FileUpload) {
					continue;
				}
				$file = $formRuntime[$element->getIdentifier()];
				if ($file) {
					if ($file instanceof FileReference) {
						$file = $file->getOriginalResource();
					}
					$mail->attach($file->getContents(), $file->getName(), $file->getMimeType());
				}
			}
		}

		return $mail;
	}

}
