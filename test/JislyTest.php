<?php
/**
 * @see       https://github.com/r0mdau/jisly for the source repository
 * @copyright Copyright (c) 2023 r0mdau (https://github.com/r0mdau)
 * @license   https://github.com/r0mdau/jisly/blob/main/LICENSE.md Apache License 2.0
 */

declare(strict_types=1);

namespace Jisly;

class JislyTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->db = new Jisly('data');
    }

    public function testGetDbIsJisly()
    {
        $this->assertTrue($this->db instanceof Jisly);
    }

    public function testGetCollectionIsJislyCollection()
    {
        $collection = $this->db->collection('test.db');

        $this->assertTrue($collection instanceof JislyCollection);
    }

    private $db;
}
