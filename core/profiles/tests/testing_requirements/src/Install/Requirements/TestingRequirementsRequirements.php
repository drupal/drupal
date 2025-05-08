<?php

declare(strict_types=1);

namespace Drupal\testing_requirements\Install\Requirements;

use Drupal\Core\Extension\InstallRequirementsInterface;

/**
 * Install time requirements for the testing_requirements module.
 */
class TestingRequirementsRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    $requirements = [];
    $requirements['testing_requirements'] = [
      'title' => t('Testing requirements'),
      'severity' => REQUIREMENT_ERROR,
      'description' => t('Testing requirements failed requirements.'),
    ];

    return $requirements;
  }

}
