<?php

namespace Ruvents\ReformBundle\Tests;

use Ruvents\ReformBundle\MockUploadedFile;
use Symfony\Component\Filesystem\Filesystem;

class MockUploadedFileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Filesystem
     */
    private $fs;

    private $tmpDir;

    /**
     * @dataProvider constructorData()
     */
    public function testConstructor($arguments, $values)
    {
        /** @var MockUploadedFile $upl */
        $upl = (new \ReflectionClass(MockUploadedFile::class))->newInstanceArgs($arguments);

        $this->assertEquals($values, [
            $upl->getPathname(),
            $upl->getClientOriginalName(),
            $upl->getClientMimeType(),
            $upl->getClientSize(),
            $upl->getError(),
        ]);
    }

    public function constructorData()
    {
        return [
            [[__FILE__, 'abc'], [__FILE__, 'abc', 'application/octet-stream', null, UPLOAD_ERR_OK]],
            [[__FILE__, 'abc', 'text/plain', 123], [__FILE__, 'abc', 'text/plain', 123, UPLOAD_ERR_OK]],
        ];
    }

    public function testValid()
    {
        $f = new MockUploadedFile(__FILE__, 'a');

        $this->assertTrue($f->isValid());
    }

    public function testMove()
    {
        $file = $this->tmpDir.'/test.file';
        $contents = random_int(0, 10000);

        $this->fs->touch($file);
        file_put_contents($file, $contents);

        (new MockUploadedFile($file, 'a'))->move($this->tmpDir.'/new', 'new.file');

        $newFile = $this->tmpDir.'/new/new.file';

        $this->assertFileExists($newFile);
        $this->assertEquals($contents, file_get_contents($newFile));
    }

    protected function setUp()
    {
        $this->fs = new Filesystem();
        $this->tmpDir = __DIR__.'/tmp';

        $this->fs->mkdir($this->tmpDir);
    }

    protected function tearDown()
    {
        $this->fs->remove($this->tmpDir);
    }
}
