<?php

/**
 * @see       https://github.com/r0mdau/jisly for the source repository
 * @copyright Copyright (c) 2026 r0mdau (https://github.com/r0mdau)
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
    public const string LOGICAL_AND = "AND";
    public const string LOGICAL_OR = "OR";

    private string $file;

    /**
     * @var array<array-key, \stdClass>
     */
    private array $data;

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
     * @param array<string, mixed>|null $document
     * @return array<array-key, \stdClass>
     */
    public function find(?array $document = null, string $logical = "OR"): array
    {
        return $this->search($document, $logical);
    }

    /**
     * @param array<string, mixed>|null $document
     */
    public function findOne(?array $document = null, string $logical = "OR"): ?object
    {
        $result = $this->search($document, $logical);
        $first = reset($result);
        return $first === false ? null : $first;
    }

    /**
     * @param array<string, mixed> $document
     */
    public function insert(array $document): bool
    {
        if (!isset($document["_rid"])) {
            $document["_rid"] = bin2hex(random_bytes(16));
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

    /**
     * @param array<string, mixed> $values
     */
    public function update(string $_rid, array $values): bool
    {
        $success = false;
        $handle = $this->openFile("r+", LOCK_EX);
        $this->hydrateData($handle);

        if (isset($this->data[$_rid])) {
            $this->data[$_rid] = (object)(["_rid" => $_rid] + $values);
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
                if ($data instanceof \stdClass && isset($data->_rid)) {
                    $this->data[$data->_rid] = $data;
                }
            }
        }
    }

    /**
     * @param LOCK_SH|LOCK_EX $lock
     * @return resource
     */
    private function openFile(string $mode, int $lock)
    {
        $handle = fopen($this->file, $mode);
        if ($handle === false) {
            throw new \RuntimeException("Unable to open {$this->file}");
        }
        flock($handle, $lock);
        return $handle;
    }

    /**
     * @param resource $handle
     * @return bool
     */
    private function rewriteFile($handle): bool
    {
        if (ftruncate($handle, 0) === false || rewind($handle) === false) {
            return false;
        }

        $success = true;
        foreach ($this->data as $data) {
            $success = $this->write($handle, (array)$data) && $success;
        }
        return $success;
    }

    /**
     * @param array<string, mixed>|null $document
     * @return array<array-key, \stdClass>
     */
    private function search(?array $document = null, string $logical = "OR"): array
    {
        if (is_null($document)) {
            return $this->data;
        }

        $results = [];
        foreach ($this->data as $object) {
            $find = 0;
            foreach (array_keys($document) as $documentKey) {
                // Loose comparison is intended, so "123" matches 123
                // @phpstan-ignore equal.notAllowed
                if (property_exists($object, $documentKey) && $object->{$documentKey} == $document[$documentKey]) {
                    $find++;
                    if (
                        $logical === static::LOGICAL_OR
                        || ($logical === static::LOGICAL_AND && $find === count($document))
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
     * @param array<string, mixed> $document
     */
    private function write($handle, array $document): bool
    {
        return fwrite($handle, json_encode($document) . "\n") !== false;
    }
}
