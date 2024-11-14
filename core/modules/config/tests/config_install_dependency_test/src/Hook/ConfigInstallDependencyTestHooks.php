<?php

declare(strict_types=1);

namespace Drupal\config_install_dependency_test\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for config_install_dependency_test.
 */
class ConfigInstallDependencyTestHooks {

  /**
   * Implements hook_ENTITY_TYPE_create().
   */
  #[Hook('config_test_create')]
  public function configTestCreate(EntityInterface $entity) {
    // Add an enforced dependency on this module so that we can test if this is
    // possible during module installation.
    $entity->setEnforcedDependencies(['module' => ['config_install_dependency_test']]);
  }

}
