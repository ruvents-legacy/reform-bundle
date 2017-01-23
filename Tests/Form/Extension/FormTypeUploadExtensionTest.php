<?php

namespace Ruvents\ReformBundle\Tests\Form\Extension;

use Ruvents\ReformBundle\Form\Extension\FormTypeUploadExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class FormTypeUploadExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetExtendedType()
    {
        /** @var FormTypeUploadExtension $ext */
        $ext = $this->getMockBuilder(FormTypeUploadExtension::class)
            ->setMethodsExcept(['getExtendedType'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertEquals(FormType::class, $ext->getExtendedType());
    }
}
