<?php

namespace Jisly;

class JislyTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
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