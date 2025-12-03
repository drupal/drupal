<?php

declare(strict_types=1);

namespace Drupal\module_install_requirements\Install\Requirements;

use Drupal\Core\Extension\InstallRequirementsInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;

/**
 * Provides method for checking requirements during install time.
 */
class ModuleInstallRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    $GLOBALS['module_install_requirements'] = 'module_install_requirements';

    return [
      'test.runtime.install' => [
        'title' => t('InstallOk'),
        'value' => t('None'),
        'description' => t('Install OK.'),
        'severity' => RequirementSeverity::OK,
      ],
    ];
  }

}
