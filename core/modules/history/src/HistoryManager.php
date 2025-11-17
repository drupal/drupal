<?php

declare(strict_types=1);

namespace Drupal\history;

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Helper functions for history module.
 */
class HistoryManager {

  /**
   * Constructs a new HistoryManager object.
   */
  public function __construct(
    protected AccountInterface $currentUser,
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Returns the number of new comments on a given entity for the current user.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to which the comments are attached to.
   * @param string|null $field_name
   *   (optional) The field_name to count comments for. Defaults to any field.
   * @param int $timestamp
   *   (optional) Time to count from. Defaults to time of last user access the
   *   entity.
   *
   * @return int|false
   *   The number of new comments or FALSE if the user is not authenticated or
   *   if the Comment module is not installed.
   */
  public function getCountNewComments(EntityInterface $entity, ?string $field_name = NULL, int $timestamp = 0): int|false {
    if ($this->currentUser->isAuthenticated() && $this->moduleHandler->moduleExists('comment')) {
      // Retrieve the timestamp at which the current user last viewed this
      // entity.
      if (!$timestamp) {
        if ($entity->getEntityTypeId() == 'node') {
          $timestamp = history_read($entity->id());
        }
        else {
          $function = $entity->getEntityTypeId() . '_last_viewed';
          if (function_exists($function)) {
            $timestamp = $function($entity->id());
          }
          else {
            // Default to 30 days ago.
            // @todo Remove this else branch when we have a generic
            //   HistoryRepository service in https://www.drupal.org/i/3267011.
            $timestamp = COMMENT_NEW_LIMIT;
          }
        }
      }
      $timestamp = ($timestamp > HISTORY_READ_LIMIT ? $timestamp : HISTORY_READ_LIMIT);

      // Use the timestamp to retrieve the number of new comments.
      $query = $this->entityTypeManager->getStorage('comment')->getQuery()
        ->accessCheck(TRUE)
        ->condition('entity_type', $entity->getEntityTypeId())
        ->condition('entity_id', $entity->id())
        ->condition('created', $timestamp, '>')
        ->condition('status', CommentInterface::PUBLISHED);
      if ($field_name) {
        // Limit to a particular field.
        $query->condition('field_name', $field_name);
      }

      return $query->count()->execute();
    }
    return FALSE;
  }

}
