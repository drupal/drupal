<?php

namespace Drupal\FunctionalTests\HttpKernel;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests CORS provided by Drupal.
 *
 * @see sites/default/default.services.yml
 * @see \Asm89\Stack\Cors
 * @see \Asm89\Stack\CorsService
 *
 * @group Http
 */
class CorsIntegrationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'test_page_test', 'page_cache'];

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

    // Enable CORS with the default options.
    $cors_config['enabled'] = TRUE;

    $this->setContainerParameter('cors.config', $cors_config);
    $this->rebuildContainer();

    // Fire off a request.
    $this->drupalGet('/test-page', [], ['Origin' => 'http://example.com']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', 'http://example.com');

    // Fire the same exact request. This time it should be cached.
    $this->drupalGet('/test-page', [], ['Origin' => 'http://example.com']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', 'http://example.com');

    // Fire a request for a different origin. Verify the CORS header.
    $this->drupalGet('/test-page', [], ['Origin' => 'http://example.org']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', 'http://example.org');

    // Configure the CORS stack to allow a specific set of origins.
    $cors_config['allowedOrigins'] = ['http://example.com'];

    $this->setContainerParameter('cors.config', $cors_config);
    $this->rebuildContainer();

    // Fire a request from an origin that isn't allowed.
    /** @var \Symfony\Component\HttpFoundation\Response $response */
    $this->drupalGet('/test-page', [], ['Origin' => 'http://non-valid.com']);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextContains('Not allowed.');

    // Specify a valid origin.
    $this->drupalGet('/test-page', [], ['Origin' => 'http://example.com']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', 'http://example.com');
  }

}
