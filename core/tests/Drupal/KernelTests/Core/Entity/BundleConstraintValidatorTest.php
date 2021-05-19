<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests validation constraints for BundleConstraintValidator.
 *
 * @group Entity
 */
class BundleConstraintValidatorTest extends KernelTestBase {

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedData;

  protected static $modules = ['node', 'field', 'text', 'user'];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->typedData = $this->container->get('typed_data_manager');
  }

  /**
   * Tests bundle constraint validation.
   */
  public function testValidation() {
    // Test with multiple values.
    $this->assertValidation(['foo', 'bar']);
    // Test with a single string value as well.
    $this->assertValidation('foo');
  }

  /**
   * Executes the BundleConstraintValidator test for a given bundle.
   *
   * @param string|array $bundle
   *   Bundle/bundles to use as constraint option.
   */
  protected function assertValidation($bundle) {
    // Create a typed data definition with a Bundle constraint.
    $definition = DataDefinition::create('entity_reference')
      ->addConstraint('Bundle', $bundle);

    // Test the validation.
    $node = $this->container->get('entity_type.manager')->getStorage('node')->create(['type' => 'foo']);

    $typed_data = $this->typedData->create($definition, $node);
    $violations = $typed_data->validate();
    $this->assertEquals(0, $violations->count(), 'Validation passed for correct value.');

    // Test the validation when an invalid value is passed.
    $page_node = $this->container->get('entity_type.manager')->getStorage('node')->create(['type' => 'baz']);

    $typed_data = $this->typedData->create($definition, $page_node);
    $violations = $typed_data->validate();
    $this->assertEquals(1, $violations->count(), 'Validation failed for incorrect value.');

    // Make sure the information provided by a violation is correct.
    $violation = $violations[0];
    $this->assertEquals(t('The entity must be of bundle %bundle.', ['%bundle' => implode(', ', (array) $bundle)]), $violation->getMessage(), 'The message for invalid value is correct.');
    $this->assertEquals($typed_data, $violation->getRoot(), 'Violation root is correct.');
    $this->assertEquals($page_node, $violation->getInvalidValue(), 'The invalid value is set correctly in the violation.');
  }

}
