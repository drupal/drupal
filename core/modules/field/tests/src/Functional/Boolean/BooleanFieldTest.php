<?php

namespace Drupal\Tests\field\Functional\Boolean;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests boolean field functionality.
 *
 * @group field
 */
class BooleanFieldTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'entity_test',
    'field_ui',
    'options',
    'field_test_boolean_access_denied',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * A field to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
      'administer entity_test form display',
      'administer entity_test fields',
    ]));
  }

  /**
   * Tests boolean field.
   */
  public function testBooleanField() {
    $on = $this->randomMachineName();
    $off = $this->randomMachineName();
    $label = $this->randomMachineName();

    // Create a field with settings to validate.
    $field_name = mb_strtolower($this->randomMachineName());
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'boolean',
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => $label,
      'required' => TRUE,
      'settings' => [
        'on_label' => $on,
        'off_label' => $off,
      ],
    ]);
    $this->field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Create a form display for the default form mode.
    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, [
        'type' => 'boolean_checkbox',
      ])
      ->save();
    // Create a display for the full view mode.
    $display_repository->getViewDisplay('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, [
        'type' => 'boolean',
      ])
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[value]", '');
    $this->assertText($this->field->label(), 'Uses field label by default.');
    $this->assertNoRaw($on);

    // Submit and ensure it is accepted.
    $edit = [
      "{$field_name}[value]" => 1,
    ];
    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText('entity_test ' . $id . ' has been created.');

    // Verify that boolean value is displayed.
    $entity = EntityTest::load($id);
    $this->drupalGet($entity->toUrl());
    $this->assertRaw('<div class="field__item">' . $on . '</div>');

    // Test with "On" label option.
    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
      ])
      ->save();

    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[value]", '');
    $this->assertRaw($on);
    $this->assertNoText($this->field->label());

    // Test if we can change the on label.
    $on = $this->randomMachineName();
    $edit = [
      'settings[on_label]' => $on,
    ];
    $this->drupalPostForm('entity_test/structure/entity_test/fields/entity_test.entity_test.' . $field_name, $edit, 'Save settings');
    // Check if we see the updated labels in the creation form.
    $this->drupalGet('entity_test/add');
    $this->assertRaw($on);

    // Go to the form display page and check if the default settings works as
    // expected.
    $fieldEditUrl = 'entity_test/structure/entity_test/form-display';
    $this->drupalGet($fieldEditUrl);

    // Click on the widget settings button to open the widget settings form.
    $this->submitForm([], $field_name . "_settings_edit");

    $this->assertText(
      'Use field label instead of the "On" label as the label.',
      t('Display setting checkbox available.')
    );

    // Enable setting.
    $edit = ['fields[' . $field_name . '][settings_edit_form][settings][display_label]' => 1];
    $this->submitForm($edit, $field_name . "_plugin_settings_update");
    $this->submitForm([], 'Save');

    // Go again to the form display page and check if the setting
    // is stored and has the expected effect.
    $this->drupalGet($fieldEditUrl);
    $this->assertText('Use field label: Yes', 'Checking the display settings checkbox updated the value.');

    $this->submitForm([], $field_name . "_settings_edit");
    $this->assertText(
      'Use field label instead of the "On" label as the label.',
      t('Display setting checkbox is available')
    );
    $this->getSession()->getPage()->hasCheckedField('fields[' . $field_name . '][settings_edit_form][settings][display_label]');

    // Test the boolean field settings.
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.' . $field_name);
    $this->assertSession()->fieldValueEquals('edit-settings-on-label', $on);
    $this->assertSession()->fieldValueEquals('edit-settings-off-label', $off);
  }

  /**
   * Test field access.
   */
  public function testFormAccess() {
    $on = 'boolean_on';
    $off = 'boolean_off';
    $label = 'boolean_label';
    $field_name = 'boolean_name';
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'boolean',
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => $label,
      'settings' => [
        'on_label' => $on,
        'off_label' => $off,
      ],
    ]);
    $this->field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Create a form display for the default form mode.
    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, [
        'type' => 'boolean_checkbox',
      ])
      ->save();

    // Create a display for the full view mode.
    $display_repository->getViewDisplay('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, [
        'type' => 'boolean',
      ])
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldExists("{$field_name}[value]");

    // Should be posted OK.
    $this->submitForm([], 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText('entity_test ' . $id . ' has been created.');

    // Tell the test module to disable access to the field.
    \Drupal::state()->set('field.test_boolean_field_access_field', $field_name);
    $this->drupalGet('entity_test/add');
    // Field should not be there anymore.
    $this->assertSession()->fieldNotExists("{$field_name}[value]");
    // Should still be able to post the form.
    $this->submitForm([], 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText('entity_test ' . $id . ' has been created.');
  }

}
