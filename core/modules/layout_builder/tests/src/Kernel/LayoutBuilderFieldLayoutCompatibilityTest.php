<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;

/**
 * Ensures that Layout Builder and Field Layout are compatible with each other.
 *
 * @group layout_builder
 */
class LayoutBuilderFieldLayoutCompatibilityTest extends LayoutBuilderCompatibilityTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_layout',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->display
      ->setLayoutId('layout_twocol')
      ->save();
  }

  /**
   * Tests the compatibility of Layout Builder and Field Layout.
   */
  public function testCompatibility() {
    // Ensure that the configurable field is shown in the correct region and
    // that the non-configurable field is shown outside the layout.
    $expected_fields = [
      'field field--name-name field--type-string field--label-hidden field__item',
      'field field--name-test-field-display-configurable field--type-boolean field--label-above',
      'clearfix text-formatted field field--name-test-display-configurable field--type-text field--label-above',
      'clearfix text-formatted field field--name-test-display-non-configurable field--type-text field--label-above',
      'clearfix text-formatted field field--name-test-display-multiple field--type-text field--label-above',
    ];
    $this->assertFieldAttributes($this->entity, $expected_fields);
    $this->assertNotEmpty($this->cssSelect('.layout__region--first .field--name-test-display-configurable'));
    $this->assertNotEmpty($this->cssSelect('.layout__region--first .field--name-test-field-display-configurable'));
    $this->assertNotEmpty($this->cssSelect('.field--name-test-display-non-configurable'));
    $this->assertEmpty($this->cssSelect('.layout__region .field--name-test-display-non-configurable'));

    $this->installLayoutBuilder();

    // Without using Layout Builder for an override, the result has not changed.
    $this->assertFieldAttributes($this->entity, $expected_fields);

    // Add a layout override.
    $this->enableOverrides();
    /** @var \Drupal\layout_builder\SectionStorageInterface $field_list */
    $field_list = $this->entity->get(OverridesSectionStorage::FIELD_NAME);
    $field_list->appendSection(new Section('layout_onecol'));
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
    $field_list->removeAllSections();
    $this->entity->save();
    $this->assertFieldAttributes($this->entity, $expected_fields);
  }

}
