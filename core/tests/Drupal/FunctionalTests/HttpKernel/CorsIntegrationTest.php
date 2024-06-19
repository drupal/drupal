<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\HttpKernel;

use Drupal\Core\Url;
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
  protected static $modules = ['system', 'test_page_test', 'page_cache'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testCrossSiteRequest(): void {
    // Test default parameters.
    $cors_config = $this->container->getParameter('cors.config');
    $this->assertFalse($cors_config['enabled']);
    $this->assertSame([], $cors_config['allowedHeaders']);
    $this->assertSame([], $cors_config['allowedMethods']);
    $this->assertSame(['*'], $cors_config['allowedOrigins']);

    $this->assertFalse($cors_config['exposedHeaders']);
    $this->assertFalse($cors_config['maxAge']);
    $this->assertFalse($cors_config['supportsCredentials']);

    // Enable CORS with the default options.
    $cors_config['enabled'] = TRUE;

    $this->setContainerParameter('cors.config', $cors_config);
    $this->rebuildContainer();

    // Fire off a request.
    $this->drupalGet('/test-page', [], ['Origin' => 'http://example.com']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', '*');
    $this->assertSession()->responseHeaderNotContains('Vary', 'Origin');

    // Fire the same exact request. This time it should be cached.
    $this->drupalGet('/test-page', [], ['Origin' => 'http://example.com']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', '*');
    $this->assertSession()->responseHeaderNotContains('Vary', 'Origin');

    // Fire a request for a different origin. Verify the CORS header.
    $this->drupalGet('/test-page', [], ['Origin' => 'http://example.org']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', '*');
    $this->assertSession()->responseHeaderNotContains('Vary', 'Origin');

    // Configure the CORS stack to match allowed origins using regex patterns.
    $cors_config['allowedOrigins'] = [];
    $cors_config['allowedOriginsPatterns'] = ['#^http://[a-z-]*\.valid.com$#'];

    $this->setContainerParameter('cors.config', $cors_config);
    $this->rebuildContainer();

    // Fire a request from an origin that isn't allowed.
    /** @var \Symfony\Component\HttpFoundation\Response $response */
    $this->drupalGet('/test-page', [], ['Origin' => 'http://non-valid.com']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderDoesNotExist('Access-Control-Allow-Origin');
    $this->assertSession()->responseHeaderContains('Vary', 'Origin');

    // Specify a valid origin.
    $this->drupalGet('/test-page', [], ['Origin' => 'http://sub-domain.valid.com']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', 'http://sub-domain.valid.com');
    $this->assertSession()->responseHeaderContains('Vary', 'Origin');

    // Test combining allowedOrigins and allowedOriginsPatterns.
    $cors_config['allowedOrigins'] = ['http://domainA.com'];
    $cors_config['allowedOriginsPatterns'] = ['#^http://domain[B-Z-]*\.com$#'];

    $this->setContainerParameter('cors.config', $cors_config);
    $this->rebuildContainer();

    // Specify an origin that does not match allowedOrigins nor
    // allowedOriginsPattern.
    $this->drupalGet('/test-page', [], ['Origin' => 'http://non-valid.com']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderDoesNotExist('Access-Control-Allow-Origin');
    $this->assertSession()->responseHeaderContains('Vary', 'Origin');

    // Specify a valid origin that matches allowedOrigins.
    $this->drupalGet('/test-page', [], ['Origin' => 'http://domainA.com']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', 'http://domainA.com');
    $this->assertSession()->responseHeaderContains('Vary', 'Origin');

    // Specify a valid origin that matches allowedOriginsPatterns.
    $this->drupalGet('/test-page', [], ['Origin' => 'http://domainX.com']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', 'http://domainX.com');
    $this->assertSession()->responseHeaderContains('Vary', 'Origin');

    // Configure the CORS stack to allow a specific origin.
    $cors_config['allowedOrigins'] = ['http://example.com'];
    $cors_config['allowedOriginsPatterns'] = [];

    $this->setContainerParameter('cors.config', $cors_config);
    $this->rebuildContainer();

    // Fire a request from an origin that isn't allowed.
    /** @var \Symfony\Component\HttpFoundation\Response $response */
    $this->drupalGet('/test-page', [], ['Origin' => 'http://non-valid.com']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', 'http://example.com');
    $this->assertSession()->responseHeaderNotContains('Vary', 'Origin');

    // Specify a valid origin.
    $this->drupalGet('/test-page', [], ['Origin' => 'http://example.com']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', 'http://example.com');
    $this->assertSession()->responseHeaderNotContains('Vary', 'Origin');

    // Configure the CORS stack to allow a specific set of origins.
    $cors_config['allowedOrigins'] = ['http://example.com', 'https://drupal.org'];

    $this->setContainerParameter('cors.config', $cors_config);
    $this->rebuildContainer();

    // Fire a request from an origin that isn't allowed.
    /** @var \Symfony\Component\HttpFoundation\Response $response */
    $this->drupalGet('/test-page', [], ['Origin' => 'http://non-valid.com']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', NULL);
    $this->assertSession()->responseHeaderContains('Vary', 'Origin');

    // Specify a valid origin.
    $this->drupalGet('/test-page', [], ['Origin' => 'http://example.com']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', 'http://example.com');
    $this->assertSession()->responseHeaderContains('Vary', 'Origin');

    // Specify a valid origin.
    $this->drupalGet('/test-page', [], ['Origin' => 'https://drupal.org']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Access-Control-Allow-Origin', 'https://drupal.org');
    $this->assertSession()->responseHeaderContains('Vary', 'Origin');

    // Verify POST still functions with 'Origin' header set to site's domain.
    $origin = \Drupal::request()->getSchemeAndHttpHost();

    /** @var \GuzzleHttp\ClientInterface $httpClient */
    $httpClient = $this->getSession()->getDriver()->getClient()->getClient();
    $url = Url::fromUri('base:/test-page');
    $response = $httpClient->request('POST', $url->setAbsolute()->toString(), [
      'headers' => [
        'Origin' => $origin,
      ],
    ]);
    $this->assertEquals(200, $response->getStatusCode());
  }

}
