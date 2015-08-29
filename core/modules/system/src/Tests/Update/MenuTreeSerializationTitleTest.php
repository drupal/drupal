<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\MenuTreeSerializationTitleTest.
 */

namespace Drupal\system\Tests\Update;

use Drupal\Core\StringTranslation\TranslationWrapper;

/**
 * Tests system_update_8001().
 *
 * @group Update
 */
class MenuTreeSerializationTitleTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Ensures that the system_update_8001() runs as expected.
   */
  public function testUpdate() {
    $this->runUpdates();

    // Ensure that some fields got dropped.
    $database = \Drupal::database();
    $schema = $database->schema();

    if (!$schema->tableExists('menu_tree')) {
      return;
    }

    $this->assertFalse($schema->fieldExists('menu_tree', 'title_arguments'));
    $this->assertFalse($schema->fieldExists('menu_tree', 'title_contexts'));

    // Ensure that all titles and description values can be unserialized.
    $select = $database->select('menu_tree');
    $result = $select->fields('menu_tree', ['id', 'title', 'description'])
      ->execute()
      ->fetchAllAssoc('id');

    // The test coverage relies upon the fact that unserialize() would emit a
    // warning if the value is not a valid serialized value.
    foreach ($result as $link) {
      $title = unserialize($link->title);
      $description = unserialize($link->description);
      // Verify that all the links from system module have a been updated with
      // a TranslationWrapper as title and description due to the rebuild.
      if (strpos($link->id, 'system.') === 0) {
        $this->assertTrue($title instanceof TranslationWrapper, get_class($title));
        if ($description) {
          $this->assertTrue($description instanceof TranslationWrapper, get_class($description));
        }
      }
    }
  }

}
