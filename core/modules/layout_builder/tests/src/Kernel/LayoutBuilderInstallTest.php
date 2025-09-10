<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;

/**
 * Ensures that Layout Builder and core EntityViewDisplays are compatible.
 *
 * @group layout_builder
 */
class LayoutBuilderInstallTest extends LayoutBuilderCompatibilityTestBase {

  /**
   * Tests the compatibility of Layout Builder with existing entity displays.
   */
  public function testCompatibility(): void {
    // Ensure that the fields are shown.
    $expected_fields = [
      'field field--name-name field--type-string field--label-hidden field__item',
      'field field--name-test-field-display-configurable field--type-boolean field--label-above',
      'clearfix text-formatted field field--name-test-display-configurable field--type-text field--label-above',
      'clearfix text-formatted field field--name-test-display-non-configurable field--type-text field--label-above',
      'clearfix text-formatted field field--name-test-display-multiple field--type-text field--label-above',
    ];
    $this->assertFieldAttributes($this->entity, $expected_fields);

    $this->installLayoutBuilder();

    // Without using Layout Builder for an override, the result has not changed.
    $this->assertFieldAttributes($this->entity, $expected_fields);

    // Add a layout override.
    $this->enableOverrides();
    $this->entity = $this->reloadEntity($this->entity);
    $this->entity->get(OverridesSectionStorage::FIELD_NAME)->appendSection(new Section('layout_onecol'));
    $this->entity->save();

    // The rendered entity has now changed. The non-configurable field is shown
    // outside the layout, the configurable field is not shown at all, and the
    // layout itself is rendered (but empty).
    $new_expected_fields = [
      'field field--name-name field--type-string field--label-hidden field__item',
      'clearfix text-formatted field field--name-test-display-non-configurable field--type-text field--label-above',
      'clearfix text-formatted field field--name-test-display-multiple field--type-text field--label-above',
    ];
    $this->assertFieldAttributes($this->entity, $new_expected_fields);
    $this->assertNotEmpty($this->cssSelect('.layout--onecol'));

    // Removing the layout restores the original rendering of the entity.
    $this->entity->get(OverridesSectionStorage::FIELD_NAME)->removeAllSections();
    $this->entity->save();
    $this->assertFieldAttributes($this->entity, $expected_fields);

    // Test that adding a new field after Layout Builder has been installed will
    // add the new field to the default region of the first section.
    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test_base_field_display',
      'field_name' => 'test_field_display_post_install',
      'type' => 'text',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test_base_field_display',
      'label' => 'FieldConfig with configurable display',
    ])->save();

    $this->entity = $this->reloadEntity($this->entity);
    $this->entity->test_field_display_post_install = 'Test string';
    $this->entity->save();

    $this->display = $this->reloadEntity($this->display);
    $this->display
      ->setComponent('test_field_display_post_install', ['weight' => 50])
      ->save();
    $new_expected_fields = [
      'field field--name-name field--type-string field--label-hidden field__item',
      'field field--name-test-field-display-configurable field--type-boolean field--label-above',
      'clearfix text-formatted field field--name-test-display-configurable field--type-text field--label-above',
      'clearfix text-formatted field field--name-test-field-display-post-install field--type-text field--label-above',
      'clearfix text-formatted field field--name-test-display-non-configurable field--type-text field--label-above',
      'clearfix text-formatted field field--name-test-display-multiple field--type-text field--label-above',
    ];
    $this->assertFieldAttributes($this->entity, $new_expected_fields);
    $this->assertNotEmpty($this->cssSelect('.layout--onecol'));
    $this->assertText('Test string');
  }

}
