<?php

/**
 * @file
 * Contains \Drupal\system\Tests\KeyValueStore\KeyValueConfigEntityStorageTest.
 */

namespace Drupal\system\Tests\KeyValueStore;

use Drupal\config\Tests\ConfigEntityTest;
use Drupal\Core\Entity\KeyValueStore\KeyValueEntityStorage;

/**
 * Tests config entity CRUD with key value entity storage.
 */
class KeyValueConfigEntityStorageTest extends ConfigEntityTest {

  /**
   * {@inheritdoc}
   */
  const MAX_ID_LENGTH = KeyValueEntityStorage::MAX_ID_LENGTH;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('keyvalue_test');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'KeyValueEntityStorage config entity test',
      'description' => 'Tests KeyValueEntityStorage for config entities.',
      'group' => 'Entity API',
    );
  }

}
