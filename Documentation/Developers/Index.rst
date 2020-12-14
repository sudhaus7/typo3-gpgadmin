.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt

.. _developers:

Using the API
=============

To get the keys working in your extension, you only have to add the included signer from this extension.

.. code-block:: php

   /** @var MailMessage $message */
   $message = GeneralUtility::makeInstance( \TYPO3\CMS\Core\Mail\MailMessage::class );
   $message
      ->setTo($recipient) // has to have a valid key in the keyring
      ->setFrom($sender)
      ->setSubject($subject);

   // this is the pgp/gpg part
   $signer = new \SUDHAUS7\Sudhaus7Gpgadmin\Helper\SwiftSignersOpenPGPSigner();
   $message->attachSigner($signer);

   // continue with business as usual
   $message->setBody($emailBody, 'text/html');
   $message->send();

The signer-class will be called just before the send is executed and will sign, and encrypt the message body and wrap it in the appropriate content-types and segments.
