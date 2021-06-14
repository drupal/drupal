<?php

namespace Drupal\Tests\rdf\Kernel\Migrate\d7;

use Drupal\rdf\RdfMappingInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests RDF mappings migration from Drupal 7 to 8.
 *
 * @group rdf
 */
class MigrateRdfMappingTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_ui',
    'node',
    'rdf',
    'taxonomy',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(static::$modules);

    $this->executeMigrations([
      'd7_node_type',
      'd7_taxonomy_vocabulary',
      'd7_rdf_mapping',
    ]);
  }

  /**
   * Asserts various aspects of a RDF mapping.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string[] $types
   *   The expected RDF types.
   * @param array[] $field_mappings
   *   The expected RDF field mappings.
   */
  protected function assertRdfMapping($entity_type, $bundle, $types, $field_mappings) {
    $rdf_mapping = rdf_get_mapping($entity_type, $bundle);
    $this->assertInstanceOf(RdfMappingInterface::class, $rdf_mapping);
    $this->assertSame($types, $rdf_mapping->getBundleMapping());
    foreach ($field_mappings as $field => $mapping) {
      $this->assertSame($mapping, $rdf_mapping->getFieldMapping($field));
    }
  }

  /**
   * Tests RDF mappings migration from Drupal 7 to 8.
   */
  public function testRdfMappingMigration() {
    $this->assertRdfMapping(
      'node',
      'article',
      [
        'types' => [
          'sioc:Item',
          'foaf:Document',
        ],
      ],
      [
        'field_image' => [
          'properties' => [
            'og:image',
            'rdfs:seeAlso',
          ],
          'mapping_type' => 'rel',
        ],
        'field_tags' => [
          'properties' => [
            'dc:subject',
          ],
          'mapping_type' => 'rel',
        ],
        'title' => [
          'properties' => [
            'dc:title',
          ],
        ],
        'created' => [
          'properties' => [
            'dc:date',
            'dc:created',
          ],
          'datatype' => 'xsd:dateTime',
          'datatype_callback' => [
            'callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value',
          ],
        ],
        'changed' => [
          'properties' => [
            'dc:modified',
          ],
          'datatype' => 'xsd:dateTime',
          'datatype_callback' => [
            'callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value',
          ],
        ],
        'body' => [
          'properties' => [
            'content:encoded',
          ],
        ],
        'uid' => [
          'properties' => [
            'sioc:has_creator',
          ],
          'mapping_type' => 'rel',
        ],
        'name' => [
          'properties' => [
            'foaf:name',
          ],
        ],
        'comment_count' => [
          'properties' => [
            'sioc:num_replies',
          ],
          'datatype' => 'xsd:integer',
        ],
        'last_activity' => [
          'properties' => [
            'sioc:last_activity_date',
          ],
          'datatype' => 'xsd:dateTime',
          'datatype_callback' => [
            'callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value',
          ],
        ],
      ]
    );
    $this->assertRdfMapping(
      'node',
      'blog',
      [
        'types' => [
          'sioc:Post',
          'sioct:BlogPost',
        ],
      ],
      [
        'title' => [
          'properties' => [
            'dc:title',
          ],
        ],
        'created' => [
          'properties' => [
            'dc:date',
            'dc:created',
          ],
          'datatype' => 'xsd:dateTime',
          'datatype_callback' => [
            'callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value',
          ],
        ],
        'changed' => [
          'properties' => [
            'dc:modified',
          ],
          'datatype' => 'xsd:dateTime',
          'datatype_callback' => [
            'callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value',
          ],
        ],
        'body' => [
          'properties' => [
            'content:encoded',
          ],
        ],
        'uid' => [
          'properties' => [
            'sioc:has_creator',
          ],
          'mapping_type' => 'rel',
        ],
        'name' => [
          'properties' => [
            'foaf:name',
          ],
        ],
        'comment_count' => [
          'properties' => [
            'sioc:num_replies',
          ],
          'datatype' => 'xsd:integer',
        ],
        'last_activity' => [
          'properties' => [
            'sioc:last_activity_date',
          ],
          'datatype' => 'xsd:dateTime',
          'datatype_callback' => [
            'callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value',
          ],
        ],
      ]
    );
    $this->assertRdfMapping(
      'node',
      'forum',
      [
        'types' => [
          'sioc:Post',
          'sioct:BoardPost',
        ],
      ],
      [
        'taxonomy_forums' => [
          'properties' => [
            'sioc:has_container',
          ],
          'mapping_type' => 'rel',
        ],
        'title' => [
          'properties' => [
            'dc:title',
          ],
        ],
        'created' => [
          'properties' => [
            'dc:date',
            'dc:created',
          ],
          'datatype' => 'xsd:dateTime',
          'datatype_callback' => [
            'callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value',
          ],
        ],
        'changed' => [
          'properties' => [
            'dc:modified',
          ],
          'datatype' => 'xsd:dateTime',
          'datatype_callback' => [
            'callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value',
          ],
        ],
        'body' => [
          'properties' => [
            'content:encoded',
          ],
        ],
        'uid' => [
          'properties' => [
            'sioc:has_creator',
          ],
          'mapping_type' => 'rel',
        ],
        'name' => [
          'properties' => [
            'foaf:name',
          ],
        ],
        'comment_count' => [
          'properties' => [
            'sioc:num_replies',
          ],
          'datatype' => 'xsd:integer',
        ],
        'last_activity' => [
          'properties' => [
            'sioc:last_activity_date',
          ],
          'datatype' => 'xsd:dateTime',
          'datatype_callback' => [
            'callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value',
          ],
        ],
      ]
    );
    $this->assertRdfMapping(
      'node',
      'page',
      [
        'types' => [
          'foaf:Document',
        ],
      ],
      [
        'title' => [
          'properties' => [
            'dc:title',
          ],
        ],
        'created' => [
          'properties' => [
            'dc:date',
            'dc:created',
          ],
          'datatype' => 'xsd:dateTime',
          'datatype_callback' => [
            'callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value',
          ],
        ],
        'changed' => [
          'properties' => [
            'dc:modified',
          ],
          'datatype' => 'xsd:dateTime',
          'datatype_callback' => [
            'callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value',
          ],
        ],
        'body' => [
          'properties' => [
            'content:encoded',
          ],
        ],
        'uid' => [
          'properties' => [
            'sioc:has_creator',
          ],
          'mapping_type' => 'rel',
        ],
        'name' => [
          'properties' => [
            'foaf:name',
          ],
        ],
        'comment_count' => [
          'properties' => [
            'sioc:num_replies',
          ],
          'datatype' => 'xsd:integer',
        ],
        'last_activity' => [
          'properties' => [
            'sioc:last_activity_date',
          ],
          'datatype' => 'xsd:dateTime',
          'datatype_callback' => [
            'callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value',
          ],
        ],
      ]
    );
    $this->assertRdfMapping(
      'taxonomy_term',
      'forums',
      [
        'types' => [
          'sioc:Container',
          'sioc:Forum',
        ],
      ],
      [
        'name' => [
          'properties' => [
            'rdfs:label',
            'skos:prefLabel',
          ],
        ],
        'description' => [
          'properties' => [
            'skos:definition',
          ],
        ],
        'vid' => [
          'properties' => [
            'skos:inScheme',
          ],
          'mapping_type' => 'rel',
        ],
        'parent' => [
          'properties' => [
            'skos:broader',
          ],
          'mapping_type' => 'rel',
        ],
      ]
    );

    // Clear the map table and check that the migration runs successfully when
    // the rdf mappings already exist.
    $id_map = $this->getMigration('d7_rdf_mapping')->getIdMap();
    $id_map->destroy();
    $this->executeMigration('d7_rdf_mapping');
  }

}
