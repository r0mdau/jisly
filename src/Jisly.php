<?php
/**
 * @see       https://github.com/r0mdau/jisly for the source repository
 * @copyright Copyright (c) 2018 r0mdau (https://github.com/r0mdau)
 * @license   https://github.com/r0mdau/jisly/blob/master/LICENSE.md Apache License 2.0
 */

declare(strict_types=1);

namespace Jisly;

class Jisly
{
    private $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    public function collection($name): JislyCollection
    {
        return new JislyCollection($this->directory, $name);
    }
}
