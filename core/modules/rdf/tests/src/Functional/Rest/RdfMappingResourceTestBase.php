<?php

namespace Drupal\Tests\rdf\Functional\Rest;

use Drupal\node\Entity\NodeType;
use Drupal\rdf\Entity\RdfMapping;
use Drupal\Tests\rest\Functional\EntityResource\ConfigEntityResourceTestBase;

abstract class RdfMappingResourceTestBase extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'rdf'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'rdf_mapping';

  /**
   * @var \Drupal\rdf\RdfMappingInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer site configuration']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" node type.
    $camelids = NodeType::create([
      'name' => 'Camelids',
      'type' => 'camelids',
    ]);

    $camelids->save();

    // Create the RDF mapping.
    $llama = RdfMapping::create([
      'targetEntityType' => 'node',
      'bundle' => 'camelids',
    ]);
    $llama->setBundleMapping([
      'types' => ['sioc:Item', 'foaf:Document'],
    ])
      ->setFieldMapping('title', [
        'properties' => ['dc:title'],
      ])
      ->setFieldMapping('created', [
        'properties' => ['dc:date', 'dc:created'],
        'datatype' => 'xsd:dateTime',
        'datatype_callback' => ['callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value'],
      ])
      ->save();

    return $llama;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'bundle' => 'camelids',
      'dependencies' => [
        'config' => [
          'node.type.camelids',
        ],
        'module' => [
          'node',
        ],
      ],
      'fieldMappings' => [
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
      ],
      'id' => 'node.camelids',
      'langcode' => 'en',
      'status' => TRUE,
      'targetEntityType' => 'node',
      'types' => [
        'sioc:Item',
        'foaf:Document',
      ],
      'uuid' => $this->entity->uuid(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    return [
      'user.permissions',
    ];
  }

}
