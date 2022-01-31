<?php
/**
 * @see       https://github.com/r0mdau/jisly for the source repository
 * @copyright Copyright (c) 2022 r0mdau (https://github.com/r0mdau)
 * @license   https://github.com/r0mdau/jisly/blob/main/LICENSE.md Apache License 2.0
 */

declare(strict_types=1);

namespace Jisly;

/**
 * Class JislyCollection
 * @package Jisly
 */
class JislyCollection
{
    const LOGICAL_AND = "AND";
    const LOGICAL_OR = "OR";

    /**
     * @var string
     */
    private $file;

    /**
     * @var array
     */
    private $data;

    /**
     * JislyCollection constructor.
     * @param string $dir
     * @param string $file
     */
    public function __construct(string $dir, string $file)
    {
        $this->file = "{$dir}/{$file}";
        $this->data = [];

        if (!file_exists($this->file)) {
            touch($this->file);
        } else {
            $handle = $this->openFile('r', LOCK_SH);
            $this->hydrateData($handle);
            flock($handle, LOCK_UN);
        }
    }

    /**
     * @param string $_rid
     * @return bool
     */
    public function delete(string $_rid): bool
    {
        $success = false;
        $handle = $this->openFile("r+", LOCK_EX);
        $this->hydrateData($handle);

        if (isset($this->data[$_rid])) {
            unset($this->data[$_rid]);
            $success = $this->rewriteFile($handle);
        }
        flock($handle, LOCK_UN);
        return $success;
    }

    /**
     * @param array|null $document
     * @param string $logical
     * @return array
     */
    public function find(array $document = null, string $logical = "OR"): array
    {
        return $this->search($document, $logical);
    }

    /**
     * @param array|null $document
     * @param string $logical
     * @return object
     */
    public function findOne(array $document = null, string $logical = "OR"): object
    {
        $result = $this->search($document, $logical);
        return reset($result);
    }

    /**
     * @param array $document
     * @return bool
     */
    public function insert(array $document): bool
    {
        if (!isset($document["_rid"])) {
            $document["_rid"] = md5(uniqid((string)rand(1, 100), true));
        }

        $handle = $this->openFile('a', LOCK_EX);
        $success = $this->write($handle, $document);
        $this->data[] = (object)$document;
        flock($handle, LOCK_UN);
        return $success;
    }

    /**
     * @return bool
     */
    public function truncate(): bool
    {
        $handle = $this->openFile('w', LOCK_EX);
        flock($handle, LOCK_UN);
        return true;
    }

    /**
     * @param string $_rid
     * @param array $values
     * @return bool
     */
    public function update(string $_rid, array $values): bool
    {
        $success = false;
        $handle = $this->openFile("r+", LOCK_EX);
        $this->hydrateData($handle);

        if (isset($this->data[$_rid])) {
            $this->data[$_rid] = (object)$values;
            $success = $this->rewriteFile($handle);
        }
        flock($handle, LOCK_UN);
        return $success;
    }

    /**
     * @param resource $handle
     */
    private function hydrateData($handle): void
    {
        $this->data = [];
        while (!feof($handle)) {
            $data = fgets($handle);
            if ($data !== false) {
                $data = json_decode($data);
                if (isset($data->_rid)) {
                    $this->data[$data->_rid] = $data;
                }
            }
        }
    }

    /**
     * @param string $mode
     * @param int $lock
     * @return resource
     */
    private function openFile(string $mode, int $lock)
    {
        $handle = fopen($this->file, $mode);
        flock($handle, $lock);
        return $handle;
    }

    /**
     * @param resource $handle
     * @return bool
     */
    private function rewriteFile($handle): bool
    {
        $success = false;
        if (ftruncate($handle, 0) === true && rewind($handle) === true) {
            foreach ($this->data as $data) {
                $success = $this->write($handle, (array)$data);
            }
        }
        return $this->data !== null && isset($this->data) ? true : $success;
    }

    /**
     * @param array|null $document
     * @param string $logical
     * @return array
     */
    private function search(array $document = null, string $logical = "OR"): array
    {
        if (is_null($document)) {
            return $this->data;
        }

        $results = [];
        foreach ($this->data as $object) {
            $find = 0;
            foreach (array_keys($document) as $documentKey) {
                /** @phpstan-ignore-next-line */
                if (property_exists($object, $documentKey) && $object->{$documentKey} == $document[$documentKey]) {
                    $find++;
                    if ($logical == static::LOGICAL_OR
                        || ($logical == static::LOGICAL_AND && $find == count($document))
                    ) {
                        $results[$object->_rid] = $object;
                    }
                }
            }
        }
        return $results;
    }

    /**
     * @param resource $handle
     * @param array $document
     * @return bool
     */
    private function write($handle, array $document)
    {
        return fwrite($handle, json_encode($document) . "\n") !== false;
    }
}
