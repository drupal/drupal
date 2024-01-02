<?php

namespace Drupal\FunctionalTests\HttpKernel;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Content-Length set by Drupal.
 *
 * @group Http
 */
class ContentLengthTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'http_middleware_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testContentLength(): void {
    // Fire off a request.
    $this->drupalGet(Url::fromRoute('http_middleware_test.test_response'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Content-Length', '40');

    $this->setContainerParameter('no-alter-content-length', TRUE);
    $this->rebuildContainer();

    // Fire the same exact request but this time length is different.
    $this->drupalGet(Url::fromRoute('http_middleware_test.test_response'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Content-Length', '41');
  }

}
