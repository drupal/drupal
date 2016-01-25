<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\DisplayEntityReferenceTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Views;

/**
 * Tests the entity reference display plugin.
 *
 * @group views
 *
 * @see \Drupal\views\Plugin\views\display\EntityReference
 */
class DisplayEntityReferenceTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_display_entity_reference');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'field', 'views_ui');

  /**
   * The used field name in the test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The field storage.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field config.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser(array('administer views')));

    // Create the text field.
    $this->fieldName = 'field_test_entity_ref_display';
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'text',
    ]);
    $this->fieldStorage->save();

    // Create an instance of the text field on the content type.
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
    ]);
    $this->field->save();

    // Create some entities to search. Add a common string to the name and
    // the text field in two entities so we can test that we can search in both.
    for ($i = 0; $i < 5; $i++) {
      EntityTest::create([
        'bundle' => 'entity_test',
        'name' => 'name' . $i,
        $this->fieldName => 'text',
      ])->save();
      EntityTest::create([
        'bundle' => 'entity_test',
        'name' => 'name',
        $this->fieldName => 'text' . $i,
      ])->save();
    }
  }

  /**
   * Tests the entity reference display plugin.
   */
  public function testEntityReferenceDisplay() {
    // Add the new field to the fields.
    $this->drupalPostForm('admin/structure/views/nojs/add-handler/test_display_entity_reference/default/field', ['name[entity_test__' . $this->fieldName . '.' . $this->fieldName . ']' => TRUE], t('Add and configure fields'));
    $this->drupalPostForm(NULL, [], t('Apply'));

    // Test that the right fields are shown on the display settings form.
    $this->drupalGet('admin/structure/views/nojs/display/test_display_entity_reference/entity_reference_1/style_options');
    $this->assertText('Test entity: Name');
    $this->assertText('Test entity: ' . $this->field->label());

    // Add the new field to the search fields.
    $this->drupalPostForm(NULL, ['style_options[search_fields][' . $this->fieldName . ']' => $this->fieldName], t('Apply'));
    $this->drupalPostForm(NULL, [], t('Save'));

    $view = Views::getView('test_display_entity_reference');
    $view->setDisplay('entity_reference_1');

    // Add the required settings to test a search operation.
    $options = [
      'match' => '1',
      'match_operator' => 'CONTAINS',
      'limit' => 0,
      'ids' => NULL,
    ];
    $view->display_handler->setOption('entity_reference_options', $options);

    $this->executeView($view);

    // Test that we have searched in both fields.
    $this->assertEqual(count($view->result), 2, 'Search returned two rows');
  }

}
