<?php

namespace Drupal\Tests\options\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Functional\FieldTestBase;

/**
 * Tests the Options widgets.
 *
 * @group options
 */
class OptionsWidgetsTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'options',
    'entity_test',
    'options_test',
    'taxonomy',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A field storage with cardinality 1 to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $card1;

  /**
   * A field storage with cardinality 2 to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $card2;

  /**
   * A field storage with float values to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $float;

  protected function setUp(): void {
    parent::setUp();

    // Field storage with cardinality 1.
    $this->card1 = FieldStorageConfig::create([
      'field_name' => 'card_1',
      'entity_type' => 'entity_test',
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => [
        'allowed_values' => [
          // Make sure that 0 works as an option.
          0 => 'Zero',
          1 => 'One',
          // Make sure that option text is properly sanitized.
          2 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>',
          // Make sure that HTML entities in option text are not double-encoded.
          3 => 'Some HTML encoded markup with &lt; &amp; &gt;',
        ],
      ],
    ]);
    $this->card1->save();

    // Field storage with cardinality 2.
    $this->card2 = FieldStorageConfig::create([
      'field_name' => 'card_2',
      'entity_type' => 'entity_test',
      'type' => 'list_integer',
      'cardinality' => 2,
      'settings' => [
        'allowed_values' => [
          // Make sure that 0 works as an option.
          0 => 'Zero',
          1 => 'One',
          // Make sure that option text is properly sanitized.
          2 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>',
        ],
      ],
    ]);
    $this->card2->save();

    // Field storage with list of float values.
    $this->float = FieldStorageConfig::create([
      'field_name' => 'float',
      'entity_type' => 'entity_test',
      'type' => 'list_float',
      'cardinality' => 1,
      'settings' => [
        'allowed_values' => [
          '0.0' => '0.0',
          '1.5' => '1.5',
          '2.0' => '2.0',
        ],
      ],
    ]);
    $this->float->save();

    // Create a web user.
    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
    ]));
  }

  /**
   * Tests the 'options_buttons' widget (single select).
   */
  public function testRadioButtons() {
    // Create an instance of the 'single value' field.
    $field = FieldConfig::create([
      'field_storage' => $this->card1,
      'bundle' => 'entity_test',
    ]);
    $field->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($this->card1->getName(), [
        'type' => 'options_buttons',
      ])
      ->save();

    // Create an entity.
    $entity = EntityTest::create([
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ]);
    $entity->save();
    $entity_init = clone $entity;

    // With no field data, no buttons are checked.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession()->checkboxNotChecked('edit-card-1-0');
    $this->assertSession()->checkboxNotChecked('edit-card-1-1');
    $this->assertSession()->checkboxNotChecked('edit-card-1-2');
    $this->assertRaw('Some dangerous &amp; unescaped <strong>markup</strong>');
    $this->assertRaw('Some HTML encoded markup with &lt; &amp; &gt;');

    // Select first option.
    $edit = ['card_1' => 0];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_1', [0]);

    // Check that the selected button is checked.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession()->checkboxChecked('edit-card-1-0');
    $this->assertSession()->checkboxNotChecked('edit-card-1-1');
    $this->assertSession()->checkboxNotChecked('edit-card-1-2');

    // Unselect option.
    $edit = ['card_1' => '_none'];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_1', []);

    // Check that required radios with one option is auto-selected.
    $this->card1->setSetting('allowed_values', [99 => 'Only allowed value']);
    $this->card1->save();
    $field->setRequired(TRUE);
    $field->save();
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession()->checkboxChecked('edit-card-1-99');
  }

  /**
   * Tests the 'options_buttons' widget (multiple select).
   */
  public function testCheckBoxes() {
    // Create an instance of the 'multiple values' field.
    $field = FieldConfig::create([
      'field_storage' => $this->card2,
      'bundle' => 'entity_test',
    ]);
    $field->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($this->card2->getName(), [
        'type' => 'options_buttons',
      ])
      ->save();

    // Create an entity.
    $entity = EntityTest::create([
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ]);
    $entity->save();
    $entity_init = clone $entity;

    // Display form: with no field data, nothing is checked.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession()->checkboxNotChecked('edit-card-2-0');
    $this->assertSession()->checkboxNotChecked('edit-card-2-1');
    $this->assertSession()->checkboxNotChecked('edit-card-2-2');
    $this->assertRaw('Some dangerous &amp; unescaped <strong>markup</strong>');

    // Submit form: select first and third options.
    $edit = [
      'card_2[0]' => TRUE,
      'card_2[1]' => FALSE,
      'card_2[2]' => TRUE,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_2', [0, 2]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession()->checkboxChecked('edit-card-2-0');
    $this->assertSession()->checkboxNotChecked('edit-card-2-1');
    $this->assertSession()->checkboxChecked('edit-card-2-2');

    // Submit form: select only first option.
    $edit = [
      'card_2[0]' => TRUE,
      'card_2[1]' => FALSE,
      'card_2[2]' => FALSE,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_2', [0]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession()->checkboxChecked('edit-card-2-0');
    $this->assertSession()->checkboxNotChecked('edit-card-2-1');
    $this->assertSession()->checkboxNotChecked('edit-card-2-2');

    // Submit form: select the three options while the field accepts only 2.
    $edit = [
      'card_2[0]' => TRUE,
      'card_2[1]' => TRUE,
      'card_2[2]' => TRUE,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertText('this field cannot hold more than 2 values');

    // Submit form: uncheck all options.
    $edit = [
      'card_2[0]' => FALSE,
      'card_2[1]' => FALSE,
      'card_2[2]' => FALSE,
    ];
    $this->submitForm($edit, 'Save');
    // Check that the value was saved.
    $this->assertFieldValues($entity_init, 'card_2', []);

    // Required checkbox with one option is auto-selected.
    $this->card2->setSetting('allowed_values', [99 => 'Only allowed value']);
    $this->card2->save();
    $field->setRequired(TRUE);
    $field->save();
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession()->checkboxChecked('edit-card-2-99');
  }

  /**
   * Tests the 'options_select' widget (single select).
   */
  public function testSelectListSingle() {
    // Create an instance of the 'single value' field.
    $field = FieldConfig::create([
      'field_storage' => $this->card1,
      'bundle' => 'entity_test',
      'required' => TRUE,
    ]);
    $field->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($this->card1->getName(), [
        'type' => 'options_select',
      ])
      ->save();

    // Create an entity.
    $entity = EntityTest::create([
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ]);
    $entity->save();
    $entity_init = clone $entity;

    // Display form.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    // A required field without any value has a "none" option.
    $this->assertNotEmpty($this->xpath('//select[@id=:id]//option[@value="_none" and text()=:label]', [':id' => 'edit-card-1', ':label' => '- Select a value -']), 'A required select list has a "Select a value" choice.');

    // With no field data, nothing is selected.
    $this->assertTrue($this->assertSession()->optionExists('card_1', '_none')->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_1', 0)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_1', 1)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_1', 2)->isSelected());
    $this->assertRaw('Some dangerous &amp; unescaped markup');

    // Submit form: select invalid 'none' option.
    $edit = ['card_1' => '_none'];
    $this->submitForm($edit, 'Save');
    $this->assertRaw(t('@title field is required.', ['@title' => $field->getName()]));

    // Submit form: select first option.
    $edit = ['card_1' => 0];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_1', [0]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    // A required field with a value has no 'none' option.
    $this->assertEmpty($this->xpath('//select[@id=:id]//option[@value="_none"]', [':id' => 'edit-card-1']), 'A required select list with an actual value has no "none" choice.');
    $this->assertTrue($this->assertSession()->optionExists('card_1', 0)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_1', 1)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_1', 2)->isSelected());

    // Make the field non required.
    $field->setRequired(FALSE);
    $field->save();

    // Display form.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    // A non-required field has a 'none' option.
    $this->assertNotEmpty($this->xpath('//select[@id=:id]//option[@value="_none" and text()=:label]', [':id' => 'edit-card-1', ':label' => '- None -']), 'A non-required select list has a "None" choice.');
    // Submit form: Unselect the option.
    $edit = ['card_1' => '_none'];
    $this->drupalPostForm('entity_test/manage/' . $entity->id() . '/edit', $edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_1', []);

    // Test optgroups.

    $this->card1->setSetting('allowed_values', []);
    $this->card1->setSetting('allowed_values_function', 'options_test_allowed_values_callback');
    $this->card1->save();

    // Display form: with no field data, nothing is selected
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertFalse($this->assertSession()->optionExists('card_1', 0)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_1', 1)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_1', 2)->isSelected());
    $this->assertRaw('Some dangerous &amp; unescaped markup');
    $this->assertRaw('More &lt;script&gt;dangerous&lt;/script&gt; markup');
    $this->assertRaw('Group 1');

    // Submit form: select first option.
    $edit = ['card_1' => 0];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_1', [0]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertTrue($this->assertSession()->optionExists('card_1', 0)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_1', 1)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_1', 2)->isSelected());

    // Submit form: Unselect the option.
    $edit = ['card_1' => '_none'];
    $this->drupalPostForm('entity_test/manage/' . $entity->id() . '/edit', $edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_1', []);
  }

  /**
   * Tests the 'options_select' widget (multiple select).
   */
  public function testSelectListMultiple() {
    // Create an instance of the 'multiple values' field.
    $field = FieldConfig::create([
      'field_storage' => $this->card2,
      'bundle' => 'entity_test',
    ]);
    $field->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($this->card2->getName(), [
        'type' => 'options_select',
      ])
      ->save();

    // Create an entity.
    $entity = EntityTest::create([
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ]);
    $entity->save();
    $entity_init = clone $entity;

    // Display form: with no field data, nothing is selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertTrue($this->assertSession()->optionExists('card_2', '_none')->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_2', 0)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_2', 1)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_2', 2)->isSelected());
    $this->assertRaw('Some dangerous &amp; unescaped markup');

    // Submit form: select first and third options.
    $edit = ['card_2[]' => [0 => 0, 2 => 2]];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_2', [0, 2]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertTrue($this->assertSession()->optionExists('card_2', 0)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_2', 1)->isSelected());
    $this->assertTrue($this->assertSession()->optionExists('card_2', 2)->isSelected());

    // Submit form: select only first option.
    $edit = ['card_2[]' => [0 => 0]];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_2', [0]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertTrue($this->assertSession()->optionExists('card_2', 0)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_2', 1)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_2', 2)->isSelected());

    // Submit form: select the three options while the field accepts only 2.
    $edit = ['card_2[]' => [0 => 0, 1 => 1, 2 => 2]];
    $this->submitForm($edit, 'Save');
    $this->assertText('this field cannot hold more than 2 values');

    // Submit form: uncheck all options.
    $edit = ['card_2[]' => []];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_2', []);

    // Test the 'None' option.

    // Check that the 'none' option has no effect if actual options are selected
    // as well.
    $edit = ['card_2[]' => ['_none' => '_none', 0 => 0]];
    $this->drupalPostForm('entity_test/manage/' . $entity->id() . '/edit', $edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_2', [0]);

    // Check that selecting the 'none' option empties the field.
    $edit = ['card_2[]' => ['_none' => '_none']];
    $this->drupalPostForm('entity_test/manage/' . $entity->id() . '/edit', $edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_2', []);

    // A required select list does not have an empty key.
    $field->setRequired(TRUE);
    $field->save();
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertEmpty($this->xpath('//select[@id=:id]//option[@value=""]', [':id' => 'edit-card-2']), 'A required select list does not have an empty key.');

    // We do not have to test that a required select list with one option is
    // auto-selected because the browser does it for us.

    // Test optgroups.

    // Use a callback function defining optgroups.
    $this->card2->setSetting('allowed_values', []);
    $this->card2->setSetting('allowed_values_function', 'options_test_allowed_values_callback');
    $this->card2->save();
    $field->setRequired(FALSE);
    $field->save();

    // Display form: with no field data, nothing is selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertFalse($this->assertSession()->optionExists('card_2', 0)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_2', 1)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_2', 2)->isSelected());
    $this->assertRaw('Some dangerous &amp; unescaped markup');
    $this->assertRaw('More &lt;script&gt;dangerous&lt;/script&gt; markup');
    $this->assertRaw('Group 1');

    // Submit form: select first option.
    $edit = ['card_2[]' => [0 => 0]];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_2', [0]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertTrue($this->assertSession()->optionExists('card_2', 0)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_2', 1)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('card_2', 2)->isSelected());

    // Submit form: Unselect the option.
    $edit = ['card_2[]' => ['_none' => '_none']];
    $this->drupalPostForm('entity_test/manage/' . $entity->id() . '/edit', $edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_2', []);
  }

  /**
   * Tests the 'options_select' widget (float values).
   */
  public function testSelectListFloat() {

    // Create an instance of the 'float value' field.
    $field = FieldConfig::create([
      'field_storage' => $this->float,
      'bundle' => 'entity_test',
      'required' => TRUE,
    ]);
    $field->save();

    $this->container
      ->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('entity_test.entity_test.default')
      ->setComponent($this->float->getName(), ['type' => 'options_select'])
      ->save();

    // Create an entity.
    $entity = EntityTest::create([
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ]);
    $entity->save();

    // Display form.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');

    // With no field data, nothing is selected.
    $this->assertFalse($this->assertSession()->optionExists('float', 0)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('float', 1.5)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('float', 2)->isSelected());

    // Submit form.
    $edit = ['float' => 1.5];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity, 'float', [1.5]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertFalse($this->assertSession()->optionExists('float', 0)->isSelected());
    $this->assertTrue($this->assertSession()->optionExists('float', 1.5)->isSelected());
    $this->assertFalse($this->assertSession()->optionExists('float', 2)->isSelected());
  }

  /**
   * Tests the 'options_select' and 'options_button' widget for empty value.
   */
  public function testEmptyValue() {
    // Create an instance of the 'single value' field.
    $field = FieldConfig::create([
      'field_storage' => $this->card1,
      'bundle' => 'entity_test',
    ]);
    $field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Change it to the check boxes/radio buttons widget.
    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($this->card1->getName(), [
        'type' => 'options_buttons',
      ])
      ->save();

    // Create an entity.
    $entity = EntityTest::create([
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ]);
    $entity->save();

    // Display form: check that _none options are present and has label.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertNotEmpty($this->xpath('//div[@id=:id]//input[@value=:value]', [':id' => 'edit-card-1', ':value' => '_none']), 'A test radio button has a "None" choice.');
    $this->assertNotEmpty($this->xpath('//div[@id=:id]//label[@for=:for and text()=:label]', [':id' => 'edit-card-1', ':for' => 'edit-card-1-none', ':label' => 'N/A']), 'A test radio button has a "N/A" choice.');

    // Change it to the select widget.
    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($this->card1->getName(), [
        'type' => 'options_select',
      ])
      ->save();

    // Display form: check that _none options are present and has label.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    // A required field without any value has a "none" option.
    $this->assertNotEmpty($this->xpath('//select[@id=:id]//option[@value="_none" and text()=:label]', [':id' => 'edit-card-1', ':label' => '- None -']), 'A test select has a "None" choice.');
  }

}
