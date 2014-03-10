<?php

/**
 * @file
 * Definition of Drupal\comment\CommentViewBuilder.
 */

namespace Drupal\comment;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\field\FieldInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render controller for comments.
 */
class CommentViewBuilder extends EntityViewBuilder {

  /**
   * The field info service.
   *
   * @var \Drupal\field\FieldInfo
   */
  protected $fieldInfo;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The CSRF token manager service.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('field.info'),
      $container->get('csrf_token')
    );
  }

  /**
   * Constructs a new CommentViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\field\FieldInfo $field_info
   *   The field info service.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, FieldInfo $field_info, CsrfTokenGenerator $csrf_token) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->fieldInfo = $field_info;
    $this->csrfToken = $csrf_token;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityViewBuilder::buildContent().
   *
   * In addition to modifying the content key on entities, this implementation
   * will also set the comment entity key which all comments carry.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a comment is attached to an entity that no longer exists.
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    /** @var \Drupal\comment\CommentInterface[] $entities */
    $return = array();
    if (empty($entities)) {
      return $return;
    }

    // Pre-load associated users into cache to leverage multiple loading.
    $uids = array();
    foreach ($entities as $entity) {
      $uids[] = $entity->getOwnerId();
    }
    $this->entityManager->getStorageController('user')->loadMultiple(array_unique($uids));

    parent::buildContent($entities, $displays, $view_mode, $langcode);

    // Load all the entities that have comments attached.
    $commented_entity_ids = array();
    $commented_entities = array();
    foreach ($entities as $entity) {
      $commented_entity_ids[$entity->getCommentedEntityTypeId()][] = $entity->getCommentedEntityId();
    }
    // Load entities in bulk. This is more performant than using
    // $comment->getCommentedEntity() as we can load them in bulk per type.
    foreach ($commented_entity_ids as $entity_type => $entity_ids) {
      $commented_entities[$entity_type] = $this->entityManager->getStorageController($entity_type)->loadMultiple($entity_ids);
    }

    foreach ($entities as $entity) {
      if (isset($commented_entities[$entity->getCommentedEntityTypeId()][$entity->getCommentedEntityId()])) {
        $commented_entity = $commented_entities[$entity->getCommentedEntityTypeId()][$entity->getCommentedEntityId()];
      }
      else {
        throw new \InvalidArgumentException(t('Invalid entity for comment.'));
      }
      $entity->content['#entity'] = $entity;
      $entity->content['#theme'] = 'comment__' . $entity->getFieldId() . '__' . $commented_entity->bundle();
      $entity->content['links'] = array(
        '#type' => 'render_cache_placeholder',
        '#callback' => '\Drupal\comment\CommentViewBuilder::renderLinks',
        '#context' => array(
          'comment_entity_id' => $entity->id(),
          'view_mode' => $view_mode,
          'langcode' => $langcode,
          'commented_entity_type' => $commented_entity->getEntityTypeId(),
          'commented_entity_id' => $commented_entity->id(),
          'in_preview' => !empty($entity->in_preview),
        ),
      );

      if (!isset($entity->content['#attached'])) {
        $entity->content['#attached'] = array();
      }
      $entity->content['#attached']['library'][] = 'comment/drupal.comment-by-viewer';
      if ($this->moduleHandler->moduleExists('history') &&  \Drupal::currentUser()->isAuthenticated()) {
        $entity->content['#attached']['library'][] = 'comment/drupal.comment-new-indicator';

        // Embed the metadata for the comment "new" indicators on this node.
        $entity->content['#post_render_cache']['history_attach_timestamp'] = array(
          array('node_id' => $commented_entity->id()),
        );
      }
    }
  }

  /**
   * #post_render_cache callback; replaces the placeholder with comment links.
   *
   * Renders the links on a comment.
   *
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
  public static function renderLinks(array $context) {
    $links = array(
      '#theme' => 'links__comment',
      '#pre_render' => array('drupal_pre_render_links'),
      '#attributes' => array('class' => array('links', 'inline')),
    );

    if (!$context['in_preview']) {
      $entity = entity_load('comment', $context['comment_entity_id']);
      $commented_entity = entity_load($context['commented_entity_type'], $context['commented_entity_id']);

      $links['comment'] = self::buildLinks($entity, $commented_entity);

      // Allow other modules to alter the comment links.
      $hook_context = array(
        'view_mode' => $context['view_mode'],
        'langcode' => $context['langcode'],
        'commented_entity' => $commented_entity
      );
      \Drupal::moduleHandler()->alter('comment_links', $links, $entity, $hook_context);
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
  protected static function buildLinks(CommentInterface $entity, EntityInterface $commented_entity) {
    $links = array();
    $status = $commented_entity->get($entity->getFieldName())->status;

    $container = \Drupal::getContainer();

    if ($status == CommentItemInterface::OPEN) {
      if ($entity->access('delete')) {
        $links['comment-delete'] = array(
          'title' => t('Delete'),
          'href' => "comment/{$entity->id()}/delete",
          'html' => TRUE,
        );
      }

      if ($entity->access('update')) {
        $links['comment-edit'] = array(
          'title' => t('Edit'),
          'href' => "comment/{$entity->id()}/edit",
          'html' => TRUE,
        );
      }
      if ($entity->access('create')) {
        $links['comment-reply'] = array(
          'title' => t('Reply'),
          'href' => "comment/reply/{$entity->getCommentedEntityTypeId()}/{$entity->getCommentedEntityId()}/{$entity->getFieldName()}/{$entity->id()}",
          'html' => TRUE,
        );
      }
      if (!$entity->isPublished() && $entity->access('approve')) {
        $links['comment-approve'] = array(
          'title' => t('Approve'),
          'route_name' => 'comment.approve',
          'route_parameters' => array('comment' => $entity->id()),
          'html' => TRUE,
        );
      }
      if (empty($links)) {
        $links['comment-forbidden']['title'] = \Drupal::service('comment.manager')->forbiddenMessage($commented_entity, $entity->getFieldName());
        $links['comment-forbidden']['html'] = TRUE;
      }
    }

    // Add translations link for translation-enabled comment bundles.
    if ($container->get('module_handler')->moduleExists('content_translation') && content_translation_translate_access($entity)) {
      $links['comment-translations'] = array(
        'title' => t('Translate'),
        'href' => 'comment/' . $entity->id() . '/translations',
        'html' => TRUE,
      );
    }

    return array(
      '#theme' => 'links__comment__comment',
      // The "entity" property is specified to be present, so no need to
      // check.
      '#links' => $links,
      '#attributes' => array('class' => array('links', 'inline')),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $comment, EntityViewDisplayInterface $display, $view_mode, $langcode = NULL) {
    parent::alterBuild($build, $comment, $display, $view_mode, $langcode);
    if (empty($comment->in_preview)) {
      $prefix = '';
      $commented_entity = $comment->getCommentedEntity();
      $instance = $this->fieldInfo->getInstance($commented_entity->getEntityTypeId(), $commented_entity->bundle(), $comment->getFieldName());
      $is_threaded = isset($comment->divs)
        && $instance->getSetting('default_mode') == COMMENT_MODE_THREADED;

      // Add indentation div or close open divs as needed.
      if ($is_threaded) {
        $build['#attached']['css'][] = drupal_get_path('module', 'comment') . '/css/comment.theme.css';
        $prefix .= $comment->divs <= 0 ? str_repeat('</div>', abs($comment->divs)) : "\n" . '<div class="indented">';
      }

      // Add anchor for each comment.
      $prefix .= "<a id=\"comment-{$comment->id()}\"></a>\n";
      $build['#prefix'] = $prefix;

      // Close all open divs.
      if ($is_threaded && !empty($comment->divs_final)) {
        $build['#suffix'] = str_repeat('</div>', $comment->divs_final);
      }
    }
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
   * @return array $element
   *   The updated $element.
   */
  public static function attachNewCommentsLinkMetadata(array $element, array $context) {
    // Build "X new comments" link metadata.
    $new = (int)comment_num_new($context['entity_id'], $context['entity_type']);
    // Early-return if there are zero new comments for the current user.
    if ($new === 0) {
      return $element;
    }
    $entity = \Drupal::entityManager()
      ->getStorageController($context['entity_type'])
      ->load($context['entity_id']);
    $field_name = $context['field_name'];
    $query = comment_new_page_count($entity->{$field_name}->comment_count, $new, $entity);

    // Attach metadata.
    $element['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array(
        'comment' => array(
          'newCommentsLinks' => array(
            $context['entity_type'] => array(
              $context['field_name'] => array(
                $context['entity_id'] => array(
                  'new_comment_count' => (int)$new,
                  'first_new_comment_link' => \Drupal::urlGenerator()->generateFromPath('node/' . $entity->id(), array('query' => $query, 'fragment' => 'new')),
                )
              )
            ),
          )
        ),
      ),
    );

    return $element;
  }

}
