<?php

declare(strict_types=1);

namespace Drupal\history;

use Drupal\comment\CommentLinkBuilderInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Adds history functionality to comment links on nodes.
 */
class HistoryCommentLinkBuilder implements CommentLinkBuilderInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new HistoryCommentLinkBuilder object.
   */
  public function __construct(
    protected CommentLinkBuilderInterface $inner,
    protected CommentManagerInterface $commentManager,
    protected AccountInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected HistoryManager $historyManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function buildCommentedEntityLinks(FieldableEntityInterface $entity, array &$context): array {
    $entity_links = $this->inner->buildCommentedEntityLinks($entity, $context);
    if ($context['view_mode'] !== 'teaser') {
      return $entity_links;
    }
    $fields = $this->commentManager->getFields($entity->getEntityTypeId());
    foreach ($fields as $field_name => $detail) {
      // Skip fields that the entity does not have.
      if (!$entity->hasField($field_name)) {
        continue;
      }
      $links = [];
      $commenting_status = $entity->get($field_name)->status;
      if ($commenting_status != CommentItemInterface::HIDDEN) {
        // Entity has commenting status open or closed.
        if ($this->currentUser->hasPermission('access comments')) {
          if (!empty($entity->get($field_name)->comment_count)) {
            $links['comment-new-comments'] = [
              'title' => '',
              'url' => Url::fromRoute('<current>'),
              'attributes' => [
                'class' => 'hidden',
                'title' => new TranslatableMarkup('Jump to the first new comment.'),
                'data-history-node-last-comment-timestamp' => $entity->get($field_name)->last_comment_timestamp,
                'data-history-node-field-name' => $field_name,
              ],
            ];
          }
        }
      }
      if (!empty($links)) {
        // @todo do we have the same order as before?
        $entity_links['comment__' . $field_name]['#links'] += $links;
        if ($this->currentUser->isAuthenticated()) {
          $entity_links['comment__' . $field_name]['#cache']['contexts'][] = 'user';
          $entity_links['comment__' . $field_name]['#attached']['library'][] = 'history/drupal.node-new-comments-link';
          // Embed the metadata for the "X new comments" link (if any) on this
          // entity.
          $entity_links['comment__' . $field_name]['#attached']['drupalSettings']['history']['lastReadTimestamps'][$entity->id()] = history_read($entity->id());
          $new_comments = $this->historyManager->getCountNewComments($entity);
          if ($new_comments > 0) {
            $page_number = $this->entityTypeManager
              ->getStorage('comment')
              ->getNewCommentPageNumber($entity->{$field_name}->comment_count, $new_comments, $entity, $field_name);
            $query = $page_number ? ['page' => $page_number] : NULL;
            $value = [
              'new_comment_count' => (int) $new_comments,
              'first_new_comment_link' => $entity->toUrl('canonical', [
                'query' => $query,
                'fragment' => 'new',
              ])->toString(),
            ];
            $parents = ['comment', 'newCommentsLinks', $entity->getEntityTypeId(), $field_name, $entity->id()];
            NestedArray::setValue($entity_links['comment__' . $field_name]['#attached']['drupalSettings'], $parents, $value);
          }
        }
      }
    }
    return $entity_links;
  }

}
