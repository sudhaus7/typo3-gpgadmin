<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Tests\Functional;

use Nimut\TestingFramework\v10\TestCase\FunctionalTestCase;
use SUDHAUS7\Sudhaus7Gpgadmin\Domain\Model\KeyInformationImmutable;
use SUDHAUS7\Sudhaus7Gpgadmin\Domain\Service\PgpExtensionHandler;

use function file_get_contents;

class GpgExtensionEncoderTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3conf/ext/sudhaus7_gpgadmin',
    ];

    protected $configurationToUseInTestInstance = [
        'BE'=>[
            'debug'=>true,
        ],
        'FE'=>[
            'debug'=>true,
        ],
        'EXTENSIONS'=>[
            'sudhaus7_gpgadmin' => [
                'gpgbinary' => '/opt/local/bin/gpg',
            ],
        ],
    ];

    protected $key;

    /** @test */
    public function canInstantiateHandler()
    {
        $handler = new PgpExtensionHandler();
        $this->assertInstanceOf(PgpExtensionHandler::class, $handler);
    }

    /** @test */
    public function canReadKey()
    {
        $handler = new PgpExtensionHandler();
        $info = $handler->keyInformation($this->key);
        $this->assertInstanceOf(KeyInformationImmutable::class, $info);
    }

    /** @test */
    public function isCorrectKey()
    {
        $handler = new PgpExtensionHandler();
        $info = $handler->keyInformation($this->key);
        $this->assertEquals('foppel@gmail.com', $info->getEmail());
    }

    /** @test */
    public function canEncode()
    {
        $msg = 'Test Message';
        $handler = new PgpExtensionHandler();
        $info = $handler->keyInformation($this->key);
        $encoded = $handler->encode($msg, $info);
        $this->assertStringStartsWith('-----BEGIN PGP MESSAGE-----', $encoded);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->key = file_get_contents(__DIR__.'/Fixtures/key.asc');
        //$this->importDataSet('ntf://Database/pages.xml');
    }
}
