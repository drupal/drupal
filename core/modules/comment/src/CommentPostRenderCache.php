<?php

/**
 * @file
 * Contains \Drupal\comment\CommentPostRenderCache.
 */

namespace Drupal\comment;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Renderer;

/**
 * Defines a service for comment post render cache callbacks.
 */
class CommentPostRenderCache {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Comment manager service.
   *
   * @var \Drupal\comment\CommentManagerInterface
   */
  protected $commentManager;

  /**
   * Current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new CommentPostRenderCache object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   The comment manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityFormBuilderInterface $entity_form_builder, AccountInterface $current_user, CommentManagerInterface $comment_manager, ModuleHandlerInterface $module_handler, Renderer $renderer) {
    $this->entityManager = $entity_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->currentUser = $current_user;
    $this->commentManager = $comment_manager;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
  }

  /**
   * #post_render_cache callback; replaces placeholder with comment form.
   *
   * @param array $element
   *   The renderable array that contains the to be replaced placeholder.
   * @param array $context
   *   An array with the following keys:
   *   - entity_type: an entity type
   *   - entity_id: an entity ID
   *   - field_name: a comment field name
   *   - comment_type: the comment type
   *
   * @return array
   *   A renderable array containing the comment form.
   */
  public function renderForm(array $element, array $context) {
    $values = array(
      'entity_type' => $context['entity_type'],
      'entity_id' => $context['entity_id'],
      'field_name' => $context['field_name'],
      'comment_type' => $context['comment_type'],
      'pid' => NULL,
    );
    $comment = $this->entityManager->getStorage('comment')->create($values);
    $form = $this->entityFormBuilder->getForm($comment);
    $markup = $this->renderer->render($form);

    $callback = 'comment.post_render_cache:renderForm';
    $placeholder = $this->generatePlaceholder($callback, $context);
    $element['#markup'] = str_replace($placeholder, $markup, $element['#markup']);
    $element = $this->renderer->mergeBubbleableMetadata($element, $form);

    return $element;
  }

  /**
   * #post_render_cache callback; replaces the placeholder with comment links.
   *
   * Renders the links on a comment.
   *
   * @param array $element
   *   The renderable array that contains the to be replaced placeholder.
   * @param array $context
   *   An array with the following keys:
   *   - comment_entity_id: a comment entity ID
   *   - view_mode: the view mode in which the comment entity is being viewed
   *   - langcode: in which language the comment entity is being viewed
   *   - commented_entity_type: the entity type to which the comment is attached
   *   - commented_entity_id: the entity ID to which the comment is attached
   *   - in_preview: whether the comment is currently being previewed
   *
   * @return array
   *   A renderable array representing the comment links.
   */
  public function renderLinks(array $element, array $context) {
    $callback = 'comment.post_render_cache:renderLinks';
    $placeholder = $this->generatePlaceholder($callback, $context);
    $links = array(
      '#theme' => 'links__comment',
      '#pre_render' => array('drupal_pre_render_links'),
      '#attributes' => array('class' => array('links', 'inline')),
    );

    if (!$context['in_preview']) {
      /** @var \Drupal\comment\CommentInterface $entity */
      $entity = $this->entityManager->getStorage('comment')->load($context['comment_entity_id']);
      $commented_entity = $entity->getCommentedEntity();

      $links['comment'] = $this->buildLinks($entity, $commented_entity);

      // Allow other modules to alter the comment links.
      $hook_context = array(
        'view_mode' => $context['view_mode'],
        'langcode' => $context['langcode'],
        'commented_entity' => $commented_entity,
      );
      $this->moduleHandler->alter('comment_links', $links, $entity, $hook_context);
    }
    $markup = $this->renderer->render($links);
    $element['#markup'] = str_replace($placeholder, $markup, $element['#markup']);

    return $element;
  }

  /**
   * Build the default links (reply, edit, delete …) for a comment.
   *
   * @param \Drupal\comment\CommentInterface $entity
   *   The comment object.
   * @param \Drupal\Core\Entity\EntityInterface $commented_entity
   *   The entity to which the comment is attached.
   *
   * @return array
   *   An array that can be processed by drupal_pre_render_links().
   */
  protected function buildLinks(CommentInterface $entity, EntityInterface $commented_entity) {
    $links = array();
    $status = $commented_entity->get($entity->getFieldName())->status;

    if ($status == CommentItemInterface::OPEN) {
      if ($entity->access('delete')) {
        $links['comment-delete'] = array(
          'title' => t('Delete'),
          'url' => $entity->urlInfo('delete-form'),
        );
      }

      if ($entity->access('update')) {
        $links['comment-edit'] = array(
          'title' => t('Edit'),
          'url' => $entity->urlInfo('edit-form'),
        );
      }
      if ($entity->access('create')) {
        $links['comment-reply'] = array(
          'title' => t('Reply'),
          'url' => Url::fromRoute('comment.reply', [
            'entity_type' => $entity->getCommentedEntityTypeId(),
            'entity' => $entity->getCommentedEntityId(),
            'field_name' => $entity->getFieldName(),
            'pid' => $entity->id(),
          ]),
        );
      }
      if (!$entity->isPublished() && $entity->access('approve')) {
        $links['comment-approve'] = array(
          'title' => t('Approve'),
          'url' => Url::fromRoute('comment.approve', ['comment' => $entity->id()]),
        );
      }
      if (empty($links) && $this->currentUser->isAnonymous()) {
        $links['comment-forbidden']['title'] = $this->commentManager->forbiddenMessage($commented_entity, $entity->getFieldName());
      }
    }

    // Add translations link for translation-enabled comment bundles.
    if ($this->moduleHandler->moduleExists('content_translation') && $this->access($entity)->isAllowed()) {
      $links['comment-translations'] = array(
        'title' => t('Translate'),
        'url' => $entity->urlInfo('drupal:content-translation-overview'),
      );
    }

    return array(
      '#theme' => 'links__comment__comment',
      // The "entity" property is specified to be present, so no need to check.
      '#links' => $links,
      '#attributes' => array('class' => array('links', 'inline')),
    );
  }

  /**
   * #post_render_cache callback; attaches "X new comments" link metadata.
   *
   * @param array $element
   *   A render array with the following keys:
   *   - #markup
   *   - #attached
   * @param array $context
   *   An array with the following keys:
   *   - entity_type: an entity type
   *   - entity_id: an entity ID
   *   - field_name: a comment field name
   *
   * @return array
   *   The updated $element.
   */
  public function attachNewCommentsLinkMetadata(array $element, array $context) {
    $entity = $this->entityManager
      ->getStorage($context['entity_type'])
      ->load($context['entity_id']);
    // Build "X new comments" link metadata.
    $new = $this->commentManager
      ->getCountNewComments($entity);
    // Early-return if there are zero new comments for the current user.
    if ($new === 0) {
      return $element;
    }

    $field_name = $context['field_name'];
    $page_number = $this->entityManager
      ->getStorage('comment')
      ->getNewCommentPageNumber($entity->{$field_name}->comment_count, $new, $entity);
    $query = $page_number ? array('page' => $page_number) : NULL;

    // Attach metadata.
    $element['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array(
        'comment' => array(
          'newCommentsLinks' => array(
            $context['entity_type'] => array(
              $context['field_name'] => array(
                $context['entity_id'] => array(
                  'new_comment_count' => (int) $new,
                  'first_new_comment_link' => $entity->url('canonical', [
                    'query' => $query,
                    'fragment' => 'new',
                  ]),
                ),
              ),
            ),
          ),
        ),
      ),
    );
    return $element;
  }

  /**
   * Wraps drupal_render_cache_generate_placeholder().
   */
  protected function generatePlaceholder($callback, $context) {
    return drupal_render_cache_generate_placeholder($callback, $context);
  }

  /**
   * Wraps content_translation_translate_access.
   */
  protected function access(EntityInterface $entity) {
    return content_translation_translate_access($entity);
  }

}
