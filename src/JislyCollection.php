<?php
/**
 * @see       https://github.com/r0mdau/jisly for the source repository
 * @copyright Copyright (c) 2018 r0mdau (https://github.com/r0mdau)
 * @license   https://github.com/r0mdau/jisly/blob/master/LICENSE.md Apache License 2.0
 */

declare(strict_types=1);

namespace Jisly;

class JislyCollection
{
    const LOGICAL_AND = "AND";
    const LOGICAL_OR = "OR";

    private $file;
    private $data;

    public function __construct($dir, $file)
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

    public function delete($_rid): bool
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

    public function find($document = null, $logical = "OR"): array
    {
        return $this->search($document, $logical);
    }

    public function findOne($document = null, $logical = "OR"): object
    {
        $result = $this->search($document, $logical);
        return reset($result);
    }

    public function insert($document): bool
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

    public function truncate(): bool
    {
        $handle = $this->openFile('w', LOCK_EX);
        flock($handle, LOCK_UN);
        return true;
    }

    public function update($_rid, $values): bool
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

    private function openFile($mode, $lock)
    {
        $handle = fopen($this->file, $mode);
        flock($handle, $lock);
        return $handle;
    }

    private function rewriteFile($handle): bool
    {
        $success = false;
        if (ftruncate($handle, 0) === true && rewind($handle) === true) {
            foreach ($this->data as $data) {
                $success = $this->write($handle, (array)$data);
            }
        }
        return empty($this->data) ?: $success;
    }

    private function search($document = null, $logical = "OR"): array
    {
        if (is_null($document)) {
            return $this->data;
        }

        $results = [];
        foreach ($this->data as $object) {
            $find = 0;
            foreach (array_keys($document) as $documentKey) {
                if (isset($object->{$documentKey}) && $object->{$documentKey} == $document[$documentKey]) {
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

    private function write($handle, $document)
    {
        return fwrite($handle, json_encode($document) . "\n") !== false;
    }
}
