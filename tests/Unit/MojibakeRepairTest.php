<?php

namespace Tests\Unit;

use App\Import\Support\MojibakeRepair;
use PHPUnit\Framework\TestCase;

class MojibakeRepairTest extends TestCase
{
    public function test_it_repairs_repeatedly_misdecoded_utf8_without_changing_valid_text(): void
    {
        $repair = new MojibakeRepair;
        $valid = "\u{0935}\u{0930}\u{093F}\u{0937}\u{094D}\u{0920} \u{092A}\u{0941}\u{0932}\u{093F}\u{0938}";
        $once = mb_convert_encoding($valid, 'UTF-8', 'ISO-8859-1');
        $twice = mb_convert_encoding($once, 'UTF-8', 'ISO-8859-1');

        $this->assertSame($valid, $repair->repair($twice));
        $this->assertSame($valid, $repair->repair($valid));
        $this->assertSame('<p>Valid HTML</p>', $repair->repair('<p>Valid HTML</p>'));
    }
}
