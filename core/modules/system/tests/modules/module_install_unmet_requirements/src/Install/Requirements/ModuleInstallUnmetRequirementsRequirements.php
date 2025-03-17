<?php

declare(strict_types=1);

namespace Drupal\module_install_unmet_requirements\Install\Requirements;

use Drupal\Core\Extension\InstallRequirementsInterface;

/**
 * Provides method for checking requirements during install time.
 */
class ModuleInstallUnmetRequirementsRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    $requirements['testing_requirements'] = [
      'title' => t('Testing requirements'),
      'severity' => REQUIREMENT_ERROR,
      'description' => t('Testing requirements failed requirements.'),
    ];

    return $requirements;
  }

}
