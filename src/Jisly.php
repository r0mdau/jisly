<?php

namespace Jisly;

class Jisly
{
    private $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    public function collection($name)
    {
        return new JislyCollection($this->directory, $name);
    }
}
