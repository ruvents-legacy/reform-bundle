<?php

namespace Ruvents\ReformBundle\Tests;

use Ruvents\ReformBundle\MockUploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;

class MockUploadedFileTest extends UploadTypeTestCase
{
    /**
     * @dataProvider constructorData()
     */
    public function testConstructor($arguments, $values)
    {
        /** @var MockUploadedFile $mockUploadedFile */
        $mockUploadedFile = (new \ReflectionClass(MockUploadedFile::class))->newInstanceArgs($arguments);

        $this->assertEquals($values, [
            $mockUploadedFile->getPathname(),
            $mockUploadedFile->getClientOriginalName(),
            $mockUploadedFile->getClientMimeType(),
            $mockUploadedFile->getClientSize(),
            $mockUploadedFile->getError(),
        ]);
    }

    public function constructorData()
    {
        return [
            [[__FILE__, 'abc'], [__FILE__, 'abc', 'application/octet-stream', null, UPLOAD_ERR_OK]],
            [[__FILE__, 'abc', 'text/plain', 123], [__FILE__, 'abc', 'text/plain', 123, UPLOAD_ERR_OK]],
        ];
    }

    public function testFileNotFoundException()
    {
        $this->expectException(FileNotFoundException::class);

        new MockUploadedFile($this->tmpDir.'/aaaa', 'b');
    }

    public function testValid()
    {
        $mockUploadedFile = new MockUploadedFile(__FILE__, 'a');

        $this->assertTrue($mockUploadedFile->isValid());
    }

    public function testMove()
    {
        $file = $this->createFile();

        $contents = file_get_contents($file);

        $movedFile = (new MockUploadedFile($file->getPathname(), 'test'))
            ->move($this->tmpDir.'/moved', $file->getBasename().'_moved');

        $this->assertInstanceOf(File::class, $movedFile);
        $this->assertFalse($file->isFile());
        $this->assertTrue($movedFile->isFile());
        $this->assertEquals($contents, file_get_contents($movedFile->getPathname()));
    }
}
