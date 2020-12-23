<?php
/**
 * @see       https://github.com/r0mdau/jisly for the source repository
 * @copyright Copyright (c) 2021 r0mdau (https://github.com/r0mdau)
 * @license   https://github.com/r0mdau/jisly/blob/master/LICENSE.md Apache License 2.0
 */

declare(strict_types=1);

namespace Jisly;

/**
 * Class Jisly
 * @package Jisly
 */
class Jisly
{
    /**
     * @var string
     */
    private $directory;

    /**
     * Jisly constructor.
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * @param string $name
     * @return JislyCollection
     */
    public function collection(string $name): JislyCollection
    {
        return new JislyCollection($this->directory, $name);
    }
}
