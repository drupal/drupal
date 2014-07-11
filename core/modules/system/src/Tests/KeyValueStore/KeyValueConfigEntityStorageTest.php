<?php

/**
 * @file
 * Contains \Drupal\system\Tests\KeyValueStore\KeyValueConfigEntityStorageTest.
 */

namespace Drupal\system\Tests\KeyValueStore;

use Drupal\config\Tests\ConfigEntityTest;
use Drupal\Core\Entity\KeyValueStore\KeyValueEntityStorage;

/**
 * Tests KeyValueEntityStorage for config entities.
 *
 * @group KeyValueStore
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

}
