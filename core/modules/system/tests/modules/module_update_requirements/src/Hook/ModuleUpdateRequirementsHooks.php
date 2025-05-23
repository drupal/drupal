<?php

declare(strict_types=1);

namespace Drupal\module_update_requirements\Hook;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for module_update_requirements.
 */
class ModuleUpdateRequirementsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_update_requirements().
   */
  #[Hook('update_requirements')]
  public function updateRequirements(): array {
    return [
      'test.update.error' => [
        'title' => $this->t('UpdateError'),
        'value' => $this->t('None'),
        'description' => $this->t('Update Error.'),
        'severity' => RequirementSeverity::Error,
      ],
      'test.update.error.alter' => [
        'title' => $this->t('UpdateError'),
        'value' => $this->t('None'),
        'description' => $this->t('Update Error.'),
        'severity' => RequirementSeverity::Error,
      ],
    ];
  }

  /**
   * Implements hook_update_requirements_alter().
   */
  #[Hook('update_requirements_alter')]
  public function updateRequirementsAlter(array &$requirements): void {
    $requirements['test.update.error.alter'] = [
      'title' => $this->t('UpdateWarning'),
      'value' => $this->t('None'),
      'description' => $this->t('Update Warning.'),
      'severity' => RequirementSeverity::Warning,
    ];
  }

}
