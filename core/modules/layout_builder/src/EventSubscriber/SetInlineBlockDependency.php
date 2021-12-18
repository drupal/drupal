<?php

namespace Drupal\layout_builder\EventSubscriber;

use Drupal\block_content\BlockContentEvents;
use Drupal\block_content\BlockContentInterface;
use Drupal\block_content\Event\BlockContentGetDependencyEvent;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_builder\InlineBlockUsageInterface;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
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
 *   Tagged services are internal.
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
   * @var \Drupal\layout_builder\InlineBlockUsageInterface
   */
  protected $usage;

  /**
   * Constructs SetInlineBlockDependency object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\layout_builder\InlineBlockUsageInterface $usage
   *   The inline block usage service.
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager
   *   The section storage manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, InlineBlockUsageInterface $usage, SectionStorageManagerInterface $section_storage_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->usage = $usage;
    $this->sectionStorageManager = $section_storage_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
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
   * If the block is used in an entity that entity will be returned as the
   * dependency.
   *
   * For revisionable entities the entity will only be returned if it is used in
   * the latest revision of the entity. For inline blocks that are not used in
   * the latest revision but are used in a previous revision the entity will not
   * be returned because calling
   * \Drupal\Core\Access\AccessibleInterface::access() will only check access on
   * the latest revision. Therefore if the previous revision of the entity was
   * returned as the dependency access would be granted to inline block
   * regardless of whether the user has access to the revision in which the
   * inline block was used.
   *
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   The block content entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns the layout dependency.
   *
   * @see \Drupal\block_content\BlockContentAccessControlHandler::checkAccess()
   * @see \Drupal\layout_builder\EventSubscriber\BlockComponentRenderArray::onBuildRender()
   */
  protected function getInlineBlockDependency(BlockContentInterface $block_content) {
    $layout_entity_info = $this->usage->getUsage($block_content->id());
    if (empty($layout_entity_info)) {
      // If the block does not have usage information then we cannot set a
      // dependency. It may be used by another module besides layout builder.
      return NULL;
    }
    $layout_entity_storage = $this->entityTypeManager->getStorage($layout_entity_info->layout_entity_type);
    $layout_entity = $layout_entity_storage->load($layout_entity_info->layout_entity_id);
    if ($this->isLayoutCompatibleEntity($layout_entity)) {
      if ($this->isBlockRevisionUsedInEntity($layout_entity, $block_content)) {
        return $layout_entity;
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

}
