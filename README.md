# [SUDHAUS7](https://sudhaus7.de) PGP/GPG/OpenPGP EXT:form mail finisher

[![TYPO3 10](https://img.shields.io/badge/TYPO3-10-orange.svg)](https://get.typo3.org/version/10)
[![TYPO3 11](https://img.shields.io/badge/TYPO3-11-orange.svg)](https://get.typo3.org/version/11)
![PHPSTAN:Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=flat])
![build:passing](https://img.shields.io/badge/build-passing-brightgreen.svg?style=flat])
![build:passing](https://img.shields.io/badge/Version-3.0.0-blue.svg?style=flat])


This TYPO3 extension adds an EXT:form finisher and a record type for managing GPG/PGP/OpenPGP keys and sending GPG/PGP encrypted (and signed) emails from your forms.

Software requirements
* PHP >= 7.2
* TYPO3 10.4 or 11.5

For the encryption part either ext-gnupg for PHP  is required or an installation of the gpg command line tools.

Furthermore you will need a PGP Public Key for the E-Mail address you wish to send the encrypted E-Mail to.

A typical usecase for this extension would be a contact form which sends the users data to your email account. To protect the users private data entrusted to you, this extension will encrypt the email with your public key. This then means only you as the holder of the private key can read this message. 

Multiple recipients are supported, but based on  the most E-Mail clients work, only recipients in the To: Field will be encrypted. Cc or Bcc can still be used though, for example as backup, and will receive an encrypted version of the email as well.

Keys are managed by a special record type, and can be added in the backend in the typical TYPO3 fashion. Public keys need to be copy&pasted.

Currently only encryption is supported, not signing. 

The extension can also be used as a library for extension developers to use the Interface for encrypting their own \TYPO3\CMS\Core\Mail\MailMessage or \TYPO3\CMS\Core\Mail\FluidMail based E-Mails

Future plans:

* retrieve keys from keyserver (if available)
* support for signing mails, for sending mails to customers
* refactor the Finisher part for more flexibilty
* refactor the mail-encoding part for more flexibility
* update documentation
