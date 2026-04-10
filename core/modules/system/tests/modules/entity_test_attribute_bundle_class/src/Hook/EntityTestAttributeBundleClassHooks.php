<?php

declare(strict_types=1);

namespace Drupal\entity_test_attribute_bundle_class\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\entity_test_attribute_bundle_class\Entity\EntityTestBundleClassA;
use Drupal\entity_test_attribute_bundle_class\Entity\EntityTestBundleClassB;

/**
 * Hook implementations for entity_test_attribute_bundle_class.
 */
class EntityTestAttributeBundleClassHooks {

  /**
   * Implements hook_entity_bundle_info().
   */
  #[Hook('entity_bundle_info')]
  public function entityBundleInfo(): array {
    $bundles['entity_test']['bundle_class_a']['class'] = EntityTestBundleClassA::class;
    $bundles['entity_test']['bundle_class_a']['label'] = 'Bundle class A';
    $bundles['entity_test']['bundle_class_a']['translatable'] = TRUE;

    $bundles['entity_test']['bundle_class_b']['class'] = EntityTestBundleClassB::class;
    $bundles['entity_test']['bundle_class_b']['label'] = 'Bundle class B';
    $bundles['entity_test']['bundle_class_b']['translatable'] = TRUE;

    return $bundles;
  }

}
