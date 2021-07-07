<?php

namespace Drupal\Tests\field\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests field elements in nested forms.
 *
 * @group field
 */
class NestedFormTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['field_test', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    $web_user = $this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
    ]);
    $this->drupalLogin($web_user);

    $this->fieldStorageSingle = [
      'field_name' => 'field_single',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ];
    $this->fieldStorageUnlimited = [
      'field_name' => 'field_unlimited',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];

    $this->field = [
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName() . '_label',
      'description' => '[site:name]_description',
      'weight' => mt_rand(0, 127),
      'settings' => [
        'test_field_setting' => $this->randomMachineName(),
      ],
    ];
  }

  /**
   * Tests Field API form integration within a subform.
   */
  public function testNestedFieldForm() {
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Add two fields on the 'entity_test'
    FieldStorageConfig::create($this->fieldStorageSingle)->save();
    FieldStorageConfig::create($this->fieldStorageUnlimited)->save();
    $this->field['field_name'] = 'field_single';
    $this->field['label'] = 'Single field';
    FieldConfig::create($this->field)->save();
    $display_repository->getFormDisplay($this->field['entity_type'], $this->field['bundle'])
      ->setComponent($this->field['field_name'])
      ->save();
    $this->field['field_name'] = 'field_unlimited';
    $this->field['label'] = 'Unlimited field';
    FieldConfig::create($this->field)->save();
    $display_repository->getFormDisplay($this->field['entity_type'], $this->field['bundle'])
      ->setComponent($this->field['field_name'])
      ->save();

    // Create two entities.
    $entity_type = 'entity_test';
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($entity_type);

    $entity_1 = $storage->create(['id' => 1]);
    $entity_1->enforceIsNew();
    $entity_1->field_single->value = 1;
    $entity_1->field_unlimited->value = 2;
    $entity_1->save();

    $entity_2 = $storage->create(['id' => 2]);
    $entity_2->enforceIsNew();
    $entity_2->field_single->value = 10;
    $entity_2->field_unlimited->value = 11;
    $entity_2->save();

    // Display the 'combined form'.
    $this->drupalGet('test-entity/nested/1/2');
    $this->assertSession()->fieldValueEquals('field_single[0][value]', 1);
    $this->assertSession()->fieldValueEquals('field_unlimited[0][value]', 2);
    $this->assertSession()->fieldValueEquals('entity_2[field_single][0][value]', 10);
    $this->assertSession()->fieldValueEquals('entity_2[field_unlimited][0][value]', 11);

    // Submit the form and check that the entities are updated accordingly.
    $edit = [
      'field_single[0][value]' => 1,
      'field_unlimited[0][value]' => 2,
      'field_unlimited[1][value]' => 3,
      'entity_2[field_single][0][value]' => 11,
      'entity_2[field_unlimited][0][value]' => 12,
      'entity_2[field_unlimited][1][value]' => 13,
    ];
    $this->submitForm($edit, 'Save');
    $entity_1 = $storage->load(1);
    $entity_2 = $storage->load(2);
    $this->assertFieldValues($entity_1, 'field_single', [1]);
    $this->assertFieldValues($entity_1, 'field_unlimited', [2, 3]);
    $this->assertFieldValues($entity_2, 'field_single', [11]);
    $this->assertFieldValues($entity_2, 'field_unlimited', [12, 13]);

    // Submit invalid values and check that errors are reported on the
    // correct widgets.
    $edit = [
      'field_unlimited[1][value]' => -1,
    ];
    $this->drupalGet('test-entity/nested/1/2');
    $this->submitForm($edit, 'Save');
    $this->assertRaw(t('%label does not accept the value -1', ['%label' => 'Unlimited field']));
    // Entity 1: check that the error was flagged on the correct element.
    $error_field = $this->assertSession()->fieldExists('edit-field-unlimited-1-value');
    $this->assertTrue($error_field->hasClass('error'));
    $edit = [
      'entity_2[field_unlimited][1][value]' => -1,
    ];
    $this->drupalGet('test-entity/nested/1/2');
    $this->submitForm($edit, 'Save');
    $this->assertRaw(t('%label does not accept the value -1', ['%label' => 'Unlimited field']));
    // Entity 2: check that the error was flagged on the correct element.
    $error_field = $this->assertSession()->fieldExists('edit-entity-2-field-unlimited-1-value');
    $this->assertTrue($error_field->hasClass('error'));

    // Test that reordering works on both entities.
    $edit = [
      'field_unlimited[0][_weight]' => 0,
      'field_unlimited[1][_weight]' => -1,
      'entity_2[field_unlimited][0][_weight]' => 0,
      'entity_2[field_unlimited][1][_weight]' => -1,
    ];
    $this->drupalGet('test-entity/nested/1/2');
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_1, 'field_unlimited', [3, 2]);
    $this->assertFieldValues($entity_2, 'field_unlimited', [13, 12]);

    // Test the 'add more' buttons.
    // 'Add more' button in the first entity:
    $this->drupalGet('test-entity/nested/1/2');
    $this->submitForm([], 'field_unlimited_add_more');
    $this->assertSession()->fieldValueEquals('field_unlimited[0][value]', 3);
    $this->assertSession()->fieldValueEquals('field_unlimited[1][value]', 2);
    $this->assertSession()->fieldValueEquals('field_unlimited[2][value]', '');
    $this->assertSession()->fieldValueEquals('field_unlimited[3][value]', '');
    // 'Add more' button in the first entity (changing field values):
    $edit = [
      'entity_2[field_unlimited][0][value]' => 13,
      'entity_2[field_unlimited][1][value]' => 14,
      'entity_2[field_unlimited][2][value]' => 15,
    ];
    $this->submitForm($edit, 'entity_2_field_unlimited_add_more');
    $this->assertSession()->fieldValueEquals('entity_2[field_unlimited][0][value]', 13);
    $this->assertSession()->fieldValueEquals('entity_2[field_unlimited][1][value]', 14);
    $this->assertSession()->fieldValueEquals('entity_2[field_unlimited][2][value]', 15);
    $this->assertSession()->fieldValueEquals('entity_2[field_unlimited][3][value]', '');
    // Save the form and check values are saved correctly.
    $this->submitForm([], 'Save');
    $this->assertFieldValues($entity_1, 'field_unlimited', [3, 2]);
    $this->assertFieldValues($entity_2, 'field_unlimited', [13, 14, 15]);
  }

  /**
   * Tests entity level validation within subforms.
   */
  public function testNestedEntityFormEntityLevelValidation() {
    // Create two entities.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('entity_test_constraints');

    $entity_1 = $storage->create();
    $entity_1->save();

    $entity_2 = $storage->create();
    $entity_2->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Display the 'combined form'.
    $this->drupalGet("test-entity-constraints/nested/{$entity_1->id()}/{$entity_2->id()}");
    $assert_session->hiddenFieldValueEquals('entity_2[changed]', REQUEST_TIME);

    // Submit the form and check that the entities are updated accordingly.
    $assert_session->hiddenFieldExists('entity_2[changed]')
      ->setValue(REQUEST_TIME - 86400);
    $page->pressButton(t('Save'));

    $elements = $this->cssSelect('.entity-2.error');
    $this->assertCount(1, $elements, 'The whole nested entity form has been correctly flagged with an error class.');
  }

}
