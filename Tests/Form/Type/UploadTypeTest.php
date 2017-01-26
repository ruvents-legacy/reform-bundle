<?php

namespace Ruvents\ReformBundle\Tests\Form\Type;

use Ruvents\ReformBundle\Form\Extension\FormTypeUploadExtension;
use Ruvents\ReformBundle\Form\Type\UploadType;
use Ruvents\ReformBundle\MockUploadedFile;
use Ruvents\ReformBundle\Tests\UploadTypeTestCase;
use Ruvents\ReformBundle\Upload;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\PreloadedExtension;

class UploadTypeTest extends UploadTypeTestCase
{
    public function testNotSubmitted()
    {
        $form = $this->factory->create(UploadType::class);

        $view = $form->createView();
        $children = $view->children;

        $this->assertArrayHasKey('name', $children);
        $this->assertArrayHasKey('file', $children);

        $this->assertEmpty($children['name']->vars['value']);
        $this->assertNull($children['name']->vars['data']);
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

        $this->assertArrayHasKey('name', $children);
        $this->assertArrayHasKey('file', $children);

        $this->assertEmpty($children['name']->vars['value']);
        $this->assertNull($children['name']->vars['data']);
        $this->assertNull($children['file']->vars['data']);
    }

    public function testSubmitted()
    {
        $form = $this->factory->create(UploadType::class);

        $uploadedFile = $this->createUploadedFile();
        $uploadedExt = $uploadedFile->guessExtension();

        $form->submit([
            'name' => '',
            'file' => $uploadedFile,
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $upload = $form->getData();
        $name = $upload->getName();
        $file = $upload->getFile();

        /**
         * @var Upload           $upload
         * @var MockUploadedFile $file
         */

        $this->assertRegExp('/^[\w]{40}\.'.$uploadedExt.'$/', $name);
        $this->assertInstanceOf(Upload::class, $upload);
        $this->assertInstanceOf(MockUploadedFile::class, $file);
        $this->assertEquals($name, $file->getBasename());
        $this->assertTrue($file->isFile());

        $view = $form->createView();
        $children = $view->children;

        $this->assertArrayHasKey('name', $children);
        $this->assertArrayHasKey('file', $children);
        $this->assertEquals($name, $children['name']->vars['value']);

        $this->resubmit($upload);
    }

    public function resubmit(Upload $oldUpload)
    {
        $form = $this->factory->create(UploadType::class);

        $form->submit([
            'name' => $oldUpload->getName(),
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $upload = $form->getData();
        $name = $upload->getName();
        $file = $upload->getFile();

        /**
         * @var Upload           $upload
         * @var MockUploadedFile $file
         */

        $this->assertEquals($oldUpload->getName(), $name);
        $this->assertEquals($oldUpload->getFile()->getRealPath(), $file->getRealPath());
        $this->assertFileEquals($oldUpload->getFile()->getPathname(), $file->getPathname());
    }

    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [$uploadType = new UploadType($this->tmpDir)],
                [FormType::class => [new FormTypeUploadExtension($uploadType)]]
            ),
        ];
    }
}
