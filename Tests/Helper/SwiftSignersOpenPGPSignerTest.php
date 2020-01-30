<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: markus
 * Date: 16.08.18
 * Time: 21:08
 */

namespace SUDHAUS7\Sudhaus7Gpgadmin\Tests\Helper;

use SUDHAUS7\Sudhaus7Gpgadmin\Helper\SwiftSignersOpenPGPSigner;

/**
 * Class SwiftSignersOpenPGPSignerTest
 * @package SUDHAUS7\Sudhaus7Gpgadmin\Tests\Helper
 */
class SwiftSignersOpenPGPSignerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SwiftSignersOpenPGPSigner
     */
    protected $subject = null;

    public function setUp()
    {
        $this->subject = new SwiftSignersOpenPGPSigner();
    }

    /**
     * test
     */
    public function canBeInstantiated()
    {
        self::assertInstanceOf(SwiftSignersOpenPGPSigner::class, $this->subject);
    }

    /**
     * @test
     */
    public function getEncryptInitiallyReturnsTrue()
    {
        self::assertSame(true, $this->subject->getEncrypt());
    }

    /**
     * @test
     */
    public function setEncryptSetsEncrypt()
    {
        $this->subject->setEncrypt(false);
        self::assertSame(false, $this->subject->getEncrypt());
    }

    /**
     * @test
     */
    public function getGnuPgHomeInitiallyReturnsNull()
    {
        self::assertSame(null, $this->subject->getGnupgHome());
    }

    /**
     * @test
     */
    public function setGnupgHomesetsGnupgHome()
    {
        $gnupghome = '/home/test/.gnupg';
        $this->subject->setGnupgHome($gnupghome);
        self:self::assertSame($gnupghome, $this->subject->getGnupgHome());
    }

    /**
     * @test
     */
    public function getMicalgInitiallyReturnsSHA256()
    {
        self::assertSame('SHA256', $this->subject->getMicalg());
    }

    /**
     * @test
     */
    public function setMicalgSetsMicalg()
    {
        $micalg = "SHA128";
        $this->subject->setMicalg($micalg);
        self::assertSame($micalg, $this->subject->getMicalg());
    }
}
