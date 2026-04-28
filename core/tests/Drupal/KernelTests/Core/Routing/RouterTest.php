<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Routing;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Routing\Exception\CacheableResourceNotFoundException;
use Drupal\Core\Routing\Router;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the router.
 */
#[Group('Routing')]
#[RunTestsInSeparateProcesses]
class RouterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
  ];

  /**
   * The router.
   *
   * @var \Drupal\Core\Routing\Router
   */
  protected Router $router;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->router = $this->container->get('router.no_access_checks');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
  }

  /**
   * Tests the router request matching.
   */
  public function testMatchRequest(): void {
    $defaults = $this->router->match('/admin');
    $this->assertIsArray($defaults);
    $this->assertArrayHasKey('_route', $defaults);
    $this->assertEquals('system.admin', $defaults['_route']);

    try {
      $this->router->match('/does/not/exist');
      $this->fail(sprintf(
        '%s::match() should throw an exception for a non-existing path.',
        Router::class,
      ));
    }
    catch (CacheableResourceNotFoundException $exception) {
      $this->assertEquals([], $exception->getCacheContexts());
      $this->assertEquals([], $exception->getCacheTags());
      $this->assertEquals(Cache::PERMANENT, $exception->getCacheMaxAge());
    }
    catch (\Throwable $exception) {
      $this->fail(sprintf(
        '%s::match() should throw %s, %s thrown instead.',
        Router::class,
        CacheableResourceNotFoundException::class,
        get_class($exception),
      ));
    }
  }

}
