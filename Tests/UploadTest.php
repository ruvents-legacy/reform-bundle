<?php

namespace Ruvents\ReformBundle\Tests;

use Ruvents\ReformBundle\Upload;

class UploadTest extends UploadTypeTestCase
{
    public function testSetGetName()
    {
        $upload = new Upload();
        $name = time();

        $setNameReturn = $upload->setName($name);

        $this->assertEquals($upload, $setNameReturn);
        $this->assertEquals($name, $upload->getName());
    }

    public function testSetGetFile()
    {
        $upload = new Upload();
        $file = $this->createFile();

        $setFileReturn = $upload->setFile($file);

        $this->assertEquals($upload, $setFileReturn);
        $this->assertEquals($file, $upload->getFile());
    }
}
