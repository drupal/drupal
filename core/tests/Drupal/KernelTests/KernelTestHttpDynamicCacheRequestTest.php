<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\Traits\Core\Cache\PageCachePolicyTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests making HTTP requests with dynamic page cache in a kernel test.
 *
 * @group PHPUnit
 * @group Test
 * @group KernelTests
 */
class KernelTestHttpDynamicCacheRequestTest extends KernelTestBase implements ServiceModifierInterface {

  use PageCachePolicyTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'system_test',
    'dynamic_page_cache',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Tests a request is cached and retrieved.
   */
  public function testRequestAuthenticated(): void {
    $this->setUpCurrentUser();

    $this->drupalGet('/system-test/main-content-handling');
    $this->assertEquals(Response::HTTP_OK, $this->getSession()->getStatusCode());
    $this->assertSession()->pageTextContains('Content to test main content fallback');
    $this->assertEquals('MISS', $this->getSession()->getResponseHeaders()['x-drupal-dynamic-cache'][0]);

    $this->drupalGet('/system-test/main-content-handling');
    $this->assertEquals(Response::HTTP_OK, $this->getSession()->getStatusCode());
    $this->assertSession()->pageTextContains('Content to test main content fallback');
    $this->assertEquals('HIT', $this->getSession()->getResponseHeaders()['x-drupal-dynamic-cache'][0]);
  }

}
