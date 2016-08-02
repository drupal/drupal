<?php

namespace Drupal\KernelTests\Core\HttpKernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests CORS provided by Drupal.
 *
 * @see sites/default/default.services.yml
 * @see \Asm89\Stack\Cors
 * @see \Asm89\Stack\CorsService
 *
 * @group Http
 */
class CorsIntegrationTest extends KernelTestBase implements ServiceModifierInterface {

  /**
   * The cors container configuration.
   *
   * @var null|array
   */
  protected $corsConfig = NULL;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'test_page_test'];

  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'router');
    \Drupal::service('router.builder')->rebuild();
  }

  public function testCrossSiteRequest() {

    // Test default parameters.
    $cors_config = $this->container->getParameter('cors.config');
    $this->assertSame(FALSE, $cors_config['enabled']);
    $this->assertSame([], $cors_config['allowedHeaders']);
    $this->assertSame([], $cors_config['allowedMethods']);
    $this->assertSame(['*'], $cors_config['allowedOrigins']);

    $this->assertSame(FALSE, $cors_config['exposedHeaders']);
    $this->assertSame(FALSE, $cors_config['maxAge']);
    $this->assertSame(FALSE, $cors_config['supportsCredentials']);

    // Configure the CORS stack to allow a specific set of origins, but don't
    // specify an origin header.
    $request = Request::create('/test-page');
    $request->headers->set('Origin', '');
    $cors_config['enabled'] = TRUE;
    $cors_config['allowedOrigins'] = ['http://example.com'];

    $this->corsConfig = $cors_config;
    $this->container->get('kernel')->rebuildContainer();

    /** @var \Symfony\Component\HttpFoundation\Response $response */
    $response = $this->container->get('http_kernel')->handle($request);
    $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    $this->assertEquals('Not allowed.', $response->getContent());

    // Specify a valid origin.
    $request->headers->set('Origin', 'http://example.com');
    $response = $this->container->get('http_kernel')->handle($request);
    $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if (isset($this->corsConfig)) {
      $container->setParameter('cors.config', $this->corsConfig);
    }
  }

}
