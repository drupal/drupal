<?php

namespace Drupal\KernelTests\Core\Http;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the Permissions-Policy header added by FinishResponseSubscriber.
 *
 * @group Http
 */
class BlockInterestCohortTest extends KernelTestBase {

  /**
   * Tests that FLoC is blocked by default.
   */
  public function testDefaultBlocking() {
    $request = Request::create('/');
    $response = \Drupal::service('http_kernel')->handle($request);

    $this->assertSame('interest-cohort=()', $response->headers->get('Permissions-Policy'));
  }

  /**
   * Tests that an existing interest-cohort policy is not overwritten.
   */
  public function testExistingInterestCohortPolicy() {
    $headers['Permissions-Policy'] = 'interest-cohort=*';

    $kernel = \Drupal::service('http_kernel');
    $request = Request::create('/');
    $response = new Response('', 200, $headers);
    $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
    \Drupal::service('finish_response_subscriber')->onRespond($event);

    $this->assertSame($headers['Permissions-Policy'], $response->headers->get('Permissions-Policy'));
  }

  /**
   * Tests that an existing header is not modified.
   */
  public function testExistingPolicyHeader() {
    $headers['Permissions-Policy'] = 'geolocation=()';

    $kernel = \Drupal::service('http_kernel');
    $request = Request::create('/');
    $response = new Response('', 200, $headers);
    $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
    \Drupal::service('finish_response_subscriber')->onRespond($event);

    $this->assertSame($headers['Permissions-Policy'], $response->headers->get('Permissions-Policy'));
  }

  /**
   * Tests that FLoC blocking is ignored for subrequests.
   */
  public function testSubrequestBlocking() {
    $request = Request::create('/');
    $response = \Drupal::service('http_kernel')->handle($request, HttpKernelInterface::SUB_REQUEST);

    $this->assertFalse($response->headers->has('Permissions-Policy'));
  }

  /**
   * Tests that FLoC blocking can be disabled in settings.php.
   */
  public function testDisableBlockSetting() {
    $settings = Settings::getAll();
    $settings['block_interest_cohort'] = FALSE;
    new Settings($settings);

    $request = Request::create('/');
    $response = \Drupal::service('http_kernel')->handle($request);

    $this->assertFalse($response->headers->has('Permissions-Policy'));
  }

}
