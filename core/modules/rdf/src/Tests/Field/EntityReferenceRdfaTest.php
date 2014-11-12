<?php
/**
 * @file
 * Contains \Drupal\rdf\Tests\Field\TaxonomyTermReferenceRdfaTest.
 */

namespace Drupal\rdf\Tests\Field;

use Drupal\rdf\Tests\Field\FieldRdfaTestBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\Language;

/**
 * Tests the RDFa output of the entity reference field formatter.
 *
 * @group rdf
 */
class EntityReferenceRdfaTest extends FieldRdfaTestBase {

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
  protected $target_entity;

  /**
   * {@inheritdoc}
   */
  public static $modules = array('entity_reference', 'text', 'filter');

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');

    entity_reference_create_field($this->entityType, $this->bundle, $this->fieldName, 'Field test', $this->entityType);

    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, array(
      'properties' => array('schema:knows'),
    ))->save();

    // Create the entity to be referenced.
    $this->target_entity = entity_create($this->entityType, array('name' => $this->randomMachineName()));
    $this->target_entity->save();

    // Create the entity that will have the entity reference field.
    $this->entity = entity_create($this->entityType, array('name' => $this->randomMachineName()));
    $this->entity->save();
    $this->entity->{$this->fieldName}->entity = $this->target_entity;
    $this->entity->{$this->fieldName}->access = TRUE;
    $this->uri = $this->getAbsoluteUri($this->entity);
  }

  /**
   * Tests all the entity reference formatters.
   */
  public function testAllFormatters() {
    $entity_uri = $this->getAbsoluteUri($this->target_entity);

    // Tests the label formatter.
    $this->assertFormatterRdfa(array('type' => 'entity_reference_label'), 'http://schema.org/knows', array('value' => $entity_uri, 'type' => 'uri'));
    // Tests the entity formatter.
    $this->assertFormatterRdfa(array('type' => 'entity_reference_entity_view'), 'http://schema.org/knows', array('value' => $entity_uri, 'type' => 'uri'));
  }

}
