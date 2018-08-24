# typo3-gpgadmin

This software is a backend module for listing, searching, adding and deleting PGP-keys for your web user.

You need a bunch of software, getting this package work:

At first, you need a TYPO3 instance, with version 7.6 or higher.
* PHP >= 5.6
* TYPO3 >= 7.6
* ext-gnupg
* swiftmailer should be your favourite Mailer in TYPO3, so you can use the Swiftmailer Signer included in this ext.

This ext should work under 9.x, but it is actually untested. You want to help? Test it and pull your requests to this repository.

## THIS EXT IS WORK IN PROGRESS
### You can use it in your productive setup, but we don't give any guarantee, that it is working correct.

## Usage

To get the keys working in your ext, you only have to add the included Signer from this ext.

```php
        /** @var MailMessage $message */
        $message = $this->objectManager->get('TYPO3\\CMS\\Core\\Mail\\MailMessage');
        $message->setTo($recipient)->setFrom($sender)->setSubject($subject);
        $signer = new \SUDHAUS7\Sudhaus7Gpgadmin\Helper\SwiftSignersOpenPGPSigner();
        foreach ($recipient as $email => $item) {
            $signer->addRecipient($item);
        }
        $message->attachSigner($signer);
        $message->setBody($emailBody, 'text/html');
        $message->send();
```

Be aware, that every email adress only has ONE active public/private key. With multiple keys you will get an error, because the Signer doesn't know, which one to use.
Your sending email must implement public AND private key in the gpg database.
