<?php

declare(strict_types=1);

namespace Drupal\deprecated_twig_template\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for deprecated_twig_template.
 */
class DeprecatedTwigTemplateHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'deprecated_template' => [
        'variables' => [
          'message' => NULL,
        ],
        'deprecated' => 'The "deprecated-template.html.twig" template is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. Use another template instead. See https://www.example.com',
      ],
    ];
  }

}
