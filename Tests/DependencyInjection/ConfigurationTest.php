<?php

namespace Ruvents\ReformBundle\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use Ruvents\ReformBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    use ConfigurationTestCaseTrait;

    public function testEmpty()
    {
        $this->assertConfigurationIsValid([]);
    }

    public function testUpload()
    {
        $this->assertConfigurationIsInvalid([
            'ruvents_reform' => [
                'upload' => null,
            ],
        ], 'The child node "default_path" at path "ruvents_reform.upload" must be configured.');

        $this->assertConfigurationIsInvalid([
            'ruvents_reform' => [
                'upload' => [
                    'default_path' => '',
                ],
            ],
        ], 'The path "ruvents_reform.upload.default_path" cannot contain an empty value, but got "".');

        $this->assertConfigurationIsValid([
            'ruvents_reform' => [
                'upload' => [
                    'default_path' => 'tmp',
                ],
            ],
        ]);
    }

    protected function getConfiguration()
    {
        return new Configuration();
    }
}
