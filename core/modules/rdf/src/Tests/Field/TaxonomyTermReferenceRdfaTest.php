<?php
/**
 * @file
 * Contains \Drupal\rdf\Tests\Field\TaxonomyTermReferenceRdfaTest.
 */

namespace Drupal\rdf\Tests\Field;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\rdf\Tests\Field\FieldRdfaTestBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests the RDFa output of the taxonomy term reference field formatter.
 *
 * @group rdf
 */
class TaxonomyTermReferenceRdfaTest extends FieldRdfaTestBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldType = 'taxonomy_term_reference';

  /**
   * The term for testing.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $term;

  /**
   * The URI of the term for testing.
   *
   * @var string
   */
  protected $termUri;

  /**
   * {@inheritdoc}
   */
  public static $modules = array('taxonomy', 'options', 'text', 'filter');

  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');

    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomMachineName(),
      'vid' => drupal_strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $vocabulary->save();

    entity_create('field_storage_config', array(
      'name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'taxonomy_term_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $vocabulary->id(),
            'parent' => 0,
          ),
        ),
      ),
    ))->save();
    entity_create('field_instance_config', array(
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'bundle' => 'entity_test',
    ))->save();

    $this->term = entity_create('taxonomy_term', array(
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $this->term->save();

    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, array(
      'properties' => array('schema:about'),
    ))->save();

    // Set up test values.
    $this->entity = entity_create('entity_test');
    $this->entity->{$this->fieldName}->target_id = $this->term->id();
    $this->entity->save();
    $this->uri = $this->getAbsoluteUri($this->entity);
  }

  /**
   * Tests the plain formatter.
   */
  public function testAllFormatters() {
    // Tests the plain formatter.
    $this->assertFormatterRdfa(array('type' => 'taxonomy_term_reference_plain'), 'http://schema.org/about', array('value' => $this->term->getName(), 'type' => 'literal'));
    // Tests the link formatter.
    $term_uri = $this->getAbsoluteUri($this->term);
    $this->assertFormatterRdfa(array('type'=>'taxonomy_term_reference_link'), 'http://schema.org/about', array('value' => $term_uri, 'type' => 'uri'));
  }

}
