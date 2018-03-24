<?php
/**
 * @see       https://github.com/r0mdau/jisly for the source repository
 * @copyright Copyright (c) 2018 r0mdau (https://github.com/r0mdau)
 * @license   https://github.com/r0mdau/jisly/blob/master/LICENSE.md Apache License 2.0
 */

namespace Jisly;

class JislyCollectionTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->db = new Jisly("data");
        $this->file = "data/test.db";
    }

    public function tearDown()
    {
        parent::tearDown();
        $handle = fopen($this->file, 'w');
        fclose($handle);
    }

    public function testConstructCollectionCreateFile()
    {
        $this->db->collection("test.db");

        $this->assertTrue(file_exists($this->file));
    }

    public function testDelete()
    {
        $this->write(["_rid" => "cool", "key" => 123]);
        $this->write(["_rid" => "cooly", "key" => 456]);
        $this->getCollection()->delete("cool");

        $this->assertEquals(
            ["cooly" => (object)["_rid" => "cooly", "key" => 456]],
            $this->getDataFromFile()
        );
    }

    public function testDeleteReturnTrueIfSuccess()
    {
        $this->write(["_rid" => "cooly", "key" => 456]);

        $this->assertTrue($this->getCollection()->delete("cooly"));
    }

    public function testDeleteReturnFalseIfFail()
    {
        $this->assertFalse($this->getCollection()->delete("key"));
    }

    public function testFindLogicalOR()
    {
        $this->write(["_rid" => "cool", "key" => 123]);
        $this->write(["_rid" => "cooly", "key" => 123]);
        $this->write(["_rid" => "coolos", "key" => 456]);
        $this->write(["_rid" => "cooloss", "key" => 456]);
        $result = $this->getCollection()->find(["_rid" => "cooloss", "key" => 123]);

        $this->assertEquals(
            [
                "cool" => (object)["_rid" => "cool", "key" => 123],
                "cooly" => (object)["_rid" => "cooly", "key" => 123],
                "cooloss" => (object)["_rid" => "cooloss", "key" => 456],
            ],
            $result
        );
    }

    public function testFindLogicalAND()
    {
        $this->write(["_rid" => "cool", "key" => 123, "name" => "yo"]);
        $this->write(["_rid" => "cooly", "key" => 123, "name" => "yo"]);
        $this->write(["_rid" => "coolos", "key" => 123, "name" => "tu"]);
        $this->write(["_rid" => "cooloss", "key" => 456]);
        $result = $this->getCollection()->find(["name" => "yo", "key" => 123], JislyCollection::LOGICAL_AND);

        $this->assertEquals(
            [
                "cool" => (object)["_rid" => "cool", "key" => 123, "name" => "yo"],
                "cooly" => (object)["_rid" => "cooly", "key" => 123, "name" => "yo"],
            ],
            $result
        );
    }

    public function testFindOne()
    {
        $this->write(["_rid" => "cool", "key" => 123]);
        $this->write(["_rid" => "cooly", "key" => 123]);
        $this->write(["_rid" => "coolos", "key" => 456]);
        $this->write(["_rid" => "cooloss", "key" => 456]);
        $result = $this->getCollection()->findOne(["key" => 123]);

        $this->assertEquals(
            (object)["_rid" => "cool", "key" => 123],
            $result
        );
    }

    public function testInsert()
    {
        $this->getCollection()->insert(["_rid" => "cool", "key" => 123]);
        $this->getCollection()->insert(["_rid" => "cooly", "key" => 456]);


        $this->assertEquals(
            [
                "cool" => (object)["_rid" => "cool", "key" => 123],
                "cooly" => (object)["_rid" => "cooly", "key" => 456]
            ],
            $this->getDataFromFile()
        );
    }

    public function testInsertReturnTrue()
    {
        $this->assertTrue($this->getCollection()->insert(["key" => 123]));
    }

    public function testInsertCreateRandom_rid()
    {
        $this->assertTrue($this->getCollection()->insert(["key" => 123]));
        $data = $this->getDataFromFile();
        $object = reset($data);

        $this->assertObjectHasAttribute("_rid", $object);
    }

    public function testTruncate()
    {
        $this->getCollection()->insert(["key" => 123]);

        $this->assertTrue($this->getCollection()->truncate());
        $this->assertEmpty($this->getDataFromFile());
    }

    public function testUpdate()
    {
        $this->write(["_rid" => "cool", "key" => 123]);
        $this->write(["_rid" => "coolos", "key" => 456]);
        $this->getCollection()->update("cool", ["_rid" => "cool", "key" => 7]);

        $this->assertEquals(
            [
                "cool" => (object)["_rid" => "cool", "key" => 7],
                "coolos" => (object)["_rid" => "coolos", "key" => 456]
            ],
            $this->getDataFromFile()
        );
    }

    public function testUpdateReturnTrueIfSuccess()
    {
        $this->write(["_rid" => "cool", "key" => 123]);

        $this->assertTrue($this->getCollection()->update("cool", ["_rid" => "cool", "key" => 7]));
    }

    public function testUpdateReturnFalseIfFail()
    {
        $this->assertFalse($this->getCollection()->update("cool", ["_rid" => "cool", "key" => 7]));
    }

    private function getDataFromFile()
    {
        $handle = fopen($this->file, 'r');
        $data = [];
        while (!feof($handle)) {
            $line = json_decode(fgets($handle));
            if (isset($line->_rid)) {
                $data[$line->_rid] = $line;
            }
        }
        return $data;
    }

    private function getCollection()
    {
        return $this->db->collection("test.db");
    }

    private function write($document)
    {
        $handle = fopen($this->file, 'a');
        fwrite($handle, json_encode($document) . "\n");
        fclose($handle);
    }

    private $db;
    private $file;
}