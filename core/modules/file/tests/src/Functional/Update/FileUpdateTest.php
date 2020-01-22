<?php

namespace Drupal\Tests\file\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests File update path.
 *
 * @group legacy
 */
class FileUpdateTest extends UpdatePathTestBase {

  /**
   * Modules to enable after the database is loaded.
   */
  protected static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.file_formatters_update_2677990.php',
    ];
  }

  /**
   * Tests file_update_8001().
   */
  public function testPostUpdate8001() {
    $view = 'core.entity_view_display.node.article.default';

    // Check that field_file_generic formatter has no
    // use_description_as_link_text setting.
    $formatter_settings = $this->config($view)->get('content.field_file_generic_2677990.settings');
    $this->assertTrue(!isset($formatter_settings['use_description_as_link_text']));

    // Check that field_file_table formatter has no use_description_as_link_text
    // setting.
    $formatter_settings = $this->config($view)->get('content.field_file_table_2677990.settings');
    $this->assertTrue(!isset($formatter_settings['use_description_as_link_text']));

    // Run updates.
    $this->runUpdates();

    // Check that field_file_generic formatter has a
    // use_description_as_link_text setting which value is TRUE.
    $formatter_settings = $this->config($view)->get('content.field_file_generic_2677990.settings');
    $this->assertEqual($formatter_settings, ['use_description_as_link_text' => TRUE]);

    // Check that field_file_table formatter has a use_description_as_link_text
    // setting which value is FALSE.
    $formatter_settings = $this->config($view)->get('content.field_file_table_2677990.settings');
    $this->assertEqual($formatter_settings, ['use_description_as_link_text' => FALSE]);
  }

  /**
   * Tests that the file entity type has an 'owner' entity key.
   *
   * @see file_update_8700()
   */
  public function testOwnerEntityKey() {
    // Check that the 'owner' entity key does not exist prior to the update.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('file');
    $this->assertFalse($entity_type->getKey('owner'));

    // Run updates.
    $this->runUpdates();

    // Check that the entity key exists and it has the correct value.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('file');
    $this->assertEqual('uid', $entity_type->getKey('owner'));
  }

}
