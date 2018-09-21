<?php

namespace Drupal\Tests\rdf\Kernel\Field;

use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the RDFa output of the entity reference field formatter.
 *
 * @group rdf
 */
class EntityReferenceRdfaTest extends FieldRdfaTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $fieldType = 'entity_reference';

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * The term for testing.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $targetEntity;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['text', 'filter'];

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');

    // Give anonymous users permission to view test entities.
    $this->installConfig(['user']);
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('view test entity')
      ->save();

    $this->createEntityReferenceField($this->entityType, $this->bundle, $this->fieldName, 'Field test', $this->entityType);

    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, [
      'properties' => ['schema:knows'],
    ])->save();

    // Create the entity to be referenced.
    $this->targetEntity = $this->container->get('entity_type.manager')
      ->getStorage($this->entityType)
      ->create(['name' => $this->randomMachineName()]);
    $this->targetEntity->save();

    // Create the entity that will have the entity reference field.
    $this->entity = $this->container->get('entity_type.manager')
      ->getStorage($this->entityType)
      ->create(['name' => $this->randomMachineName()]);
    $this->entity->save();
    $this->entity->{$this->fieldName}->entity = $this->targetEntity;
    $this->uri = $this->getAbsoluteUri($this->entity);
  }

  /**
   * Tests all the entity reference formatters.
   */
  public function testAllFormatters() {
    $entity_uri = $this->getAbsoluteUri($this->targetEntity);

    // Tests the label formatter.
    $this->assertFormatterRdfa(['type' => 'entity_reference_label'], 'http://schema.org/knows', ['value' => $entity_uri, 'type' => 'uri']);
    // Tests the entity formatter.
    $this->assertFormatterRdfa(['type' => 'entity_reference_entity_view'], 'http://schema.org/knows', ['value' => $entity_uri, 'type' => 'uri']);
  }

}
