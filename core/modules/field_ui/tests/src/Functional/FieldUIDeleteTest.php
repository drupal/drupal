<?php

namespace Drupal\Tests\field_ui\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\views\Entity\View;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests deletion of a field and their dependencies in the UI.
 *
 * @group field_ui
 */
class FieldUIDeleteTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field_ui',
    'field_test',
    'block',
    'field_test_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test views to enable.
   *
   * @var string[]
   */
  public static $testViews = ['test_view_field_delete'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_actions_block');

    // Create a test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'administer users',
      'administer account settings',
      'administer user display',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that deletion removes field storages and fields as expected.
   */
  public function testDeleteField() {
    $field_label = $this->randomMachineName();
    $field_name_input = 'test';
    $field_name = 'field_test';

    // Create an additional node type.
    $type_name1 = $this->randomMachineName(8) . '_test';
    $type1 = $this->drupalCreateContentType(['name' => $type_name1, 'type' => $type_name1]);
    $type_name1 = $type1->id();

    // Create a new field.
    $bundle_path1 = 'admin/structure/types/manage/' . $type_name1;
    $this->fieldUIAddNewField($bundle_path1, $field_name_input, $field_label);

    // Create an additional node type.
    $type_name2 = $this->randomMachineName(8) . '_test';
    $type2 = $this->drupalCreateContentType(['name' => $type_name2, 'type' => $type_name2]);
    $type_name2 = $type2->id();

    // Add a field to the second node type.
    $bundle_path2 = 'admin/structure/types/manage/' . $type_name2;
    $this->fieldUIAddExistingField($bundle_path2, $field_name, $field_label);

    \Drupal::service('module_installer')->install(['views']);
    ViewTestData::createTestViews(static::class, ['field_test_views']);

    $view = View::load('test_view_field_delete');
    $this->assertNotNull($view);
    $this->assertTrue($view->status());
    // Test that the View depends on the field.
    $dependencies = $view->getDependencies() + ['config' => []];
    $this->assertContains("field.storage.node.$field_name", $dependencies['config']);

    // Check the config dependencies of the first field, the field storage must
    // not be shown as being deleted yet.
    $this->drupalGet("$bundle_path1/fields/node.$type_name1.$field_name/delete");
    $this->assertSession()->pageTextNotContains('The listed configuration will be deleted.');
    $this->assertSession()->elementNotExists('xpath', '//ul[@data-drupal-selector="edit-view"]');
    $this->assertSession()->pageTextNotContains('test_view_field_delete');

    // Delete the first field.
    $this->fieldUIDeleteField($bundle_path1, "node.$type_name1.$field_name", $field_label, $type_name1, 'content type');

    // Check that the field was deleted.
    $this->assertNull(FieldConfig::loadByName('node', $type_name1, $field_name), 'Field was deleted.');
    // Check that the field storage was not deleted.
    $this->assertNotNull(FieldStorageConfig::loadByName('node', $field_name), 'Field storage was not deleted.');

    // Check the config dependencies of the first field.
    $this->drupalGet("$bundle_path2/fields/node.$type_name2.$field_name/delete");
    $this->assertSession()->pageTextContains('The listed configuration will be updated.');
    $this->assertSession()->elementTextEquals('xpath', '//ul[@data-drupal-selector="edit-view"]', 'test_view_field_delete');

    // Test that nothing is scheduled for deletion.
    $this->assertSession()->elementNotExists('css', '#edit-entity-deletes');

    // Delete the second field.
    $this->fieldUIDeleteField($bundle_path2, "node.$type_name2.$field_name", $field_label, $type_name2, 'content type');

    // Check that the field was deleted.
    $this->assertNull(FieldConfig::loadByName('node', $type_name2, $field_name), 'Field was deleted.');
    // Check that the field storage was deleted too.
    $this->assertNull(FieldStorageConfig::loadByName('node', $field_name), 'Field storage was deleted.');

    // Test that the View isn't deleted and has been disabled.
    $view = View::load('test_view_field_delete');
    $this->assertNotNull($view);
    $this->assertFalse($view->status());
    // Test that the View no longer depends on the deleted field.
    $dependencies = $view->getDependencies() + ['config' => []];
    $this->assertNotContains("field.storage.node.$field_name", $dependencies['config']);
  }

}
