<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\HttpKernel;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Content-Length set by Drupal.
 */
#[Group('Http')]
#[RunTestsInSeparateProcesses]
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
