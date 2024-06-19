<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\TypedData;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\TypedData\Validation\RecursiveContextualValidator
 * @group Validation
 */
class RecursiveContextualValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
  }

  /**
   * Tests recursive validation against given constraints against an entity.
   */
  public function testRecursiveValidate(): void {
    $entity = EntityTest::create();
    $adapter = EntityAdapter::createFromEntity($entity);
    // This would trigger the ValidReferenceConstraint due to EntityTest
    // defaulting uid to 1, which doesn't exist. Ensure that we don't get a
    // violation for that.
    $this->assertCount(0, \Drupal::typedDataManager()->getValidator()->validate($adapter, $adapter->getConstraints()));
  }

  /**
   * Tests recursive propagation of violations.
   */
  public function testRecursiveViolationPropagation(): void {
    // We create an entity reference field with a constraint which will
    // trigger the validation of the referenced entities. Then we add a
    // required field and populate it only on the parent entity, so that
    // the child entity fails the validation.
    $definitions['field_test'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Test reference')
      ->setSetting('target_type', 'entity_test')
      ->addConstraint('TestValidatedReferenceConstraint');
    $definitions['string_required'] = BaseFieldDefinition::create('string')
      ->setLabel('Required string')
      ->setRequired(TRUE);
    $this->container->get('state')->set('entity_test.additional_base_field_definitions', $definitions);

    $this->installEntitySchema('entity_test');
    $child = EntityTest::create([
      'name' => 'test2',
      'user_id' => ['target_id' => 0],
    ]);
    $parent = EntityTest::create([
      'name' => 'test',
      'user_id' => ['target_id' => 0],
      'string_required' => 'some string',
      'field_test' => ['entity' => $child],
    ]);
    // The child entity should fail the validation and the violation should
    // propagate to the parent.
    $violations = $parent->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('field_test', $violations[0]->getPropertyPath());
    $this->assertEquals('Invalid referenced entity.', $violations[0]->getMessage());
  }

}
