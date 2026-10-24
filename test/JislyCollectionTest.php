<?php
/**
 * @see       https://github.com/r0mdau/jisly for the source repository
 * @copyright Copyright (c) 2026 r0mdau (https://github.com/r0mdau)
 * @license   https://github.com/r0mdau/jisly/blob/main/LICENSE.md Apache License 2.0
 */

declare(strict_types=1);

namespace Jisly;

class JislyCollectionTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->db = new Jisly("data");
        $this->file = "data/test.db";
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        foreach ([UntruncatableStream::SCHEME, UnopenableStream::SCHEME] as $scheme) {
            if (in_array($scheme, stream_get_wrappers(), true)) {
                stream_wrapper_unregister($scheme);
            }
        }
        $handle = fopen($this->file, 'w');
        fclose($handle);
    }

    public function testConstructCollectionCreateFile()
    {
        $this->db->collection("yo.db");

        $this->assertTrue(file_exists("data/yo.db"));
        unlink("data/yo.db");
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

    public function testConstructThrowsIfFileCannotBeOpened()
    {
        $this->write(["_rid" => "cool", "key" => 123]);
        stream_wrapper_register(UnopenableStream::SCHEME, UnopenableStream::class);

        $this->expectException(\RuntimeException::class);
        // fopen() warns before returning false, which is the expected failure here
        set_error_handler(static fn () => true, E_WARNING);
        try {
            new JislyCollection(UnopenableStream::SCHEME . "://data", "test.db");
        } finally {
            restore_error_handler();
        }
    }

    public function testDeleteReturnFalseIfFileCannotBeTruncated()
    {
        $this->write(["_rid" => "cool", "key" => 123]);

        $this->assertFalse($this->getUntruncatableCollection()->delete("cool"));
    }

    public function testFindWithNullDocument()
    {
        $this->assertThat($this->getCollection()->find(), $this->isEmpty());
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

    public function testFindOneByRid()
    {
        $this->write(["_rid" => "cool", "key" => 123]);
        $this->write(["_rid" => "cooly", "key" => 456]);

        $this->assertEquals(
            (object)["_rid" => "cooly", "key" => 456],
            $this->getCollection()->findOne(["_rid" => "cooly"])
        );
    }

    public function testFindByRidLogicalAND()
    {
        $this->write(["_rid" => "cool", "key" => 123]);
        $this->write(["_rid" => "cooly", "key" => 123]);

        $this->assertEquals(
            ["cooly" => (object)["_rid" => "cooly", "key" => 123]],
            $this->getCollection()->find(["_rid" => "cooly", "key" => 123], JislyCollection::LOGICAL_AND)
        );
        $this->assertEquals(
            [],
            $this->getCollection()->find(["_rid" => "cooly", "key" => 456], JislyCollection::LOGICAL_AND)
        );
    }

    public function testFindByRidKeepsLooseComparison()
    {
        $this->write(["_rid" => "123", "key" => 456]);

        $this->assertEquals(
            (object)["_rid" => "123", "key" => 456],
            $this->getCollection()->findOne(["_rid" => "0123"])
        );
    }

    public function testFindOneReturnNullIfNotFound()
    {
        $this->write(["_rid" => "cool", "key" => 123]);

        $this->assertNull($this->getCollection()->findOne(["key" => 456]));
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

        $this->assertTrue(isset($object->_rid));
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

    public function testUpdateWithoutRidKeepsDocument()
    {
        $this->write(["_rid" => "cool", "key" => 123]);
        $this->write(["_rid" => "coolos", "key" => 456]);
        $this->getCollection()->update("cool", ["key" => 7]);

        $this->assertEquals(
            [
                "cool" => (object)["_rid" => "cool", "key" => 7],
                "coolos" => (object)["_rid" => "coolos", "key" => 456]
            ],
            $this->getDataFromFile()
        );
    }

    public function testUpdateCannotChangeRid()
    {
        $this->write(["_rid" => "cool", "key" => 123]);
        $this->write(["_rid" => "coolos", "key" => 456]);
        $this->getCollection()->update("cool", ["_rid" => "coolos", "key" => 7]);

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

    public function testUpdateReturnFalseIfFileCannotBeTruncated()
    {
        $this->write(["_rid" => "cool", "key" => 123]);

        $this->assertFalse($this->getUntruncatableCollection()->update("cool", ["_rid" => "cool", "key" => 7]));
    }

    private function getDataFromFile()
    {
        $handle = fopen($this->file, 'r');
        $data = [];
        while (!feof($handle)) {
            $line = fgets($handle);
            if ($line !== false) {
                $line = json_decode($line);
                if (isset($line->_rid)) {
                    $data[$line->_rid] = $line;
                }
            }
        }
        return $data;
    }

    private function getCollection()
    {
        return $this->db->collection("test.db");
    }

    private function getUntruncatableCollection()
    {
        stream_wrapper_register(UntruncatableStream::SCHEME, UntruncatableStream::class);
        return new JislyCollection(UntruncatableStream::SCHEME . "://data", "test.db");
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

/**
 * Stream wrapper over a real file whose truncate always fails.
 */
class UntruncatableStream
{
    const SCHEME = "untruncatable";

    /**
     * @var resource|null
     */
    public $context;

    /**
     * @var resource
     */
    private $handle;

    public function stream_open($path, $mode)
    {
        $this->handle = fopen(static::realPath($path), $mode);
        return $this->handle !== false;
    }

    public function stream_read($count)
    {
        return fread($this->handle, $count);
    }

    public function stream_write($data)
    {
        return fwrite($this->handle, $data);
    }

    public function stream_eof()
    {
        return feof($this->handle);
    }

    public function stream_seek($offset, $whence)
    {
        return fseek($this->handle, $offset, $whence) === 0;
    }

    public function stream_tell()
    {
        return ftell($this->handle);
    }

    public function stream_lock($operation)
    {
        return flock($this->handle, $operation);
    }

    public function stream_truncate($size)
    {
        return false;
    }

    public function stream_close()
    {
        fclose($this->handle);
    }

    public function url_stat($path, $flags)
    {
        $realPath = static::realPath($path);
        return file_exists($realPath) ? stat($realPath) : false;
    }

    private static function realPath($path)
    {
        return substr($path, strlen(static::SCHEME . "://"));
    }
}

/**
 * Stream wrapper over a real file that can never be opened.
 */
class UnopenableStream extends UntruncatableStream
{
    const SCHEME = "unopenable";

    public function stream_open($path, $mode)
    {
        return false;
    }
}
