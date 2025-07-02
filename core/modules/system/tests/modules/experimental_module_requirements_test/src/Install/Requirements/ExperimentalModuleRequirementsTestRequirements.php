<?php

declare(strict_types=1);

namespace Drupal\experimental_module_requirements_test\Install\Requirements;

use Drupal\Core\Extension\InstallRequirementsInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;

/**
 * Install time requirements for the Experimental Requirements Test module.
 */
class ExperimentalModuleRequirementsTestRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    $requirements = [];
    if (\Drupal::state()->get('experimental_module_requirements_test_requirements', FALSE)) {
      $requirements['experimental_module_requirements_test_requirements'] = [
        'severity' => RequirementSeverity::Error,
        'description' => t('The Experimental Test Requirements module can not be installed.'),
      ];
    }
    return $requirements;
  }

}
