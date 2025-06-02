<?php

declare(strict_types=1);

namespace Drupal\Tests\automated_cron\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests for automated_cron.
 *
 * @group automated_cron
 */
class AutomatedCronTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['automated_cron'];

  /**
   * Tests that automated cron runs cron on an HTTP request.
   *
   * @covers \Drupal\automated_cron\EventSubscriber\AutomatedCron::onTerminate
   */
  public function testRunsCronOnHttpRequest(): void {
    // Set automated_cron interval and times.
    // Any interval > 0 should work.
    $this->config('automated_cron.settings')->set('interval', 10800)->save();
    $request = new Request();

    // Cron uses `$_SERVER['REQUEST_TIME']` to set `system.cron_last`
    // because there is no request stack, so we set the request time
    // to the same.
    $expected = $_SERVER['REQUEST_TIME'];
    $request->server->set('REQUEST_TIME', $expected);

    // Invoke `AutomatedCron::onTerminate` and check result.
    $this->assertNull($this->container->get('state')->get('system.cron_last'));
    $this->container->get('kernel')->terminate($request, new Response());
    $this->assertEquals($expected, $this->container->get('state')->get('system.cron_last'));
  }

}
