<?php

declare(strict_types=1);

namespace Drupal\entity_serialization_test\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for entity_serialization_test.
 */
class EntitySerializationTestHooks {

  /**
   * Implements hook_entity_field_access_alter().
   *
   * Overrides some default access control to support testing.
   *
   * @see Drupal\serialization\Tests\EntitySerializationTest::testUserNormalize()
   */
  #[Hook('entity_field_access_alter')]
  public function entityFieldAccessAlter(array &$grants, array $context): void {
    // Override default access control from UserAccessControlHandler to allow
    // access to 'pass' field for the test user.
    if ($context['field_definition']->getName() == 'pass' && $context['account']->getAccountName() == 'serialization_test_user') {
      $grants[':default'] = AccessResult::allowed()->inheritCacheability($grants[':default'])->addCacheableDependency($context['items']->getEntity());
    }
  }

}
