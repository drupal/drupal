<?php

declare(strict_types=1);

namespace Drupal\Tests\automated_cron\Functional;

use Drupal\automated_cron\EventSubscriber\AutomatedCron;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\WaitTerminateTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for automated_cron.
 */
#[CoversClass(AutomatedCron::class)]
#[Group('automated_cron')]
#[RunTestsInSeparateProcesses]
class AutomatedCronTest extends BrowserTestBase {

  use WaitTerminateTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that automated cron runs cron on an HTTP request.
   */
  public function testRunsCronOnHttpRequest(): void {
    // Install cron manually to avoid test setup making an HTTP request which
    // would trigger cron to run before the test starts.
    \Drupal::service('module_installer')->install(['automated_cron']);

    // Set automated_cron interval and times.
    // Any interval > 0 should work.
    $this->config('automated_cron.settings')->set('interval', 10800)->save();

    $this->assertNull(\Drupal::state()->get('system.cron_last'));
    $this->assertNotNull($_SERVER['REQUEST_TIME']);
    $this->setWaitForTerminate();
    $this->drupalGet('/user/login');
    $this->assertGreaterThanOrEqual($_SERVER['REQUEST_TIME'], \Drupal::state()->get('system.cron_last'));
  }

}
