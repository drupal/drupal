<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Render;

use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Fiber suspension within Twig templates during placeholder rendering.
 */
#[Group('render')]
#[RunTestsInSeparateProcesses]
class LazyBuilderPlaceholderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'twig_fibers_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Tests Fiber::suspend() during twig rendering via placeholders.
   */
  public function testNestedLazyBuilderPlaceholders(): void {
    $render_array = $this->buildRenderArray();

    $output_string = (string) \Drupal::service('renderer')->renderRoot($render_array);

    $items = [
      '<div>Lazy builder content with param1</div>',
      'First Fibers test: param2',
      'Second Fibers test: param2',
      '<div>Lazy builder content with param3</div>',
      'First Fibers test: param4',
      'Second Fibers test: param4',
    ];
    $this->assertStringOrder($items, $output_string);
  }

  /**
   * Asserts the order of an array of strings inside a string.
   *
   * @param array $items
   *   The array of strings.
   * @param array $haystack
   *   The string that should contain $items in order.
   *
   * @todo use a generic assertion once available.
   * @see https://www.drupal.org/project/drupal/issues/2817657
   */
  protected function assertStringOrder(array $items, string $haystack): void {
    $strings = [];
    foreach ($items as $item) {
      if (($pos = strpos($haystack, $item)) === FALSE) {
        $this->fail("Cannot find '$item' in the page");
      }
      $strings[$pos] = $item;
    }
    ksort($strings);
    $ordered = implode(', ', array_map(function ($item) {
      return "'$item'";
    }, $items));
    $this->assertSame($items, array_values($strings), "Found strings, ordered as: $ordered.");
  }

  /**
   * Builds a render array with placeholdered lazy builder callbacks.
   *
   * @return array
   *   The render array with three elements containing lazy builders.
   */
  protected function buildRenderArray(): array {
    return [
      '#type' => 'container',
      'element_1' => [
        '#lazy_builder' => [static::class . '::lazyBuilderCallback', ['param1']],
        '#create_placeholder' => TRUE,
      ],
      'element_2' => [
        '#lazy_builder' => [static::class . '::twigFiberSuspendLazyBuilderCallback', ['param2']],
        '#create_placeholder' => TRUE,
      ],
      'element_3' => [
        '#lazy_builder' => [static::class . '::lazyBuilderCallback', ['param3']],
        '#create_placeholder' => TRUE,
      ],
      'element_4' => [
        '#lazy_builder' => [static::class . '::twigFiberSuspendLazyBuilderCallback', ['param4']],
        '#create_placeholder' => TRUE,
      ],
    ];
  }

  /**
   * Lazy builder callback.
   *
   * @param string $param
   *   A parameter for the callback.
   *
   * @return array
   *   A render array.
   */
  #[TrustedCallback]
  public static function lazyBuilderCallback(string $param): array {
    return [
      '#type' => 'container',
      '#markup' => 'Lazy builder content with ' . $param,
    ];
  }

  /**
   * Lazy builder callback with test template.
   *
   * @param string $param
   *   A parameter for the callback.
   *
   * @return array
   *   A render array containing another lazy builder.
   */
  #[TrustedCallback]
  public static function twigFiberSuspendLazyBuilderCallback(string $param): array {
    return [
      '#type' => 'inline_template',
      '#template' => '<div class="fibers-test">
  First {{ fibers_test_function(message) }}
  Second {{ fibers_test_function(message) }}
</div>',
      '#context' => [
        'message' => $param,
      ],
    ];
  }

}
