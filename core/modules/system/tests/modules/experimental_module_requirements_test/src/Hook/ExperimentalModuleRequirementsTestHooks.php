<?php

declare(strict_types=1);

namespace Drupal\experimental_module_requirements_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for experimental_module_requirements_test.
 */
class ExperimentalModuleRequirementsTestHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name) {
    switch ($route_name) {
      case 'help.page.experimental_module_requirements_test':
        // Make the help text conform to core standards.
        return t('The Experimental Requirements Test module is not done yet. It may eat your data, but you can read the <a href=":url">online documentation for the Experimental Requirements Test module</a>.', [':url' => 'http://www.example.com']);
    }
  }

}
