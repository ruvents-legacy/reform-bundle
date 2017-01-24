<?php

namespace Ruvents\ReformBundle;

use Symfony\Component\HttpFoundation\File\File;

class Upload
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var File
     */
    protected $file;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

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
