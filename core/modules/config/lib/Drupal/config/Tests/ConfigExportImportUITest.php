<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigExportImportUITest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Performs various configuration import/export scenarios through the UI.
 *
 * Each testX method does a complete rebuild of a Drupal site, so values being
 * tested need to be stored in protected properties in order to survive until
 * the next rebuild.
 */
class ConfigExportImportUITest extends WebTestBase {

  /**
   * The contents of the config export tarball, held between test methods.
   *
   * @var string
   */
  protected $tarball;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config', 'node', 'field');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Export/import UI',
      'description' => 'Tests the user interface for importing/exporting configuration.',
      'group' => 'Configuration',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // The initial import must be done with uid 1 because if separately named
    // roles are created then the role is lost after import. If the roles
    // created have the same name then the sync will fail because they will
    // have different UUIDs.
    $this->drupalLogin($this->root_user);
  }

  /**
   * Tests a simple site export import case.
   */
  public function testExportImport() {
    $this->originalSlogan = \Drupal::config('system.site')->get('slogan');
    $this->newSlogan = $this->randomString(16);
    $this->assertNotEqual($this->newSlogan, $this->originalSlogan);
    \Drupal::config('system.site')
      ->set('slogan', $this->newSlogan)
      ->save();
    $this->assertEqual(\Drupal::config('system.site')->get('slogan'), $this->newSlogan);

    // Create a content type.
    $this->content_type = $this->drupalCreateContentType();

    // Create a field.
    $this->field = entity_create('field_config', array(
      'name' => drupal_strtolower($this->randomName()),
      'entity_type' => 'node',
      'type' => 'text',
    ));
    $this->field->save();
    entity_create('field_instance_config', array(
      'field_name' => $this->field->name,
      'entity_type' => 'node',
      'bundle' => $this->content_type->type,
    ))->save();
    entity_get_form_display('node', $this->content_type->type, 'default')
      ->setComponent($this->field->name, array(
        'type' => 'text_textfield',
      ))
      ->save();
    entity_get_display('node', $this->content_type->type, 'full')
      ->setComponent($this->field->name)
      ->save();

    $this->drupalGet('node/add/' . $this->content_type->type);
    $this->assertFieldByName("{$this->field->name}[0][value]", '', 'Widget is displayed');

    // Export the configuration.
    $this->drupalPostForm('admin/config/development/configuration/full/export', array(), 'Export');
    $this->tarball = $this->drupalGetContent();

    \Drupal::config('system.site')
      ->set('slogan', $this->originalSlogan)
      ->save();
    $this->assertEqual(\Drupal::config('system.site')->get('slogan'), $this->originalSlogan);

    // Delete the custom field.
    $field_instances = entity_load_multiple('field_instance_config');
    foreach ($field_instances as $field_instance) {
      if ($field_instance->field_name == $this->field->name) {
        $field_instance->delete();
      }
    }
    $fields = entity_load_multiple('field_config');
    foreach ($fields as $field) {
      if ($field->name == $this->field->name) {
        $field->delete();
      }
    }
    $this->drupalGet('node/add/' . $this->content_type->type);
    $this->assertNoFieldByName("{$this->field->name}[0][value]", '', 'Widget is not displayed');

    // Import the configuration.
    $filename = 'temporary://' . $this->randomName();
    file_put_contents($filename, $this->tarball);
    $this->drupalPostForm('admin/config/development/configuration/full/import', array('files[import_tarball]' => $filename), 'Upload');
    $this->drupalPostForm(NULL, array(), 'Import all');

    $this->assertEqual(\Drupal::config('system.site')->get('slogan'), $this->newSlogan);

    $this->drupalGet('node/add');
    $this->assertFieldByName("{$this->field->name}[0][value]", '', 'Widget is displayed');
  }
}
