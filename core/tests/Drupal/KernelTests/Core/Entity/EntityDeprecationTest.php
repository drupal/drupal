<?php

namespace Drupal\KernelTests\Core\Entity;

/**
 * Tests the deprecations for the \Drupal\KernelTests\Core\Entity namespace.
 *
 * @group Entity
 * @group legacy
 */
class EntityDeprecationTest extends EntityKernelTestBase {

  /**
   * Tests deprecation of \Drupal\KernelTests\Core\Entity\EntityKernelTestBase::createUser().
   */
  public function testCreateUserDeprecation(): void {
    $this->expectDeprecation('Calling createUser() with $values as the first parameter is deprecated in drupal:10.1.0 and will be removed from drupal:11.0.0. Use createUser(array $permissions = [], $name = NULL, $admin = FALSE, array $values = []) instead. See https://www.drupal.org/node/3330762');
    $this->createUser(['uid' => 2]);
    $this->expectDeprecation('Calling createUser() with $permissions as the second parameter is deprecated in drupal:10.1.0 and will be removed from drupal:11.0.0. Use createUser(array $permissions = [], $name = NULL, $admin = FALSE, array $values = []) instead. See https://www.drupal.org/node/3330762');
    $this->createUser([], ['administer entity_test content']);
  }

}
