<?php

declare(strict_types=1);

namespace Drupal\layout_discovery\Install\Requirements;

use Drupal\Core\Extension\InstallRequirementsInterface;

/**
 * Install time requirements for the layout_discovery module.
 */
class LayoutDiscoveryRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    $requirements = [];
    if (\Drupal::moduleHandler()->moduleExists('layout_plugin')) {
      $requirements['layout_discovery'] = [
        'description' => t('Layout Discovery cannot be installed because the Layout Plugin module is installed and incompatible.'),
        'severity' => REQUIREMENT_ERROR,
      ];
    }
    return $requirements;
  }

}
