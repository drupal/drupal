<?php

declare(strict_types=1);

namespace Drupal\entity_test_bundle_class\Hook;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test_bundle_class\Entity\EntityTestVariant;
use Drupal\entity_test_bundle_class\Entity\SharedEntityTestBundleClassB;
use Drupal\entity_test_bundle_class\Entity\SharedEntityTestBundleClassA;
use Drupal\entity_test_bundle_class\Entity\EntityTestUserClass;
use Drupal\entity_test_bundle_class\Entity\NonInheritingBundleClass;
use Drupal\entity_test_bundle_class\Entity\EntityTestAmbiguousBundleClass;
use Drupal\entity_test_bundle_class\Entity\EntityTestBundleClass;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for entity_test_bundle_class.
 */
class EntityTestBundleClassHooks {

  /**
   * Implements hook_entity_bundle_info_alter().
   */
  #[Hook('entity_bundle_info_alter')]
  public function entityBundleInfoAlter(&$bundles): void {
    if (!empty($bundles['entity_test']['bundle_class'])) {
      $bundles['entity_test']['bundle_class']['class'] = EntityTestBundleClass::class;
    }
    if (\Drupal::state()->get('entity_test_bundle_class_enable_ambiguous_entity_types', FALSE)) {
      $bundles['entity_test']['bundle_class_2']['class'] = EntityTestBundleClass::class;
      $bundles['entity_test']['entity_test_no_label']['class'] = EntityTestAmbiguousBundleClass::class;
      $bundles['entity_test_no_label']['entity_test_no_label']['class'] = EntityTestAmbiguousBundleClass::class;
    }
    if (\Drupal::state()->get('entity_test_bundle_class_non_inheriting', FALSE)) {
      $bundles['entity_test']['bundle_class']['class'] = NonInheritingBundleClass::class;
    }
    if (\Drupal::state()->get('entity_test_bundle_class_enable_user_class', FALSE)) {
      $bundles['user']['user']['class'] = EntityTestUserClass::class;
    }
    if (\Drupal::state()->get('entity_test_bundle_class_does_not_exist', FALSE)) {
      $bundles['entity_test']['bundle_class']['class'] = '\Drupal\Core\NonExistentClass';
    }
    // Have two bundles share the same base entity class.
    $bundles['shared_type']['bundle_a'] = [
      'label' => 'Bundle A',
      'class' => SharedEntityTestBundleClassA::class,
    ];
    $bundles['shared_type']['bundle_b'] = [
      'label' => 'Bundle B',
      'class' => SharedEntityTestBundleClassB::class,
    ];
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(&$entity_types) : void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    if (\Drupal::state()->get('entity_test_bundle_class_override_base_class', FALSE) && isset($entity_types['entity_test'])) {
      $entity_types['entity_test']->setClass(EntityTestVariant::class);
    }
  }

  /**
   * Implements hook_entity_type_build().
   */
  #[Hook('entity_type_build')]
  public function entityTypeBuild(array &$entity_types) : void {
    // Have multiple entity types share the same class as Entity Test.
    // This allows us to test that AmbiguousBundleClassException does not
    // get thrown when sharing classes.
    /** @var \Drupal\Core\Entity\ContentEntityType $original_type */
    $cloned_type = clone $entity_types['entity_test'];
    $cloned_type->set('bundle_of', 'entity_test');
    $entity_types['shared_type'] = $cloned_type;
    $entity_types['shared_type']->setClass(EntityTest::class);
  }

}
