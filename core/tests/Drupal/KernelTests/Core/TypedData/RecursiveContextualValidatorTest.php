<?php

namespace Drupal\KernelTests\Core\TypedData;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
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
  public static $modules = [
    'entity_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
  }

  /**
   * Tests recursive validation against given constraints against an entity.
   */
  public function testRecursiveValidate() {
    $entity = EntityTest::create();
    $adapter = EntityAdapter::createFromEntity($entity);
    // This would trigger the ValidReferenceConstraint due to EntityTest
    // defaulting uid to 1, which doesn't exist. Ensure that we don't get a
    // violation for that.
    $this->assertCount(0, \Drupal::typedDataManager()->getValidator()->validate($adapter, $adapter->getConstraints()));
  }

}
