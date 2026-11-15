<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Root view Inertia memakai @vite. Test tidak boleh bergantung pada
        // hasil `bun run build` (manifest) atau dev server yang sedang jalan,
        // karena CI menjalankan PHPUnit sebelum build aset.
        $this->withoutVite();
    }
}
