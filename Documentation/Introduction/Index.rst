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

A typical use case for this extension would be a contact form that sends users' data to your email account. To protect the users' private data entrusted to you, this extension encrypts the email with your public key. This means that only you, as the owner of the private key, can read this message.

Software requirements
^^^^^^^^^^^^^^^^^^^^^

* TYPO3 10.4 or 11.5
* PHP >= 7.2
* PHP extension `ext-gnupg <https://www.php.net/manual/en/book.gnupg.php>`__ or GPG binaries
