<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Functional;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the Field UI "Manage fields" screen.
 *
 * @group field_ui
 * @group #slow
 */
class ManageFieldsFunctionalTest extends ManageFieldsFunctionalTestBase {

  /**
   * Tests that default value is correctly validated and saved.
   */
  public function testDefaultValue(): void {
    // Create a test field storage and field.
    $field_name = 'test';
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'test_field',
    ])->save();
    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $this->contentType,
    ]);
    $field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('node', $this->contentType)
      ->setComponent($field_name)
      ->save();

    $admin_path = 'admin/structure/types/manage/' . $this->contentType . '/fields/' . $field->id();
    $element_id = "edit-default-value-input-$field_name-0-value";
    $element_name = "default_value_input[{$field_name}][0][value]";
    $this->drupalGet($admin_path);
    $this->assertSession()->fieldValueEquals($element_id, '');

    // Check that invalid default values are rejected.
    $edit = [$element_name => '-1', 'set_default_value' => '1'];
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save settings');
    $this->assertSession()->pageTextContains("$field_name does not accept the value -1");

    // Check that the default value is saved.
    $edit = [$element_name => '1', 'set_default_value' => '1'];
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save settings');
    $this->assertSession()->pageTextContains("Saved $field_name configuration");
    $field = FieldConfig::loadByName('node', $this->contentType, $field_name);
    $this->assertEquals([['value' => 1]], $field->getDefaultValueLiteral(), 'The default value was correctly saved.');

    // Check that the default value shows up in the form.
    $this->drupalGet($admin_path);
    $this->assertSession()->fieldValueEquals($element_id, '1');

    // Check that the default value is left empty when "Set default value"
    // checkbox is not checked.
    $edit = [$element_name => '1', 'set_default_value' => '0'];
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save settings');
    $this->assertSession()->pageTextContains("Saved $field_name configuration");
    $field = FieldConfig::loadByName('node', $this->contentType, $field_name);
    $this->assertEquals([], $field->getDefaultValueLiteral(), 'The default value was removed.');

    // Check that the default value can be emptied.
    $this->drupalGet($admin_path);
    $edit = [$element_name => ''];
    $this->submitForm($edit, 'Save settings');
    $this->assertSession()->pageTextContains("Saved $field_name configuration");
    $field = FieldConfig::loadByName('node', $this->contentType, $field_name);
    $this->assertEquals([], $field->getDefaultValueLiteral(), 'The default value was correctly saved.');

    // Check that the default value can be empty when the field is marked as
    // required and can store unlimited values.
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $field_storage->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $field_storage->save();

    $this->drupalGet($admin_path);
    $edit = [
      'required' => 1,
    ];
    $this->submitForm($edit, 'Save settings');

    $this->drupalGet($admin_path);
    $this->submitForm([], 'Save settings');
    $this->assertSession()->pageTextContains("Saved $field_name configuration");
    $field = FieldConfig::loadByName('node', $this->contentType, $field_name);
    $this->assertEquals([], $field->getDefaultValueLiteral(), 'The default value was correctly saved.');

    // Check that the default widget is used when the field is hidden.
    $display_repository->getFormDisplay($field->getTargetEntityTypeId(), $field->getTargetBundle())
      ->removeComponent($field_name)
      ->save();
    $this->drupalGet($admin_path);
    $this->assertSession()->fieldValueEquals($element_id, '');
  }

  /**
   * Tests that Field UI respects disallowed field names.
   */
  public function testDisallowedFieldNames(): void {
    // Reset the field prefix so we can test properly.
    $this->config('field_ui.settings')->set('field_prefix', '')->save();

    $label = 'Disallowed field';
    $edit1 = [
      'new_storage_type' => 'test_field',
    ];
    $edit2 = [
      'label' => $label,
    ];

    // Try with an entity key.
    $edit2['field_name'] = 'title';
    $bundle_path = 'admin/structure/types/manage/' . $this->contentType;
    $this->drupalGet("{$bundle_path}/fields/add-field");
    $this->submitForm($edit1, 'Continue');
    $this->submitForm($edit2, 'Continue');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');

    // Try with a base field.
    $edit2['field_name'] = 'sticky';
    $bundle_path = 'admin/structure/types/manage/' . $this->contentType;
    $this->drupalGet("{$bundle_path}/fields/add-field");
    $this->submitForm($edit1, 'Continue');
    $this->submitForm($edit2, 'Continue');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');
  }

  /**
   * Tests that Field UI respects locked fields.
   */
  public function testLockedField(): void {
    // Create a locked field and attach it to a bundle. We need to do this
    // programmatically as there's no way to create a locked field through UI.
    $field_name = $this->randomMachineName(8);
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'test_field',
      'cardinality' => 1,
      'locked' => TRUE,
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->contentType,
    ])->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', $this->contentType)
      ->setComponent($field_name, [
        'type' => 'test_field_widget',
      ])
      ->save();

    // Check that the links for edit and delete are not present.
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields');
    $locked = $this->xpath('//tr[@id=:field_name]/td[4]', [':field_name' => $field_name]);
    $this->assertSame('Locked', $locked[0]->getHtml(), 'Field is marked as Locked in the UI');
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/node.' . $this->contentType . '.' . $field_name . '/delete');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that Field UI respects the 'no_ui' flag in the field type definition.
   */
  public function testHiddenFields(): void {
    // Check that the field type is not available in the 'add new field' row.
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/add-field');
    $this->assertSession()->elementNotExists('css', "[name='new_storage_type'][value='hidden_test_field']");
    $this->assertSession()->elementExists('css', "[name='new_storage_type'][value='shape']");

    // Create a field storage and a field programmatically.
    $field_name = 'hidden_test_field';
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $field_name,
    ])->save();
    $field = [
      'field_name' => $field_name,
      'bundle' => $this->contentType,
      'entity_type' => 'node',
      'label' => 'Hidden field',
    ];
    FieldConfig::create($field)->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', $this->contentType)
      ->setComponent($field_name)
      ->save();
    $this->assertInstanceOf(FieldConfig::class, FieldConfig::load('node.' . $this->contentType . '.' . $field_name));

    // Check that the newly added field appears on the 'Manage Fields'
    // screen.
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields');
    $this->assertSession()->elementTextContains('xpath', '//table[@id="field-overview"]//tr[@id="hidden-test-field"]//td[1]', $field['label']);

    // Check that the field does not appear in the 're-use existing field' row
    // on other bundles.
    $this->drupalGet('admin/structure/types/manage/page/fields/reuse');
    $this->assertSession()->elementNotExists('css', ".js-reuse-table [data-field-id='{$field_name}']");
    $this->assertSession()->elementExists('css', '.js-reuse-table [data-field-id="field_tags"]');

    // Check that non-configurable fields are not available.
    $field_types = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
    $this->drupalGet('admin/structure/types/manage/page/fields/add-field');
    foreach ($field_types as $field_type => $definition) {
      if (empty($definition['no_ui'])) {
        try {
          $this->assertSession()
            ->elementExists('css', "[name='new_storage_type'][value='$field_type']");
        }
        catch (ElementNotFoundException) {
          if ($group = $this->getFieldFromGroup($field_type)) {
            $this->assertSession()
              ->elementExists('css', "[name='new_storage_type'][value='$group']");
            $this->submitForm(['new_storage_type' => $group], 'Continue');
            $this->assertSession()
              ->elementExists('css', "[name='group_field_options_wrapper'][value='$field_type']");
            $this->submitForm([], 'Back');
          }
        }
      }
      else {
        $this->assertSession()->elementNotExists('css', "[name='new_storage_type'][value='$field_type']");
      }
    }
  }

  /**
   * Tests that a duplicate field name is caught by validation.
   */
  public function testDuplicateFieldName(): void {
    // field_tags already exists, so we're expecting an error when trying to
    // create a new field with the same name.
    $url = 'admin/structure/types/manage/' . $this->contentType . '/fields/add-field';
    $this->drupalGet($url);
    $edit = [
      'new_storage_type' => 'boolean',
    ];
    $this->submitForm($edit, 'Continue');
    $edit = [
      'label' => $this->randomMachineName(),
      'field_name' => 'tags',
    ];
    $this->submitForm($edit, 'Continue');

    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');
    $this->assertSession()->addressEquals($url);
  }

  /**
   * Tests that external URLs in the 'destinations' query parameter are blocked.
   */
  public function testExternalDestinations(): void {
    $options = [
      'query' => ['destinations' => ['http://example.com']],
    ];
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.body', $options);
    $this->submitForm([], 'Save settings');
    // The external redirect should not fire.
    $this->assertSession()->addressEquals('admin/structure/types/manage/article/fields/node.article.body?destinations%5B0%5D=http%3A//example.com');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Attempt to update field <em class="placeholder">Body</em> failed: <em class="placeholder">The internal path component &#039;http://example.com&#039; is external. You are not allowed to specify an external URL together with internal:/.</em>.');
  }

  /**
   * Tests that deletion removes field storages and fields as expected for a term.
   */
  public function testDeleteTaxonomyField(): void {
    // Create a new field.
    $bundle_path = 'admin/structure/taxonomy/manage/tags/overview';

    $this->fieldUIAddNewField($bundle_path, $this->fieldNameInput, $this->fieldLabel);

    // Delete the field.
    $this->fieldUIDeleteField($bundle_path, "taxonomy_term.tags.$this->fieldName", $this->fieldLabel, 'Tags', 'taxonomy vocabulary');

    // Check that the field was deleted.
    $this->assertNull(FieldConfig::loadByName('taxonomy_term', 'tags', $this->fieldName), 'Field was deleted.');
    // Check that the field storage was deleted too.
    $this->assertNull(FieldStorageConfig::loadByName('taxonomy_term', $this->fieldName), 'Field storage was deleted.');
  }

  /**
   * Tests that help descriptions render valid HTML.
   */
  public function testHelpDescriptions(): void {
    // Create an image field.
    FieldStorageConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'type' => 'image',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'label' => 'Image',
      'bundle' => 'article',
    ])->save();

    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'article')
      ->setComponent('field_image')
      ->save();

    $edit = [
      'description' => '<strong>Test with an upload field.',
    ];
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_image');
    $this->submitForm($edit, 'Save settings');

    // Check that hook_field_widget_single_element_form_alter() does believe
    // this is the default value form.
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_tags');
    $this->assertSession()->pageTextContains('From hook_field_widget_single_element_form_alter(): Default form is true.');

    $edit = [
      'description' => '<em>Test with a non upload field.',
    ];
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_tags');
    $this->submitForm($edit, 'Save settings');

    $this->drupalGet('node/add/article');
    $this->assertSession()->responseContains('<strong>Test with an upload field.</strong>');
    $this->assertSession()->responseContains('<em>Test with a non upload field.</em>');
  }

  /**
   * Tests the "preconfigured field" functionality.
   *
   * @see \Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface
   */
  public function testPreconfiguredFields(): void {
    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');

    // Check that the preconfigured field option exist alongside the regular
    // field type option.
    $this->assertSession()->elementExists('css', "[name='new_storage_type'][value='field_ui:test_field_with_preconfigured_options:custom_options']");
    $this->assertSession()->elementExists('css', "[name='new_storage_type'][value='test_field_with_preconfigured_options']");

    // Add a field with every possible preconfigured value.
    $this->fieldUIAddNewField(NULL, 'test_custom_options', 'Test label', 'field_ui:test_field_with_preconfigured_options:custom_options');
    $field_storage = FieldStorageConfig::loadByName('node', 'field_test_custom_options');
    $this->assertEquals(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED, $field_storage->getCardinality());
    $this->assertEquals('preconfigured_storage_setting', $field_storage->getSetting('test_field_storage_setting'));

    $field = FieldConfig::loadByName('node', 'article', 'field_test_custom_options');
    $this->assertTrue($field->isRequired());
    $this->assertEquals('preconfigured_field_setting', $field->getSetting('test_field_setting'));

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $form_display = $display_repository->getFormDisplay('node', 'article');
    $this->assertEquals('test_field_widget_multiple', $form_display->getComponent('field_test_custom_options')['type']);
    $view_display = $display_repository->getViewDisplay('node', 'article');
    $this->assertEquals('field_test_multiple', $view_display->getComponent('field_test_custom_options')['type']);
    $this->assertEquals('altered dummy test string', $view_display->getComponent('field_test_custom_options')['settings']['test_formatter_setting_multiple']);
  }

  /**
   * Tests the access to non-existent field URLs.
   */
  public function testNonExistentFieldUrls(): void {
    $field_id = 'node.foo.bar';

    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/' . $field_id);
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests that the 'field_prefix' setting works on Field UI.
   */
  public function testFieldPrefix(): void {
    // Change default field prefix.
    $field_prefix = $this->randomMachineName(10);
    $this->config('field_ui.settings')->set('field_prefix', $field_prefix)->save();

    // Create a field input and label exceeding the new maxlength, which is 22.
    $field_exceed_max_length_label = $this->randomString(23);
    $field_exceed_max_length_input = $this->randomMachineName(23);

    // Try to create the field.
    $edit1 = [
      'new_storage_type' => 'test_field',
    ];
    $edit2 = [
      'label' => $field_exceed_max_length_label,
      'field_name' => $field_exceed_max_length_input,
    ];
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/add-field');
    $this->submitForm($edit1, 'Continue');
    $this->submitForm($edit2, 'Continue');
    $this->assertSession()->pageTextContains('Machine-readable name cannot be longer than 22 characters but is currently 23 characters long.');

    // Create a valid field.
    $this->fieldUIAddNewField('admin/structure/types/manage/' . $this->contentType, $this->fieldNameInput, $this->fieldLabel);
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/node.' . $this->contentType . '.' . $field_prefix . $this->fieldNameInput);
    $this->assertSession()->pageTextContains($this->fieldLabel . ' settings for ' . $this->contentType);
  }

  /**
   * Test translation defaults.
   */
  public function testTranslationDefaults(): void {
    $this->fieldUIAddNewField('admin/structure/types/manage/' . $this->contentType, $this->fieldNameInput, $this->fieldLabel);
    $field_storage = FieldStorageConfig::loadByName('node', 'field_' . $this->fieldNameInput);
    $this->assertTrue($field_storage->isTranslatable(), 'Field storage translatable.');

    $field = FieldConfig::loadByName('node', $this->contentType, 'field_' . $this->fieldNameInput);
    $this->assertFalse($field->isTranslatable(), 'Field instance should not be translatable by default.');

    // Add a new field based on an existing field.
    $this->drupalCreateContentType(['type' => 'additional', 'name' => 'Additional type']);
    $this->fieldUIAddExistingField("admin/structure/types/manage/additional", $this->fieldName, 'Additional type');

    $field_storage = FieldStorageConfig::loadByName('node', 'field_' . $this->fieldNameInput);
    $this->assertTrue($field_storage->isTranslatable(), 'Field storage translatable.');

    $field = FieldConfig::loadByName('node', 'additional', 'field_' . $this->fieldNameInput);
    $this->assertFalse($field->isTranslatable(), 'Field instance should not be translatable by default.');
  }

}
