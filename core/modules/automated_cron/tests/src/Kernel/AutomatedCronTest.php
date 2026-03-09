<?php

declare(strict_types=1);

namespace Drupal\Tests\automated_cron\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests for automated_cron.
 */
#[Group('automated_cron')]
#[RunTestsInSeparateProcesses]
class AutomatedCronTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['automated_cron'];

  /**
   * Tests that automated cron does not run cron on a CLI request.
   *
   * @legacy-covers \Drupal\automated_cron\EventSubscriber\AutomatedCron::onTerminate
   */
  public function testCronDoesNotRunOnCliRequest(): void {
    // Set automated_cron interval and times.
    // Any interval > 0 should work.
    $this->config('automated_cron.settings')->set('interval', 10800)->save();
    $request = new Request();

    // Cron uses `$_SERVER['REQUEST_TIME']` to set `system.cron_last`
    // because there is no request stack, so we set the request time
    // to the same.
    $request->server->set('REQUEST_TIME', $_SERVER['REQUEST_TIME']);

    // Invoke `AutomatedCron::onTerminate` and check result.
    $this->assertNull($this->container->get('state')->get('system.cron_last'));
    $this->container->get('kernel')->terminate($request, new Response());
    $this->assertNull($this->container->get('state')->get('system.cron_last'));
  }

}
