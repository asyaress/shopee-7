<?php

namespace Tests\Unit;

use App\Console\Commands\ShopeeSyncAdsCommand;
use Carbon\Carbon;
use Illuminate\Console\OutputStyle;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class ShopeeSyncAdsCommandTest extends TestCase
{
    public function test_old_start_date_is_clamped_to_shopee_history_window(): void
    {
        Carbon::setTestNow('2026-06-20 12:00:00');

        try {
            $command = new ShopeeSyncAdsCommand();
            $buffer = new BufferedOutput();
            $output = new OutputStyle(new StringInput(''), $buffer);

            $outputProperty = new ReflectionProperty($command, 'output');
            $outputProperty->setValue($command, $output);

            $method = new ReflectionMethod($command, 'clampStartToApiWindow');
            $result = $method->invoke($command, Carbon::parse('2025-07-01')->startOfDay());

            $this->assertSame('2025-12-21', $result->toDateString());
            $this->assertStringContainsString('sync dimulai dari 2025-12-21', $buffer->fetch());
        } finally {
            Carbon::setTestNow();
        }
    }
}
