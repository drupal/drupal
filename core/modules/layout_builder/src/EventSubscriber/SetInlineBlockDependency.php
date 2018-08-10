<?php

namespace Drupal\layout_builder\EventSubscriber;

use Drupal\block_content\BlockContentEvents;
use Drupal\block_content\BlockContentInterface;
use Drupal\block_content\Event\BlockContentGetDependencyEvent;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_builder\InlineBlockUsage;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber that returns an access dependency for inline blocks.
 *
 * When used within the layout builder the access dependency for inline blocks
 * will be explicitly set but if access is evaluated outside of the layout
 * builder then the dependency may not have been set.
 *
 * A known example of when the access dependency will not have been set is when
 * determining 'view' or 'download' access to a file entity that is attached
 * to a content block via a field that is using the private file system. The
 * file access handler will evaluate access on the content block without setting
 * the dependency.
 *
 * @internal
 *
 * @see \Drupal\file\FileAccessControlHandler::checkAccess()
 * @see \Drupal\block_content\BlockContentAccessControlHandler::checkAccess()
 */
class SetInlineBlockDependency implements EventSubscriberInterface {

  use LayoutEntityHelperTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The inline block usage service.
   *
   * @var \Drupal\layout_builder\InlineBlockUsage
   */
  protected $usage;

  /**
   * Constructs SetInlineBlockDependency object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\layout_builder\InlineBlockUsage $usage
   *   The inline block usage service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, InlineBlockUsage $usage) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->usage = $usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      BlockContentEvents::BLOCK_CONTENT_GET_DEPENDENCY => 'onGetDependency',
    ];
  }

  /**
   * Handles the BlockContentEvents::INLINE_BLOCK_GET_DEPENDENCY event.
   *
   * @param \Drupal\block_content\Event\BlockContentGetDependencyEvent $event
   *   The event.
   */
  public function onGetDependency(BlockContentGetDependencyEvent $event) {
    if ($dependency = $this->getInlineBlockDependency($event->getBlockContentEntity())) {
      $event->setAccessDependency($dependency);
    }
  }

  /**
   * Get the access dependency of an inline block.
   *
   * If the content block is used in a layout for a non-revisionable entity the
   * entity will be returned.
   *
   * If the content block is used in a layout for a revisionable entity the
   * first revision that uses the block will be returned.
   *
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   The block content entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns the layout dependency.
   */
  protected function getInlineBlockDependency(BlockContentInterface $block_content) {
    $layout_entity_info = $this->usage->getUsage($block_content->id());
    if (empty($layout_entity_info)) {
      // If the block does not have usage information then we cannot set a
      // dependency. It may be used by another module besides layout builder.
      return NULL;
    }
    /** @var \Drupal\layout_builder\InlineBlockUsage $usage */
    $layout_entity_storage = $this->entityTypeManager->getStorage($layout_entity_info->layout_entity_type);
    $layout_entity = $layout_entity_storage->load($layout_entity_info->layout_entity_id);
    if ($this->isLayoutCompatibleEntity($layout_entity)) {
      if (!$layout_entity->getEntityType()->isRevisionable()) {
        // Check to see if this revision of the block was used in this entity.
        // Although the layout builder does not create new block revisions when
        // the layout entity does not support revisions another module may
        // have created new revisions for this block.
        if ($this->isBlockRevisionUsedInEntity($layout_entity, $block_content)) {
          return $layout_entity;
        }
      }
      else {
        foreach ($this->getEntityRevisionIds($layout_entity) as $revision_id) {
          $revision = $layout_entity_storage->loadRevision($revision_id);
          if ($this->isBlockRevisionUsedInEntity($revision, $block_content)) {
            return $revision;
          }
        }
      }

    }
    return NULL;
  }

  /**
   * Determines if a block content revision is used in an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $layout_entity
   *   The layout entity.
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   The block content revision.
   *
   * @return bool
   *   TRUE if the block content revision is used as an inline block in the
   *   layout entity.
   */
  protected function isBlockRevisionUsedInEntity(EntityInterface $layout_entity, BlockContentInterface $block_content) {
    $sections_blocks_revision_ids = $this->getInlineBlockRevisionIdsInSections($this->getEntitySections($layout_entity));
    return in_array($block_content->getRevisionId(), $sections_blocks_revision_ids);
  }

  /**
   * Gets the revision IDs for an entity.
   *
   * @todo Move this logic to \Drupal\Core\Entity\Sql\SqlContentEntityStorage in
   * https://www.drupal.org/project/drupal/issues/2986027.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return int[]
   *   The revision IDs.
   */
  protected function getEntityRevisionIds(EntityInterface $entity) {
    $entity_type = $this->entityTypeManager->getDefinition($entity->getEntityTypeId());
    if ($revision_table = $entity_type->getRevisionTable()) {
      $query = $this->database->select($revision_table);
      $query->condition($entity_type->getKey('id'), $entity->id());
      $query->fields($revision_table, [$entity_type->getKey('revision')]);
      $query->orderBy($entity_type->getKey('revision'), 'DESC');
      return $query->execute()->fetchCol();
    }
    return [];
  }

}
