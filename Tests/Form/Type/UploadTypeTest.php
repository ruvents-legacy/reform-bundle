<?php

namespace Ruvents\ReformBundle\Tests\Form\Type;

use Ruvents\ReformBundle\Form\Extension\FormTypeUploadExtension;
use Ruvents\ReformBundle\Form\Type\UploadType;
use Ruvents\ReformBundle\Helper\UploadHelper;
use Ruvents\ReformBundle\MockUploadedFile;
use Ruvents\ReformBundle\TmpFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadTypeTest extends TypeTestCase
{
    /**
     * @var UploadHelper
     */
    private $uploadHelper;

    /**
     * @var string
     */
    private $tmpDir;

    /**
     * @var Filesystem
     */
    private $fs;

    public function testNotSubmitted()
    {
        $form = $this->factory->create(UploadType::class);

        $view = $form->createView();
        $children = $view->children;

        $this->assertArrayHasKey('id', $children);
        $this->assertArrayHasKey('file', $children);
        $this->assertEmpty($children['id']->vars['value']);
        $this->assertNull($children['file']->vars['data']);
    }

    public function testSubmittedEmpty()
    {
        $form = $this->factory->create(UploadType::class);

        $form->submit([
            'id' => '',
            'file' => null,
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());
        $this->assertEquals(null, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        $this->assertArrayHasKey('id', $children);
        $this->assertArrayHasKey('file', $children);
        $this->assertEmpty($children['id']->vars['value']);
        $this->assertNull($children['file']->vars['data']);
    }

    public function testSubmitted()
    {
        $form = $this->factory->create(UploadType::class);

        $form->submit([
            'id' => '',
            'file' => $upl = $this->createUploadedFile(),
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $id = $form->get('id')->getData();
        $this->assertRegExp('/^\w+$/', $id);

        /** @var TmpFile $tmpFile */
        $tmpFile = $form->getData();

        $this->assertInstanceOf(TmpFile::class, $tmpFile);
        $this->assertEquals($id, $tmpFile->getBasename());
        $this->assertEquals($this->tmpDir, $tmpFile->getPath());
        $this->assertTrue($tmpFile->isFile());
        $this->assertEquals($upl, $tmpFile->getUploadedFile());
        $this->assertFalse($tmpFile->getUploadedFile()->isFile());

        $view = $form->createView();
        $children = $view->children;

        $this->assertArrayHasKey('id', $children);
        $this->assertArrayHasKey('file', $children);
        $this->assertEquals($id, $children['id']->vars['value']);

        $this->resubmit($id, $upl);
    }

    public function resubmit($id, UploadedFile $upl)
    {
        $form = $this->factory->create(UploadType::class);

        $form->submit([
            'id' => $id,
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals($id, $form->get('id')->getData());

        /** @var TmpFile $tmpFile */
        $tmpFile = $form->getData();

        $this->assertInstanceOf(TmpFile::class, $tmpFile);
        $this->assertEquals($id, $tmpFile->getBasename());
        $this->assertEquals($this->tmpDir, $tmpFile->getPath());
        $this->assertTrue($tmpFile->isFile());
        $this->assertInstanceOf(MockUploadedFile::class, $tmpFile->getUploadedFile());
        $this->assertTrue($tmpFile->getUploadedFile()->isFile());
        $this->assertEquals($upl->getClientOriginalName(), $tmpFile->getUploadedFile()->getClientOriginalName());
        $this->assertEquals($upl->getClientMimeType(), $tmpFile->getUploadedFile()->getClientMimeType());
        $this->assertEquals($upl->getClientSize(), $tmpFile->getUploadedFile()->getClientSize());
    }

    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [new UploadType($this->uploadHelper, $this->tmpDir)],
                [FormType::class => [new FormTypeUploadExtension($this->uploadHelper)]]
            ),
        ];
    }

    protected function setUp()
    {
        $this->uploadHelper = new UploadHelper();
        $this->tmpDir = __DIR__.'/../tmp';
        $this->fs = new Filesystem();

        $this->fs->mkdir($this->tmpDir);

        parent::setUp();
    }

    protected function tearDown()
    {
        $this->fs->remove($this->tmpDir);
    }

    private function createUploadedFile()
    {
        $path = $this->tmpDir.'/'.random_int(0, 10000);
        $this->fs->touch($path);
        file_put_contents($path, $this->getRandonString());

        return new UploadedFile($path, basename($path), $this->getRandonString(), random_int(0, 1000), null, true);
    }

    private function getRandonString()
    {
        return base64_encode(random_bytes(100));
    }
}
