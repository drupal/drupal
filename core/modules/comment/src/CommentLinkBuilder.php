<?php

/**
 * @file
 * Contains \Drupal\comment\CommentLinkBuilder.
 */

namespace Drupal\comment;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Defines a class for building markup for comment links on a commented entity.
 *
 * Comment links include 'login to post new comment', 'add new comment' etc.
 */
class CommentLinkBuilder implements CommentLinkBuilderInterface {

  use StringTranslationTrait;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Comment manager service.
   *
   * @var \Drupal\comment\CommentManagerInterface
   */
  protected $commentManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new CommentLinkBuilder object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   Comment manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   String translation service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(AccountInterface $current_user, CommentManagerInterface $comment_manager, ModuleHandlerInterface $module_handler, TranslationInterface $string_translation, EntityManagerInterface $entity_manager) {
    $this->currentUser = $current_user;
    $this->commentManager = $comment_manager;
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildCommentedEntityLinks(FieldableEntityInterface $entity, array &$context) {
    $entity_links = array();
    $view_mode = $context['view_mode'];
    if ($view_mode == 'search_index' || $view_mode == 'search_result' || $view_mode == 'print' || $view_mode == 'rss') {
      // Do not add any links if the entity is displayed for:
      // - search indexing.
      // - constructing a search result excerpt.
      // - print.
      // - rss.
      return array();
    }

    $fields = $this->commentManager->getFields($entity->getEntityTypeId());
    foreach ($fields as $field_name => $detail) {
      // Skip fields that the entity does not have.
      if (!$entity->hasField($field_name)) {
        continue;
      }
      $links = array();
      $commenting_status = $entity->get($field_name)->status;
      if ($commenting_status != CommentItemInterface::HIDDEN) {
        // Entity has commenting status open or closed.
        $field_definition = $entity->getFieldDefinition($field_name);
        if ($view_mode == 'teaser') {
          // Teaser view: display the number of comments that have been posted,
          // or a link to add new comments if the user has permission, the
          // entity is open to new comments, and there currently are none.
          if ($this->currentUser->hasPermission('access comments')) {
            if (!empty($entity->get($field_name)->comment_count)) {
              $links['comment-comments'] = array(
                'title' => $this->formatPlural($entity->get($field_name)->comment_count, '1 comment', '@count comments'),
                'attributes' => array('title' => $this->t('Jump to the first comment.')),
                'fragment' => 'comments',
                'url' => $entity->urlInfo(),
              );
              if ($this->moduleHandler->moduleExists('history')) {
                $links['comment-new-comments'] = array(
                  'title' => '',
                  'url' => Url::fromRoute('<current>'),
                  'attributes' => array(
                    'class' => 'hidden',
                    'title' => $this->t('Jump to the first new comment.'),
                    'data-history-node-last-comment-timestamp' => $entity->get($field_name)->last_comment_timestamp,
                    'data-history-node-field-name' => $field_name,
                  ),
                );
              }
            }
          }
          // Provide a link to new comment form.
          if ($commenting_status == CommentItemInterface::OPEN) {
            $comment_form_location = $field_definition->getSetting('form_location');
            if ($this->currentUser->hasPermission('post comments')) {
              $links['comment-add'] = array(
                'title' => $this->t('Add new comment'),
                'language' => $entity->language(),
                'attributes' => array('title' => $this->t('Share your thoughts and opinions.')),
                'fragment' => 'comment-form',
              );
              if ($comment_form_location == CommentItemInterface::FORM_SEPARATE_PAGE) {
                $links['comment-add']['url'] = Url::fromRoute('comment.reply', [
                  'entity_type' => $entity->getEntityTypeId(),
                  'entity' => $entity->id(),
                  'field_name' => $field_name,
                ]);
              }
              else {
                $links['comment-add'] += ['url' => $entity->urlInfo()];
              }
            }
            elseif ($this->currentUser->isAnonymous()) {
              $links['comment-forbidden'] = array(
                'title' => $this->commentManager->forbiddenMessage($entity, $field_name),
              );
            }
          }
        }
        else {
          // Entity in other view modes: add a "post comment" link if the user
          // is allowed to post comments and if this entity is allowing new
          // comments.
          if ($commenting_status == CommentItemInterface::OPEN) {
            $comment_form_location = $field_definition->getSetting('form_location');
            if ($this->currentUser->hasPermission('post comments')) {
              // Show the "post comment" link if the form is on another page, or
              // if there are existing comments that the link will skip past.
              if ($comment_form_location == CommentItemInterface::FORM_SEPARATE_PAGE || (!empty($entity->get($field_name)->comment_count) && $this->currentUser->hasPermission('access comments'))) {
                $links['comment-add'] = array(
                  'title' => $this->t('Add new comment'),
                  'attributes' => array('title' => $this->t('Share your thoughts and opinions.')),
                  'fragment' => 'comment-form',
                );
                if ($comment_form_location == CommentItemInterface::FORM_SEPARATE_PAGE) {
                  $links['comment-add']['url'] = Url::fromRoute('comment.reply', [
                    'entity_type' => $entity->getEntityTypeId(),
                    'entity' => $entity->id(),
                    'field_name' => $field_name,
                  ]);
                }
                else {
                  $links['comment-add']['url'] = $entity->urlInfo();
                }
              }
            }
            elseif ($this->currentUser->isAnonymous()) {
              $links['comment-forbidden'] = array(
                'title' => $this->commentManager->forbiddenMessage($entity, $field_name),
              );
            }
          }
        }
      }

      if (!empty($links)) {
        $entity_links['comment__' . $field_name] = array(
          '#theme' => 'links__entity__comment__' . $field_name,
          '#links' => $links,
          '#attributes' => array('class' => array('links', 'inline')),
        );
        if ($view_mode == 'teaser' && $this->moduleHandler->moduleExists('history') && $this->currentUser->isAuthenticated()) {
          $entity_links['comment__' . $field_name]['#cache']['contexts'][] = 'user';
          $entity_links['comment__' . $field_name]['#attached']['library'][] = 'comment/drupal.node-new-comments-link';
          // Embed the metadata for the "X new comments" link (if any) on this
          // entity.
          $entity_links['comment__' . $field_name]['#attached']['drupalSettings']['history']['lastReadTimestamps'][$entity->id()] = (int) history_read($entity->id());
          $new_comments = $this->commentManager->getCountNewComments($entity);
          if ($new_comments > 0) {
            $page_number = $this->entityManager
              ->getStorage('comment')
              ->getNewCommentPageNumber($entity->{$field_name}->comment_count, $new_comments, $entity);
            $query = $page_number ? ['page' => $page_number] : NULL;
            $value = [
              'new_comment_count' => (int) $new_comments,
              'first_new_comment_link' => $entity->url('canonical', [
                'query' => $query,
                'fragment' => 'new',
              ]),
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
