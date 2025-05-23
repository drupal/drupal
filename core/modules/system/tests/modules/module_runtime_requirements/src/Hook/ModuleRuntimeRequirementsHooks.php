<?php

declare(strict_types=1);

namespace Drupal\module_runtime_requirements\Hook;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for module_runtime_requirements.
 */
class ModuleRuntimeRequirementsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtimeRequirements(): array {
    return [
      'test.runtime.error' => [
        'title' => $this->t('RuntimeError'),
        'value' => $this->t('None'),
        'description' => $this->t('Runtime Error.'),
        'severity' => RequirementSeverity::Error,
      ],
      'test.runtime.error.alter' => [
        'title' => $this->t('RuntimeError'),
        'value' => $this->t('None'),
        'description' => $this->t('Runtime Error.'),
        'severity' => RequirementSeverity::Error,
      ],
    ];
  }

  /**
   * Implements hook_runtime_requirements_alter().
   */
  #[Hook('runtime_requirements_alter')]
  public function runtimeRequirementsAlter(array &$requirements): void {
    $requirements['test.runtime.error.alter'] = [
      'title' => $this->t('RuntimeWarning'),
      'value' => $this->t('None'),
      'description' => $this->t('Runtime Warning.'),
      'severity' => RequirementSeverity::Warning,
    ];
  }

}
