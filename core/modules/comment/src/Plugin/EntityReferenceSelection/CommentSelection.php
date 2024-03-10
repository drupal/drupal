<?php

namespace Drupal\comment\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Attribute\EntityReferenceSelection;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\comment\CommentInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides specific access control for the comment entity type.
 */
#[EntityReferenceSelection(
  id: "default:comment",
  label: new TranslatableMarkup("Comment selection"),
  entity_types: ["comment"],
  group: "default",
  weight: 1
)]
class CommentSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Adding the 'comment_access' tag is sadly insufficient for comments:
    // core requires us to also know about the concept of 'published' and
    // 'unpublished'.
    if (!$this->currentUser->hasPermission('administer comments')) {
      $query->condition('status', CommentInterface::PUBLISHED);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $comment = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // In order to create a referenceable comment, it needs to published.
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment->setPublished();

    return $comment;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    if (!$this->currentUser->hasPermission('administer comments')) {
      $entities = array_filter($entities, function ($comment) {
        /** @var \Drupal\comment\CommentInterface $comment */
        return $comment->isPublished();
      });
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $result = [];
    if ($ids) {
      $target_type = $this->configuration['target_type'];
      $entity_type = $this->entityTypeManager->getDefinition($target_type);
      $query = $this->buildEntityQuery();
      // Mirror the conditions checked in buildEntityQuery().
      if (!$this->currentUser->hasPermission('administer comments')) {
        $query->condition('status', 1);
      }
      $result = $query
        ->condition($entity_type->getKey('id'), $ids, 'IN')
        ->execute();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {
    parent::entityQueryAlter($query);

    $tables = $query->getTables();
    $data_table = 'comment_field_data';
    if (!isset($tables['comment_field_data']['alias'])) {
      // If no conditions join against the comment data table, it should be
      // joined manually to allow node access processing.
      $query->innerJoin($data_table, NULL, "[base_table].[cid] = [$data_table].[cid] AND [$data_table].[default_langcode] = 1");
    }

    // Historically, comments were always linked to 'node' entities, but that is
    // no longer the case, as the 'node' module might not even be enabled.
    // Comments can now be linked to any entity and they can also be referenced
    // by other entities, so we won't have a single table to join to. That
    // actually means that we can no longer optimize the query on those cases.
    // However, the most common case remains to be comment replies, and in this
    // case, we can get the host entity type if the 'entity' value is present
    // and perform the extra joins and alterations needed.
    $comment = $this->getConfiguration()['entity'];
    if ($comment instanceof CommentInterface) {
      $host_entity_type_id = $comment->getCommentedEntityTypeId();

      /** @var \Drupal\Core\Entity\EntityTypeInterface $host_entity_type */
      $host_entity_type = $this->entityTypeManager->getDefinition($host_entity_type_id);
      $host_entity_field_data_table = $host_entity_type->getDataTable();

      // Not all entities have a data table, so check first.
      if ($host_entity_field_data_table) {
        $id_key = $host_entity_type->getKey('id');

        // The Comment module doesn't implement per-comment access, so it
        // checks instead that the user has access to the host entity.
        $entity_alias = $query->innerJoin($host_entity_field_data_table, 'n', "[%alias].[$id_key] = [$data_table].[entity_id] AND [$data_table].[entity_type] = '$host_entity_type_id'");
        // Pass the query to the entity access control.
        $this->reAlterQuery($query, $host_entity_type_id . '_access', $entity_alias);

        // Additional checks for "node" entities.
        if ($host_entity_type_id === 'node') {
          // Passing the query to node_query_node_access_alter() is sadly
          // insufficient for nodes.
          // @see \Drupal\node\Plugin\EntityReferenceSelection\NodeSelection::buildEntityQuery()
          if (!$this->currentUser->hasPermission('bypass node access') && !$this->moduleHandler->hasImplementations('node_grants')) {
            $query->condition($entity_alias . '.status', 1);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->getConfiguration()['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      // Additional access check as comments might be attached to entities
      // which the current user does not have access to.
      if ($entity->access('view', $this->currentUser)) {
        $bundle = $entity->bundle();
        $options[$bundle][$entity_id] = Html::escape($this->entityRepository->getTranslationFromContext($entity)->label() ?? '');
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    $options = $this->getReferenceableEntities($match, $match_operator);
    return count($options, COUNT_RECURSIVE) - count($options);
  }

}
