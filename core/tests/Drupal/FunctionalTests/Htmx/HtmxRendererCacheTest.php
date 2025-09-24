<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Htmx;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Render\MainContent\HtmxRenderer;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the cache headers set HtmxRenderer responses.
 */
#[CoversClass(HtmxRenderer::class)]
#[Group('Htmx')]
#[RunTestsInSeparateProcesses]
class HtmxRendererCacheTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'test_htmx',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateUser([
      'access content',
    ]);
  }

  public function testCacheResources():void {
    $options = [
      'query' => [
        MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_htmx',
      ],
    ];
    $this->drupalGet('/htmx-test-attachments/replace', $options);

    $this->assertSession()->responseHeaderExists('X-Drupal-Cache-Tags');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Tags', '4xx-response config:user.role.anonymous http_response');

    $this->assertSession()->responseHeaderExists('X-Drupal-Cache-Contexts');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Contexts', 'user.permissions');

    $this->assertSession()->responseHeaderExists('X-Drupal-Cache');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    // Test that the cache is hit when the same request is made again.
    $this->drupalGet('/htmx-test-attachments/replace', $options);
    $this->assertSession()->responseHeaderExists('X-Drupal-Cache');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
  }

}
