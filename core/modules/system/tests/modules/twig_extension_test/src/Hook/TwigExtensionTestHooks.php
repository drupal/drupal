<?php

declare(strict_types=1);

namespace Drupal\twig_extension_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for twig_extension_test.
 */
class TwigExtensionTestHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) : array {
    return [
      'twig_extension_test_filter' => [
        'variables' => [
          'message' => NULL,
          'safe_join_items' => NULL,
        ],
        'template' => 'twig_extension_test.filter',
      ],
      'twig_extension_test_function' => [
        'render element' => 'element',
        'template' => 'twig_extension_test.function',
      ],
    ];
  }

}
