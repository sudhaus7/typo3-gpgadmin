.. include:: /Includes.rst.txt

.. _introduction:

Introduction
------------


.. _what-does-it-do:

What does it do?
^^^^^^^^^^^^^^^^

This TYPO3 extension adds a backend module enabling the maintainer to manage a gpg/pgp keyring. It is possible to add and remove pgp public keys.

Additionally it provides a finisher for EXT:form to send encrypted (and signed) messages directly from your feedback form.

For developers an api is exposed to encrypt (and sign) custom created mails through TYPO3's mailer api.

This extension uses ext-gnupg for PHP 7.x (https://www.php.net/manual/en/book.gnupg.php)

Software requirements
^^^^^^^^^^^^^^^^^^^^^

* PHP >= 7.2
* TYPO3 9.5
* ext-gnupg
