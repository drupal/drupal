<?php

declare(strict_types=1);

namespace Drupal\update_script_test\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Requirements for the Update Script Test module.
 */
class UpdateScriptTestRequirements {

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Implements hook_update_requirements().
   */
  #[Hook('update_requirements')]
  public function update(): array {
    $requirements = [];
    // Set a requirements warning or error when the test requests it.
    $requirement_type = $this->configFactory->get('update_script_test.settings')->get('requirement_type');
    switch ($requirement_type) {
      case RequirementSeverity::Warning->value:
        $requirements['update_script_test'] = [
          'title' => 'Update script test',
          'value' => 'Warning',
          'description' => 'This is a requirements warning provided by the update_script_test module.',
          'severity' => RequirementSeverity::Warning,
        ];
        break;

      case RequirementSeverity::Error->value:
        $requirements['update_script_test'] = [
          'title' => 'Update script test',
          'value' => 'Error',
          'description' => 'This is a (buggy description fixed in update_script_test_requirements_alter()) requirements error provided by the update_script_test module.',
          'severity' => RequirementSeverity::Error,
        ];
        break;
    }
    return $requirements;
  }

  /**
   * Implements hook_update_requirements_alter().
   */
  #[Hook('update_requirements_alter')]
  public function updateAlter(array &$requirements): void {
    if (isset($requirements['update_script_test']) && $requirements['update_script_test']['severity'] === RequirementSeverity::Error) {
      $requirements['update_script_test']['description'] = 'This is a requirements error provided by the update_script_test module.';
    }
  }

}
