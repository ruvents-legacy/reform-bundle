<?php

namespace Ruvents\ReformBundle\Tests;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class UploadTypeTestCase extends TypeTestCase
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $tmpDir;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = __DIR__.'/tmp';
        $this->filesystem->mkdir($this->tmpDir);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->filesystem->remove($this->tmpDir);

        parent::tearDown();
    }

    /**
     * @return File
     */
    protected function createFile()
    {
        $rand = time();

        $pathname = $this->tmpDir.'/'.$rand;

        file_put_contents($pathname, $rand);

        return new File($pathname);
    }

    /**
     * @return UploadedFile
     */
    protected function createUploadedFile()
    {
        $file = $this->createFile();

        return new UploadedFile(
            $file->getPathname(),
            $file->getBasename(),
            $file->getMimeType(),
            $file->getSize(),
            null,
            true
        );
    }
}
