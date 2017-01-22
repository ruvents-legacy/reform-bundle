<?php

namespace Ruvents\ReformBundle\Tests;

use Ruvents\ReformBundle\TmpFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TmpFileTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $tmpFile = new TmpFile(__FILE__);

        $this->assertSame($tmpFile->getRealPath(), __FILE__);
    }

    public function testSetUploadedFile()
    {
        $tmpFile = new TmpFile(__FILE__);
        $uplFile = new UploadedFile(__FILE__, 'test');

        $tmpFile->setUploadedFile($uplFile);

        $this->assertSame($tmpFile->getUploadedFile(), $uplFile);
    }
}
