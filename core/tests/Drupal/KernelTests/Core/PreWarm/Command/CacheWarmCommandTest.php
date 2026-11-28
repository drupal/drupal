<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\PreWarm\Command;

use Drupal\Core\PreWarm\Command\CacheWarmCommand;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests the 'cache:warm' command.
 */
#[Group('Console')]
#[RunTestsInSeparateProcesses]
#[CoversClass(CacheWarmCommand::class)]
class CacheWarmCommandTest extends KernelTestBase {

  /**
   * Tests that the command pre-warms caches.
   */
  public function testCacheWarm(): void {
    // The element info plugin manager is a prewarmable core service that
    // caches its definitions under this cid.
    $cache = $this->container->get('cache.discovery');
    $this->assertFalse($cache->get('element_info'));

    $command = $this->container->get(CacheWarmCommand::class);
    $tester = new CommandTester($command);
    $code = $tester->execute([]);
    $this->assertSame(Command::SUCCESS, $code);
    $this->assertStringContainsString('Warmed caches.', $tester->getDisplay());
    $this->assertNotFalse($cache->get('element_info'));
  }

}
