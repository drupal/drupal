<?php

namespace Drupal\KernelTests\Core\Url;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests link generation with hooks.
 *
 * @group Utility
 */
class LinkGenerationTest extends KernelTestBase {

  protected static $modules = ['link_generation_test'];

  /**
   * Tests how hook_link_alter() can affect escaping of the link text.
   */
  public function testHookLinkAlter() {
    $url = Url::fromUri('http://example.com');
    $renderer = \Drupal::service('renderer');

    $link = $renderer->executeInRenderContext(new RenderContext(), function () use ($url) {
      return \Drupal::service('link_generator')->generate(['#markup' => '<em>link with markup</em>'], $url);
    });
    $this->setRawContent($link);
    $this->assertInstanceOf(MarkupInterface::class, $link);
    // Ensure the content of the link is not escaped.
    $this->assertRaw('<em>link with markup</em>');

    // Test just adding text to an already safe string.
    \Drupal::state()->set('link_generation_test_link_alter', TRUE);
    $link = $renderer->executeInRenderContext(new RenderContext(), function () use ($url) {
      return \Drupal::service('link_generator')->generate(['#markup' => '<em>link with markup</em>'], $url);
    });
    $this->setRawContent($link);
    $this->assertInstanceOf(MarkupInterface::class, $link);
    // Ensure the content of the link is escaped.
    $this->assertEscaped('<em>link with markup</em> <strong>Test!</strong>');

    // Test passing a safe string to t().
    \Drupal::state()->set('link_generation_test_link_alter_safe', TRUE);
    $link = $renderer->executeInRenderContext(new RenderContext(), function () use ($url) {
      return \Drupal::service('link_generator')->generate(['#markup' => '<em>link with markup</em>'], $url);
    });
    $this->setRawContent($link);
    $this->assertInstanceOf(MarkupInterface::class, $link);
    // Ensure the content of the link is escaped.
    $this->assertRaw('<em>link with markup</em> <strong>Test!</strong>');

    // Test passing an unsafe string to t().
    $link = $renderer->executeInRenderContext(new RenderContext(), function () use ($url) {
      return \Drupal::service('link_generator')->generate('<em>link with markup</em>', $url);
    });
    $this->setRawContent($link);
    $this->assertInstanceOf(MarkupInterface::class, $link);
    // Ensure the content of the link is escaped.
    $this->assertEscaped('<em>link with markup</em>');
    $this->assertRaw('<strong>Test!</strong>');
  }

}
