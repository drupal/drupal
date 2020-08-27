<?php

namespace Drupal\comment;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Element\Link;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Defines a service for comment #lazy_builder callbacks.
 */
class CommentLazyBuilders implements TrustedCallbackInterface {
  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['entityManager' => 'entity.manager'];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new CommentLazyBuilders object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   The comment manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, AccountInterface $current_user, CommentManagerInterface $comment_manager, ModuleHandlerInterface $module_handler, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->currentUser = $current_user;
    $this->commentManager = $comment_manager;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
  }

  /**
   * #lazy_builder callback; builds the comment form.
   *
   * @param string $commented_entity_type_id
   *   The commented entity type ID.
   * @param string $commented_entity_id
   *   The commented entity ID.
   * @param string $field_name
   *   The comment field name.
   * @param string $comment_type_id
   *   The comment type ID.
   *
   * @return array
   *   A renderable array containing the comment form.
   */
  public function renderForm($commented_entity_type_id, $commented_entity_id, $field_name, $comment_type_id) {
    $values = [
      'entity_type' => $commented_entity_type_id,
      'entity_id' => $commented_entity_id,
      'field_name' => $field_name,
      'comment_type' => $comment_type_id,
      'pid' => NULL,
    ];
    $comment = $this->entityTypeManager->getStorage('comment')->create($values);
    return $this->entityFormBuilder->getForm($comment);
  }

  /**
   * #lazy_builder callback; builds a comment's links.
   *
   * @param string $comment_entity_id
   *   The comment entity ID.
   * @param string $view_mode
   *   The view mode in which the comment entity is being viewed.
   * @param string $langcode
   *   The language in which the comment entity is being viewed.
   * @param bool $is_in_preview
   *   Whether the comment is currently being previewed.
   *
   * @return array
   *   A renderable array representing the comment links.
   */
  public function renderLinks($comment_entity_id, $view_mode, $langcode, $is_in_preview) {
    $links = [
      '#theme' => 'links__comment',
      '#pre_render' => [[Link::class, 'preRenderLinks']],
      '#attributes' => ['class' => ['links', 'inline']],
    ];

    if (!$is_in_preview) {
      /** @var \Drupal\comment\CommentInterface $entity */
      $entity = $this->entityTypeManager->getStorage('comment')->load($comment_entity_id);
      if ($commented_entity = $entity->getCommentedEntity()) {
        $links['comment'] = $this->buildLinks($entity, $commented_entity);
      }

      // Allow other modules to alter the comment links.
      $hook_context = [
        'view_mode' => $view_mode,
        'langcode' => $langcode,
        'commented_entity' => $commented_entity,
      ];
      $this->moduleHandler->alter('comment_links', $links, $entity, $hook_context);
    }
    return $links;
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
    $links = [];
    $status = $commented_entity->get($entity->getFieldName())->status;

    if ($status == CommentItemInterface::OPEN) {
      if ($entity->access('delete')) {
        $links['comment-delete'] = [
          'title' => t('Delete'),
          'url' => $entity->toUrl('delete-form'),
        ];
      }

      if ($entity->access('update')) {
        $links['comment-edit'] = [
          'title' => t('Edit'),
          'url' => $entity->toUrl('edit-form'),
        ];
      }
      if ($entity->access('create')) {
        $links['comment-reply'] = [
          'title' => t('Reply'),
          'url' => Url::fromRoute('comment.reply', [
            'entity_type' => $entity->getCommentedEntityTypeId(),
            'entity' => $entity->getCommentedEntityId(),
            'field_name' => $entity->getFieldName(),
            'pid' => $entity->id(),
          ]),
        ];
      }
      if (!$entity->isPublished() && $entity->access('approve')) {
        $links['comment-approve'] = [
          'title' => t('Approve'),
          'url' => Url::fromRoute('comment.approve', ['comment' => $entity->id()]),
        ];
      }
      if (empty($links) && $this->currentUser->isAnonymous()) {
        $links['comment-forbidden']['title'] = $this->commentManager->forbiddenMessage($commented_entity, $entity->getFieldName());
      }
    }

    // Add translations link for translation-enabled comment bundles.
    if ($this->moduleHandler->moduleExists('content_translation') && $this->access($entity)->isAllowed()) {
      $links['comment-translations'] = [
        'title' => t('Translate'),
        'url' => $entity->toUrl('drupal:content-translation-overview'),
      ];
    }

    return [
      '#theme' => 'links__comment__comment',
      // The "entity" property is specified to be present, so no need to check.
      '#links' => $links,
      '#attributes' => ['class' => ['links', 'inline']],
    ];
  }

  /**
   * Wraps content_translation_translate_access.
   */
  protected function access(EntityInterface $entity) {
    return content_translation_translate_access($entity);
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['renderLinks', 'renderForm'];
  }

}
