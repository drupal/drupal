<?php

declare(strict_types=1);

namespace Drupal\Tests\automated_cron\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\WaitTerminateTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for automated_cron.
 */
#[Group('automated_cron')]
#[RunTestsInSeparateProcesses]
class AutomatedCronTest extends BrowserTestBase {

  use WaitTerminateTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['automated_cron'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that automated cron runs cron on an HTTP request.
   *
   * @legacy-covers \Drupal\automated_cron\EventSubscriber\AutomatedCron::onTerminate
   */
  public function testRunsCronOnHttpRequest(): void {
    // Set automated_cron interval and times.
    // Any interval > 0 should work.
    $this->config('automated_cron.settings')->set('interval', 10800)->save();
    \Drupal::state()->delete('system.cron_last');

    $this->assertNull(\Drupal::state()->get('system.cron_last'));
    $this->assertNotNull($_SERVER['REQUEST_TIME']);
    $this->setWaitForTerminate();
    $this->drupalGet('/user/login');
    $this->assertGreaterThanOrEqual($_SERVER['REQUEST_TIME'], \Drupal::state()->get('system.cron_last'));
  }

}
