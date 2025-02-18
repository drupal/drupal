<?php

declare(strict_types=1);

namespace Drupal\experimental_module_test\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for experimental_module_test.
 */
class ExperimentalModuleTestHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?\Stringable {
    switch ($route_name) {
      case 'help.page.experimental_module_test':
        // Make the help text conform to core standards.
        return $this->t('The Experimental Test module is not done yet. It may eat your data, but you can read the <a href=":url">online documentation for the Experimental Test module</a>.', [':url' => 'http://www.example.com']);
    }
    return NULL;
  }

}
