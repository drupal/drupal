<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\EventSubscriber;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that debug cacheability header lines do not exceed Apache limit.
 */
#[Group('EventSubscriber')]
#[RunTestsInSeparateProcesses]
class HttpResponseDebugCacheabilityHeadersTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['http_response_debug_cacheability_headers_test'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->setParameter('http.response.debug_cacheability_headers', TRUE);
  }

  /**
   * Tests that cache debug headers do not error from exceeding line limits.
   */
  public function testCacheDebugHeadersLineLength(): void {
    $assertSession = $this->assertSession();
    $this->drupalGet('test-cache-contexts-headers');
    $assertSession->statusCodeEquals(200);
    $assertSession->addressEquals('test-cache-contexts-headers');
    $headers = $this->getSession()->getResponseHeaders();
    $this->assertArrayHasKey('x-drupal-cache-contexts', $headers);
    $context_lines = (array) $headers['x-drupal-cache-contexts'];
    // The payload is engineered to exceed the Apache line limit, so the header
    // must have been split across multiple lines, and each individual line must
    // stay under the 8190-byte limit that would otherwise trigger a 500.
    $this->assertGreaterThan(1, count($context_lines));
    foreach ($context_lines as $line) {
      $this->assertLessThanOrEqual(8190, strlen($line));
    }
    // Merge multiple cache contexts headers together if needed.
    $contexts = implode(' ', $context_lines);
    $this->assertStringContainsString('url.query_args:0000', $contexts);
    $this->assertStringContainsString('url.query_args:0699', $contexts);

    $this->drupalGet('test-cache-tags-headers');
    $assertSession->statusCodeEquals(200);
    $assertSession->addressEquals('test-cache-tags-headers');
    $headers = $this->getSession()->getResponseHeaders();
    $this->assertArrayHasKey('x-drupal-cache-tags', $headers);
    $tag_lines = (array) $headers['x-drupal-cache-tags'];
    $this->assertGreaterThan(1, count($tag_lines));
    foreach ($tag_lines as $line) {
      $this->assertLessThanOrEqual(8190, strlen($line));
    }
    // Merge multiple cache tags headers together if needed.
    $tags = implode(' ', $tag_lines);
    $this->assertStringContainsString('cache-tag:00000', $tags);
    $this->assertStringContainsString('cache-tag:00799', $tags);
  }

}
