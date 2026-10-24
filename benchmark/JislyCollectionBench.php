<?php

/**
 * @see       https://github.com/r0mdau/jisly for the source repository
 * @copyright Copyright (c) 2026 r0mdau (https://github.com/r0mdau)
 * @license   https://github.com/r0mdau/jisly/blob/main/LICENSE.md Apache License 2.0
 */

declare(strict_types=1);

namespace JislyBench;

use Jisly\JislyCollection;
use PhpBench\Attributes as Bench;

/**
 * Times each JislyCollection operation against collections of growing size.
 *
 * Every iteration starts from a freshly written collection file holding
 * `size` documents, so mutating subjects (update, delete) are not skewed
 * by previous iterations.
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\AfterMethods('tearDown')]
#[Bench\ParamProviders('provideSizes')]
#[Bench\Iterations(5)]
#[Bench\Warmup(1)]
#[Bench\OutputTimeUnit('microseconds', precision: 1)]
class JislyCollectionBench
{
    private string $directory;

    private JislyCollection $collection;

    private int $size;

    private int $nextDelete;

    /**
     * @return \Generator<string, array{size: int}>
     */
    public function provideSizes(): \Generator
    {
        foreach ([100, 1_000, 10_000] as $size) {
            yield "{$size} docs" => ['size' => $size];
        }
    }

    /**
     * @param array{size: int} $params
     */
    public function setUp(array $params): void
    {
        $this->size = $params['size'];
        $this->nextDelete = 0;
        $this->directory = sys_get_temp_dir() . '/jisly-bench-' . bin2hex(random_bytes(4));
        mkdir($this->directory);

        $lines = '';
        for ($i = 0; $i < $this->size; $i++) {
            $lines .= json_encode($this->document($i)) . "\n";
        }
        file_put_contents("{$this->directory}/bench.db", $lines);

        $this->collection = new JislyCollection($this->directory, 'bench.db');
    }

    public function tearDown(): void
    {
        @unlink("{$this->directory}/bench.db");
        @rmdir($this->directory);
    }

    /**
     * Opening a collection reads and decodes the whole file.
     */
    #[Bench\Revs(10)]
    public function benchOpen(): void
    {
        new JislyCollection($this->directory, 'bench.db');
    }

    #[Bench\Revs(100)]
    public function benchInsert(): void
    {
        $this->collection->insert(['firstname' => 'Georges', 'name' => 'Lucas', 'age' => 42]);
    }

    #[Bench\Revs(100)]
    public function benchFindAll(): void
    {
        $this->collection->find();
    }

    #[Bench\Revs(100)]
    public function benchFindOr(): void
    {
        $this->collection->find(['name' => 'Name 7', 'age' => 30]);
    }

    #[Bench\Revs(100)]
    public function benchFindAnd(): void
    {
        $this->collection->find(['name' => 'Name 7', 'age' => 30], JislyCollection::LOGICAL_AND);
    }

    #[Bench\Revs(100)]
    public function benchFindOne(): void
    {
        $this->collection->findOne(['_rid' => $this->rid(intdiv($this->size, 2))]);
    }

    /**
     * Update rewrites the whole file, so it scales with the collection size.
     */
    #[Bench\Revs(10)]
    public function benchUpdate(): void
    {
        $this->collection->update($this->rid(intdiv($this->size, 2)), ['firstname' => 'Luke', 'name' => 'Skywalker']);
    }

    /**
     * Each rev deletes a different document, so every call rewrites the file.
     */
    #[Bench\Revs(10)]
    public function benchDelete(): void
    {
        $this->collection->delete($this->rid($this->nextDelete++));
    }

    private function rid(int $i): string
    {
        return "rid-{$i}";
    }

    /**
     * @return array<string, int|string>
     */
    private function document(int $i): array
    {
        return [
            '_rid' => $this->rid($i),
            'firstname' => "Firstname {$i}",
            'name' => 'Name ' . ($i % 10),
            'age' => 18 + ($i % 60),
        ];
    }
}
