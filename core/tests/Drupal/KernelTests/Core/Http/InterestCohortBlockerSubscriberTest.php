<?php

namespace Drupal\KernelTests\Core\Http;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the Permissions-Policy header event subscriber.
 *
 * @group ContentNegotiation
 */
class InterestCohortBlockerSubscriberTest extends KernelTestBase {

  /**
   * Tests that FLoC is blocked by default.
   */
  public function testDefaultBlocking() {
    $request = Request::create('/');
    $response = \Drupal::getContainer()->get('http_kernel')->handle($request);

    $this->assertEquals('interest-cohort=()', $response->headers->get('Permissions-Policy'));
  }

  /**
   * Tests that FLoC blocking can be disabled in settings.php.
   */
  public function testDisableBlockSetting() {
    $settings = Settings::getAll();
    $settings['block_interest_cohort'] = FALSE;
    new Settings($settings);

    $request = Request::create('/');
    $response = \Drupal::getContainer()->get('http_kernel')->handle($request);

    $this->assertFalse($response->headers->has('Permissions-Policy'));
  }

}
