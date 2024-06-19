<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\field\Entity\FieldConfig;

/**
 * Tests the Field UI "Manage fields" screen.
 *
 * @group field_ui
 * @group #slow
 */
class ManageFieldsMultipleTypesTest extends ManageFieldsFunctionalTestBase {

  /**
   * Tests that options are copied over when reusing a field.
   *
   * @dataProvider entityTypesProvider
   */
  public function testReuseField($entity_type, $bundle1, $bundle2): void {
    $field_name = 'test_reuse';
    $label = $this->randomMachineName();

    // Create field with pre-configured options.
    $this->drupalGet($bundle1['path'] . "/fields/add-field");
    $this->fieldUIAddNewField(NULL, $field_name, $label, 'field_ui:test_field_with_preconfigured_options:custom_options');
    $new_label = $this->randomMachineName();
    $this->fieldUIAddExistingField($bundle2['path'], "field_{$field_name}", $new_label);
    $field = FieldConfig::loadByName($entity_type, $bundle2['id'], "field_{$field_name}");
    $this->assertTrue($field->isRequired());
    $this->assertEquals($new_label, $field->label());
    $this->assertEquals('preconfigured_field_setting', $field->getSetting('test_field_setting'));

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $form_display = $display_repository->getFormDisplay($entity_type, $bundle2['id']);
    $this->assertEquals('test_field_widget_multiple', $form_display->getComponent("field_{$field_name}")['type']);
    $view_display = $display_repository->getViewDisplay($entity_type, $bundle2['id']);
    $this->assertEquals('field_test_multiple', $view_display->getComponent("field_{$field_name}")['type']);
    $this->assertEquals('altered dummy test string', $view_display->getComponent("field_{$field_name}")['settings']['test_formatter_setting_multiple']);
  }

  /**
   * Tests that options are copied over when reusing a field.
   *
   * @dataProvider entityTypesProvider
   */
  public function testReuseFieldMultipleDisplay($entity_type, $bundle1, $bundle2): void {
    // Create additional form mode and enable it on both bundles.
    EntityFormMode::create([
      'id' => "{$entity_type}.little",
      'label' => 'Little Form',
      'targetEntityType' => $entity_type,
    ])->save();
    $form_display = EntityFormDisplay::create([
      'id' => "{$entity_type}.{$bundle1['id']}.little",
      'targetEntityType' => $entity_type,
      'status' => TRUE,
      'bundle' => $bundle1['id'],
      'mode' => 'little',
    ]);
    $form_display->save();
    EntityFormDisplay::create([
      'id' => "{$entity_type}.{$bundle2['id']}.little",
      'targetEntityType' => $entity_type,
      'status' => TRUE,
      'bundle' => $bundle2['id'],
      'mode' => 'little',
    ])->save();

    // Create additional view mode and enable it on both bundles.
    EntityViewMode::create([
      'id' => "{$entity_type}.little",
      'targetEntityType' => $entity_type,
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'Little View Mode',
    ])->save();
    $view_display = EntityViewDisplay::create([
      'id' => "{$entity_type}.{$bundle1['id']}.little",
      'targetEntityType' => $entity_type,
      'status' => TRUE,
      'bundle' => $bundle1['id'],
      'mode' => 'little',
    ]);
    $view_display->save();
    EntityViewDisplay::create([
      'id' => "{$entity_type}.{$bundle2['id']}.little",
      'targetEntityType' => $entity_type,
      'status' => TRUE,
      'bundle' => $bundle2['id'],
      'mode' => 'little',
    ])->save();

    $field_name = 'test_reuse';
    $label = $this->randomMachineName();

    // Create field with pre-configured options.
    $this->drupalGet($bundle1['path'] . "/fields/add-field");
    $this->fieldUIAddNewField(NULL, $field_name, $label, 'field_ui:test_field_with_preconfigured_options:custom_options');
    $view_display->setComponent("field_{$field_name}", [
      'type' => 'field_test_default',
      'region' => 'content',
    ])->save();
    $form_display->setComponent("field_{$field_name}", [
      'type' => 'test_field_widget',
      'region' => 'content',
    ])->save();

    $new_label = $this->randomMachineName();
    $this->fieldUIAddExistingField($bundle2['path'], "field_{$field_name}", $new_label);

    $field = FieldConfig::loadByName($entity_type, $bundle2['id'], "field_{$field_name}");
    $this->assertTrue($field->isRequired());
    $this->assertEquals($new_label, $field->label());
    $this->assertEquals('preconfigured_field_setting', $field->getSetting('test_field_setting'));

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Ensure that the additional form display has correct settings.
    $form_display = $display_repository->getFormDisplay($entity_type, $bundle2['id'], $form_display->getMode());
    $this->assertEquals('test_field_widget', $form_display->getComponent("field_{$field_name}")['type']);

    // Ensure that the additional view display has correct settings.
    $view_display = $display_repository->getViewDisplay($entity_type, $bundle2['id'], $view_display->getMode());
    $this->assertEquals('field_test_default', $view_display->getComponent("field_{$field_name}")['type']);
  }

  /**
   * Data provider for testing Field UI with multiple entity types.
   *
   * @return array
   *   Test cases.
   */
  public static function entityTypesProvider() {
    return [
      'node' => [
        'entity_type' => 'node',
        'bundle1' => [
          'id' => 'article',
          'path' => 'admin/structure/types/manage/article',
        ],
        'bundle2' => [
          'id' => 'page',
          'path' => 'admin/structure/types/manage/page',
        ],
      ],
      'taxonomy' => [
        'entity_type' => 'taxonomy_term',
        'bundle1' => [
          'id' => 'tags',
          'path' => 'admin/structure/taxonomy/manage/tags/overview',
        ],
        'bundle2' => [
          'id' => 'kittens',
          'path' => 'admin/structure/taxonomy/manage/kittens/overview',
        ],
      ],
    ];
  }

}
