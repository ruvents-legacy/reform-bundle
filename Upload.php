<?php

namespace Ruvents\ReformBundle;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

class Upload
{
    /**
     * @Assert\Regex("/^\w+$/")
     *
     * @var string
     */
    protected $name;

    /**
     * @var File
     */
    protected $file;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param File $file
     *
     * @return $this
     */
    public function setFile(File $file)
    {
        $this->file = $file;

        return $this;
    }
}
