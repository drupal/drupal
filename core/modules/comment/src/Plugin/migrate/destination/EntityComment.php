<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\migrate\destination\EntityComment.
 */

namespace Drupal\comment\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateDestination(
 *   id = "entity:comment"
 * )
 */
class EntityComment extends EntityContentBase {

  /**
   * The state storage object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The entity query object.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;

  /**
   * An array of entity IDs for the 'commented entity' keyed by entity type.
   *
   * @var array
   */
  protected $stubCommentedEntityIds;

  /**
   * Builds an comment entity destination.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param MigrationInterface $migration
   *   The migration.
   * @param EntityStorageInterface $storage
   *   The storage for this entity type.
   * @param array $bundles
   *   The list of bundles this entity type has.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage object.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The query object that can query the given entity type.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, EntityManagerInterface $entity_manager, StateInterface $state, QueryFactory $entity_query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $entity_manager);
    $this->state = $state;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_type = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity.manager')->getStorage($entity_type),
      array_keys($container->get('entity.manager')->getBundleInfo($entity_type)),
      $container->get('entity.manager'),
      $container->get('state'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    if ($row->isStub() && ($state = $this->state->get('comment.maintain_entity_statistics', 0))) {
      $this->state->set('comment.maintain_entity_statistics', 0);
    }
    $return = parent::import($row, $old_destination_id_values);
    if ($row->isStub() && $state) {
      $this->state->set('comment.maintain_entity_statistics', $state);
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function processStubRow(Row $row) {
    parent::processStubRow($row);
    $stub_commented_entity_type = $row->getDestinationProperty('entity_type');

    // While parent::getEntity() fills the bundle property for stub entities
    // if it's still empty, here we must also make sure entity_id/entity_type
    // are filled (so $comment->getCommentedEntity() always returns a value).
    if (empty($this->stubCommentedEntityIds[$stub_commented_entity_type])) {
      // Fill stub entity id. Any id will do, as long as it exists.
      $entity_type = $this->entityManager->getDefinition($stub_commented_entity_type);
      $id_key = $entity_type->getKey('id');
      $result = $this->entityQuery
        ->get($stub_commented_entity_type)
        ->range(0, 1)
        ->execute();
      if ($result) {
        $this->stubCommentedEntityIds[$stub_commented_entity_type] = array_pop($result);
        $row->setSourceProperty($id_key, $this->stubCommentedEntityIds[$stub_commented_entity_type]);
      }
      else {
        throw new MigrateException(t('Could not find parent entity to use for comment %id', ['%id' => implode(':', $row->getSourceIdValues())]), MigrationInterface::MESSAGE_ERROR);
      }
    }

    $row->setDestinationProperty('entity_id', $this->stubCommentedEntityIds[$stub_commented_entity_type]);
    $row->setDestinationProperty('entity_type', $stub_commented_entity_type);
    $row->setDestinationProperty('created', REQUEST_TIME);
    $row->setDestinationProperty('changed', REQUEST_TIME);
  }

}
