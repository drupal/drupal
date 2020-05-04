<?php

namespace Drupal\Tests\field\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Migrate field widget settings.
 *
 * @group migrate_drupal_6
 */
class MigrateFieldWidgetSettingsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateFields();
  }

  /**
   * Test that migrated view modes can be loaded using D8 API's.
   */
  public function testWidgetSettings() {
    // Test the config can be loaded.
    $form_display = EntityFormDisplay::load('node.story.default');
    $this->assertNotNull($form_display);

    // Text field.
    $component = $form_display->getComponent('field_test');
    $expected = ['weight' => 1, 'type' => 'text_textfield'];
    $expected['settings'] = ['size' => 60, 'placeholder' => ''];
    $expected['third_party_settings'] = [];
    $expected['region'] = 'content';
    $this->assertIdentical($expected, $component, 'Text field settings are correct.');

    // Integer field.
    $component = $form_display->getComponent('field_test_two');
    $expected['type'] = 'number';
    $expected['weight'] = 1;
    $expected['settings'] = ['placeholder' => ''];
    $this->assertIdentical($expected, $component);

    // Float field.
    $component = $form_display->getComponent('field_test_three');
    $expected['weight'] = 2;
    $this->assertIdentical($expected, $component);

    // Email field.
    $component = $form_display->getComponent('field_test_email');
    $expected['type'] = 'email_default';
    $expected['weight'] = 6;
    $expected['settings'] = ['placeholder' => '', 'size' => 60];
    $this->assertIdentical($expected, $component);

    // Link field.
    $component = $form_display->getComponent('field_test_link');
    $this->assertIdentical('link_default', $component['type']);
    $this->assertIdentical(7, $component['weight']);
    $this->assertEmpty(array_filter($component['settings']));

    // File field.
    $component = $form_display->getComponent('field_test_filefield');
    $expected['type'] = 'file_generic';
    $expected['weight'] = 8;
    $expected['settings'] = ['progress_indicator' => 'bar'];
    $this->assertIdentical($expected, $component);

    // Image field.
    $component = $form_display->getComponent('field_test_imagefield');
    $expected['type'] = 'image_image';
    $expected['weight'] = 9;
    $expected['settings'] = ['progress_indicator' => 'bar', 'preview_image_style' => 'thumbnail'];
    $this->assertIdentical($expected, $component);

    // Phone field.
    $component = $form_display->getComponent('field_test_phone');
    $expected['type'] = 'telephone_default';
    $expected['weight'] = 13;
    $expected['settings'] = ['placeholder' => ''];
    $this->assertIdentical($expected, $component);

    // Date fields.
    $component = $form_display->getComponent('field_test_date');
    $expected['type'] = 'datetime_default';
    $expected['weight'] = 10;
    $expected['settings'] = [];
    $this->assertIdentical($expected, $component);

    $component = $form_display->getComponent('field_test_datestamp');
    $expected['weight'] = 11;
    $this->assertIdentical($expected, $component);

    $component = $form_display->getComponent('field_test_datetime');
    $expected['weight'] = 12;
    $this->assertIdentical($expected, $component);

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $component = $display_repository->getFormDisplay('node', 'employee', 'default')
      ->getComponent('field_company');
    $this->assertIsArray($component);
    $this->assertSame('options_select', $component['type']);

    $component = $display_repository->getFormDisplay('node', 'employee', 'default')
      ->getComponent('field_company_2');
    $this->assertIsArray($component);
    $this->assertSame('options_buttons', $component['type']);

    $component = $display_repository->getFormDisplay('node', 'employee', 'default')
      ->getComponent('field_company_3');
    $this->assertIsArray($component);
    $this->assertSame('entity_reference_autocomplete_tags', $component['type']);

    $component = $display_repository->getFormDisplay('node', 'employee', 'default')
      ->getComponent('field_commander');
    $this->assertIsArray($component);
    $this->assertSame('options_select', $component['type']);
  }

}
