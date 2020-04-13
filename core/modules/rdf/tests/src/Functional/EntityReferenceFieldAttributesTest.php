<?php

namespace Drupal\Tests\rdf\Functional;

use Drupal\Core\Url;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\rdf\Traits\RdfParsingTrait;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestBase;

/**
 * Tests RDFa markup generation for taxonomy term fields.
 *
 * @group rdf
 */
class EntityReferenceFieldAttributesTest extends TaxonomyTestBase {

  use RdfParsingTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['rdf', 'field_test', 'file', 'image'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * URI of the front page of the Drupal site.
   *
   * @var string
   */
  protected $baseUri;

  /**
   * The name of the taxonomy term reference field used in the test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The vocabulary object used in the test.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  protected function setUp(): void {
    parent::setUp();

    $web_user = $this->drupalCreateUser(['bypass node access', 'administer taxonomy']);
    $this->drupalLogin($web_user);
    $this->vocabulary = $this->createVocabulary();

    // Create the field.
    $this->fieldName = 'field_taxonomy_test';
    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'article', $this->fieldName, 'Tags', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('node', 'article')
      ->setComponent($this->fieldName, ['type' => 'options_select'])
      ->save();
    $display_repository->getViewDisplay('node', 'article', 'full')
      ->setComponent($this->fieldName, ['type' => 'entity_reference_label'])
      ->save();

    // Set the RDF mapping for the new field.
    rdf_get_mapping('node', 'article')
      ->setFieldMapping($this->fieldName, [
        'properties' => ['dc:subject'],
        'mapping_type' => 'rel',
      ])
      ->save();

    rdf_get_mapping('taxonomy_term', $this->vocabulary->id())
      ->setBundleMapping(['types' => ['skos:Concept']])
      ->setFieldMapping('name', ['properties' => ['rdfs:label']])
      ->save();

    // Prepares commonly used URIs.
    $this->baseUri = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
  }

  /**
   * Tests if file fields in teasers have correct resources.
   *
   * Ensure that file fields have the correct resource as the object in RDFa
   * when displayed as a teaser.
   */
  public function testNodeTeaser() {
    // Set the teaser display to show this field.
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'article', 'teaser')
      ->setComponent($this->fieldName, ['type' => 'entity_reference_label'])
      ->save();

    // Create a term in each vocabulary.
    $term1 = $this->createTerm($this->vocabulary);
    $term2 = $this->createTerm($this->vocabulary);
    $taxonomy_term_1_uri = $term1->toUrl('canonical', ['absolute' => TRUE])->toString();
    $taxonomy_term_2_uri = $term2->toUrl('canonical', ['absolute' => TRUE])->toString();

    // Create the node.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $node->set($this->fieldName, [
      ['target_id' => $term1->id()],
      ['target_id' => $term2->id()],
    ]);

    // Render the node.
    $node_render_array = \Drupal::entityTypeManager()
      ->getViewBuilder('node')
      ->view($node, 'teaser');
    $html = \Drupal::service('renderer')->renderRoot($node_render_array);

    // Node relations to taxonomy terms.
    $node_uri = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
    $expected_value = [
      'type' => 'uri',
      'value' => $taxonomy_term_1_uri,
    ];
    $this->assertTrue($this->hasRdfProperty($html, $this->baseUri, $node_uri, 'http://purl.org/dc/terms/subject', $expected_value), 'Node to term relation found in RDF output (dc:subject).');
    $expected_value = [
      'type' => 'uri',
      'value' => $taxonomy_term_2_uri,
    ];
    $this->assertTrue($this->hasRdfProperty($html, $this->baseUri, $node_uri, 'http://purl.org/dc/terms/subject', $expected_value), 'Node to term relation found in RDF output (dc:subject).');
    // Taxonomy terms triples.
    // Term 1.
    $expected_value = [
      'type' => 'uri',
      'value' => 'http://www.w3.org/2004/02/skos/core#Concept',
    ];
    // @todo Enable with https://www.drupal.org/node/2072791.
    // $this->assertTrue($graph->hasProperty($taxonomy_term_1_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Taxonomy term type found in RDF output (skos:Concept).');
    $expected_value = [
      'type' => 'literal',
      'value' => $term1->getName(),
    ];
    // $this->assertTrue($graph->hasProperty($taxonomy_term_1_uri, 'http://www.w3.org/2000/01/rdf-schema#label', $expected_value), 'Taxonomy term name found in RDF output (rdfs:label).');
    // Term 2.
    $expected_value = [
      'type' => 'uri',
      'value' => 'http://www.w3.org/2004/02/skos/core#Concept',
    ];
    // $this->assertTrue($graph->hasProperty($taxonomy_term_2_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Taxonomy term type found in RDF output (skos:Concept).');
    $expected_value = [
      'type' => 'literal',
      'value' => $term2->getName(),
    ];
    // $this->assertTrue($graph->hasProperty($taxonomy_term_2_uri, 'http://www.w3.org/2000/01/rdf-schema#label', $expected_value), 'Taxonomy term name found in RDF output (rdfs:label).');
  }

}
