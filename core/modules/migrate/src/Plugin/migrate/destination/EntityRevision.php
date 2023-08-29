<?php

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Provides entity revision destination plugin.
 *
 * Refer to the parent class for configuration keys:
 * \Drupal\migrate\Plugin\migrate\destination\EntityContentBase
 *
 * Entity revisions can only be migrated after the entity to which the revisions
 * belong has been migrated. For example, revisions of a given content type can
 * be migrated only after the nodes of that content type have been migrated.
 *
 * In order to avoid revision ID conflicts, make sure that the entity migration
 * also includes the revision ID. If the entity migration did not include the
 * revision ID, the entity would get the next available revision ID (1 when
 * migrating to a clean database). Then, when revisions are migrated after the
 * entities, the revision IDs would almost certainly collide.
 *
 * The examples below contain simple node and node revision migrations. The
 * examples use the EmbeddedDataSource source plugin for the sake of
 * simplicity. The important part of both examples is the 'vid' property, which
 * is the revision ID for nodes.
 *
 * Example of 'article' node migration, which must be executed before the
 * 'article' revisions.
 * @code
 * id: custom_article_migration
 * label: 'Custom article migration'
 * source:
 *   plugin: embedded_data
 *   data_rows:
 *     -
 *       nid: 1
 *       vid: 2
 *       revision_timestamp: 1514661000
 *       revision_log: 'Second revision'
 *       title: 'Current title'
 *       content: '<p>Current content</p>'
 *   ids:
 *     nid:
 *       type: integer
 * process:
 *   nid: nid
 *   vid: vid
 *   revision_timestamp: revision_timestamp
 *   revision_log: revision_log
 *   title: title
 *   'body/0/value': content
 *   'body/0/format':
 *      plugin: default_value
 *      default_value: basic_html
 * destination:
 *   plugin: entity:node
 *   default_bundle: article
 * @endcode
 *
 * Example of the corresponding node revision migration, which must be executed
 * after the above migration.
 * @code
 * id: custom_article_revision_migration
 * label: 'Custom article revision migration'
 * source:
 *   plugin: embedded_data
 *   data_rows:
 *     -
 *       nid: 1
 *       vid: 1
 *       revision_timestamp: 1514660000
 *       revision_log: 'First revision'
 *       title: 'Previous title'
 *       content: '<p>Previous content</p>'
 *   ids:
 *     nid:
 *       type: integer
 * process:
 *   nid:
 *     plugin: migration_lookup
 *     migration: custom_article_migration
 *     source: nid
 *   vid: vid
 *   revision_timestamp: revision_timestamp
 *   revision_log: revision_log
 *   title: title
 *   'body/0/value': content
 *   'body/0/format':
 *      plugin: default_value
 *      default_value: basic_html
 * destination:
 *   plugin: entity_revision:node
 *   default_bundle: article
 * migration_dependencies:
 *   required:
 *     - custom_article_migration
 * @endcode
 *
 * @MigrateDestination(
 *   id = "entity_revision",
 *   deriver = "Drupal\migrate\Plugin\Derivative\MigrateEntityRevision"
 * )
 */
class EntityRevision extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, AccountSwitcherInterface $account_switcher) {
    $plugin_definition += [
      'label' => new TranslatableMarkup('@entity_type revisions', ['@entity_type' => $storage->getEntityType()->getSingularLabel()]),
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $entity_field_manager, $field_type_manager, $account_switcher);
  }

  /**
   * Gets the entity.
   *
   * @param \Drupal\migrate\Row $row
   *   The row object.
   * @param array $old_destination_id_values
   *   The old destination IDs.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   The entity or false if it can not be created.
   */
  protected function getEntity(Row $row, array $old_destination_id_values) {
    $revision_id = $old_destination_id_values ?
      reset($old_destination_id_values) :
      $row->getDestinationProperty($this->getKey('revision'));
    $entity = NULL;
    if (!empty($revision_id)) {
      /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
      $storage = $this->storage;
      if ($entity = $storage->loadRevision($revision_id)) {
        $entity->setNewRevision(FALSE);
      }
    }
    if ($entity === NULL) {
      $entity_id = $row->getDestinationProperty($this->getKey('id'));
      $entity = $this->storage->load($entity_id);

      // If we fail to load the original entity something is wrong and we need
      // to return immediately.
      if (!$entity) {
        return FALSE;
      }

      $entity->enforceIsNew(FALSE);
      $entity->setNewRevision(TRUE);
    }
    // We need to update the entity, so that the destination row IDs are
    // correct.
    $entity = $this->updateEntity($entity, $row);
    $entity->isDefaultRevision(FALSE);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function save(ContentEntityInterface $entity, array $old_destination_id_values = []) {
    $entity->setSyncing(TRUE);
    $entity->save();
    return [$entity->getRevisionId()];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [];

    $revision_key = $this->getKey('revision');
    if (!$revision_key) {
      throw new MigrateException(sprintf('The "%s" entity type does not support revisions.', $this->storage->getEntityTypeId()));
    }
    $ids[$revision_key] = $this->getDefinitionFromEntity($revision_key);

    if ($this->isTranslationDestination()) {
      $langcode_key = $this->getKey('langcode');
      if (!$langcode_key) {
        throw new MigrateException(sprintf('The "%s" entity type does not support translations.', $this->storage->getEntityTypeId()));
      }
      $ids[$langcode_key] = $this->getDefinitionFromEntity($langcode_key);
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getHighestId() {
    $values = $this->storage->getQuery()
      ->accessCheck(FALSE)
      ->allRevisions()
      ->sort($this->getKey('revision'), 'DESC')
      ->range(0, 1)
      ->execute();
    // The array keys are the revision IDs.
    // The array contains only one entry, so we can use key().
    return (int) key($values);
  }

}
