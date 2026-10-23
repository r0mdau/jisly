<?php

/**
 * @see       https://github.com/r0mdau/jisly for the source repository
 * @copyright Copyright (c) 2026 r0mdau (https://github.com/r0mdau)
 * @license   https://github.com/r0mdau/jisly/blob/main/LICENSE.md Apache License 2.0
 */

declare(strict_types=1);

namespace Jisly;

/**
 * Class Jisly
 * @package Jisly
 */
class Jisly
{
    public function __construct(private string $directory)
    {
    }

    public function collection(string $name): JislyCollection
    {
        return new JislyCollection($this->directory, $name);
    }
}
