<?php

/**
 * @file
 * Contains Drupal\system\Tests\Entity\BundleConstraintValidatorTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\simpletest\KernelTestBase;
use Drupal\system\Tests\TypedData;

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

  public static $modules = array('node', 'field', 'text', 'user');

  protected function setUp() {
    parent::setUp();
    $this->typedData = $this->container->get('typed_data_manager');
  }

  /**
   * Tests bundle constraint validation.
   */
  public function testValidation() {
    // Test with multiple values.
    $this->assertValidation(array('foo', 'bar'));
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
    $node = $this->container->get('entity.manager')->getStorage('node')->create(array('type' => 'foo'));

    $typed_data = $this->typedData->create($definition, $node);
    $violations = $typed_data->validate();
    $this->assertEqual($violations->count(), 0, 'Validation passed for correct value.');

    // Test the validation when an invalid value is passed.
    $page_node = $this->container->get('entity.manager')->getStorage('node')->create(array('type' => 'baz'));

    $typed_data = $this->typedData->create($definition, $page_node);
    $violations = $typed_data->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed for incorrect value.');

    // Make sure the information provided by a violation is correct.
    $violation = $violations[0];
    $this->assertEqual($violation->getMessage(), t('The entity must be of bundle %bundle.', array('%bundle' => implode(', ', (array) $bundle))), 'The message for invalid value is correct.');
    $this->assertEqual($violation->getRoot(), $typed_data, 'Violation root is correct.');
    $this->assertEqual($violation->getInvalidValue(), $page_node, 'The invalid value is set correctly in the violation.');
  }
}
