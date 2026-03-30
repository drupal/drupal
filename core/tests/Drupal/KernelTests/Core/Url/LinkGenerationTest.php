<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Url;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests link generation with hooks.
 */
#[Group('Utility')]
#[RunTestsInSeparateProcesses]
class LinkGenerationTest extends KernelTestBase {

  use StringTranslationTrait;

  /**
   * Implements hook_link_alter().
   *
   * @see ::testHookLinkAlter()
   */
  #[Hook('link_alter')]
  public function linkAlter(&$variables): void {
    if (\Drupal::state()->get('link_generation_test_link_alter', FALSE)) {
      // Add text to the end of links.
      if (\Drupal::state()->get('link_generation_test_link_alter_safe', FALSE)) {
        $variables['text'] = $this->t('@text <strong>Test!</strong>', ['@text' => $variables['text']]);
      }
      else {
        $variables['text'] .= ' <strong>Test!</strong>';
      }
    }
  }

  /**
   * Tests how hook_link_alter() can affect escaping of the link text.
   *
   * @see ::linkAlter()
   */
  public function testHookLinkAlter(): void {
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
