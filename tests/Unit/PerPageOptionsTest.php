<?php

namespace Tests\Unit;

use App\Support\PerPageOptions;
use PHPUnit\Framework\TestCase;

class PerPageOptionsTest extends TestCase
{
    public function test_it_builds_proportional_options_for_two_thousand_records(): void
    {
        $this->assertSame(
            [10, 25, 50, 100, 500, 1000, 1500],
            PerPageOptions::forTotal(2000)
        );
    }

    public function test_it_builds_proportional_options_for_more_than_ten_thousand_records(): void
    {
        $this->assertSame(
            [10, 25, 50, 100, 1000, 2500, 5000, 7500, 10000],
            PerPageOptions::forTotal(10353)
        );
    }

    public function test_it_uses_fewer_options_for_small_totals_and_validates_the_selection(): void
    {
        $this->assertSame([10, 25, 50, 100], PerPageOptions::forTotal(400));
        $this->assertSame(500, PerPageOptions::resolve('500', 2000, 10));
        $this->assertSame(10, PerPageOptions::resolve('500', 400, 10));
        $this->assertSame('all', PerPageOptions::resolve('all', 400, 10));
        $this->assertSame(400, PerPageOptions::pageSize('all', 400));
    }
}
