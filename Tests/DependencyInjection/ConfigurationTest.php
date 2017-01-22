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
        ], 'The child node "default_tmp_dir" at path "ruvents_reform.upload" must be configured.');

        $this->assertConfigurationIsInvalid([
            'ruvents_reform' => [
                'upload' => [
                    'default_tmp_dir' => '',
                ],
            ],
        ], 'The path "ruvents_reform.upload.default_tmp_dir" cannot contain an empty value, but got "".');

        $this->assertConfigurationIsValid([
            'ruvents_reform' => [
                'upload' => [
                    'default_tmp_dir' => 'tmp',
                ],
            ],
        ]);
    }

    protected function getConfiguration()
    {
        return new Configuration();
    }
}
