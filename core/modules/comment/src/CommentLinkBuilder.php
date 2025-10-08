<?php

namespace Drupal\comment;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Defines a class for building markup for comment links on a commented entity.
 *
 * Comment links include 'log in to post new comment', 'add new comment' etc.
 */
class CommentLinkBuilder implements CommentLinkBuilderInterface {

  use DeprecatedServicePropertyTrait;
  use StringTranslationTrait;

  /**
   * Deprecated service properties.
   *
   * @see https://www.drupal.org/node/3544527
   */
  protected array $deprecatedProperties = [
    'moduleHandler' => 'module_handler',
    'entityTypeManager' => 'entity_type.manager',
  ];

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
   * Constructs a new CommentLinkBuilder object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   Comment manager service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface|\Drupal\Core\Extension\ModuleHandlerInterface $string_translation
   *   String translation service.
   */
  public function __construct(
    AccountInterface $current_user,
    CommentManagerInterface $comment_manager,
    #[Autowire(service: TranslationInterface::class)]
    TranslationInterface|ModuleHandlerInterface $string_translation,
  ) {
    if ($string_translation instanceof ModuleHandlerInterface) {
      @trigger_error('Passing the $module_handler argument to ' . __METHOD__ . '() is deprecated in drupal:11.3.0 and will be removed in drupal:12.0.0. See https://www.drupal.org/node/3544527', E_USER_DEPRECATED);
      $string_translation = \Drupal::service(TranslationInterface::class);
    }
    if (array_any(func_get_args(), fn ($arg) => $arg instanceof EntityTypeManagerInterface)) {
      @trigger_error('Passing the $entity_type_manager argument to ' . __METHOD__ . '() is deprecated in drupal:11.3.0 and will be removed in drupal:12.0.0. See https://www.drupal.org/node/3544527', E_USER_DEPRECATED);
    }
    $this->currentUser = $current_user;
    $this->commentManager = $comment_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function buildCommentedEntityLinks(FieldableEntityInterface $entity, array &$context) {
    $entity_links = [];
    $view_mode = $context['view_mode'];
    if ($view_mode == 'search_index' || $view_mode == 'search_result' || $view_mode == 'print' || $view_mode == 'rss') {
      // Do not add any links if the entity is displayed for:
      // - search indexing.
      // - constructing a search result excerpt.
      // - print.
      // - rss.
      return [];
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
        $field_definition = $entity->getFieldDefinition($field_name);
        if ($view_mode == 'teaser') {
          // Teaser view: display the number of comments that have been posted,
          // or a link to add new comments if the user has permission, the
          // entity is open to new comments, and there currently are none.
          if ($this->currentUser->hasPermission('access comments')) {
            if (!empty($entity->get($field_name)->comment_count)) {
              $links['comment-comments'] = [
                'title' => $this->formatPlural($entity->get($field_name)->comment_count, '1 comment', '@count comments'),
                'attributes' => ['title' => $this->t('Jump to the first comment.')],
                'fragment' => 'comments',
                'url' => $entity->toUrl(),
              ];
            }
          }
          // Provide a link to new comment form.
          if ($commenting_status == CommentItemInterface::OPEN) {
            $comment_form_location = $field_definition->getSetting('form_location');
            if ($this->currentUser->hasPermission('post comments')) {
              $links['comment-add'] = [
                'title' => $this->t('Add new comment'),
                'language' => $entity->language(),
                'attributes' => ['title' => $this->t('Share your thoughts and opinions.')],
                'fragment' => 'comment-form',
              ];
              if ($comment_form_location == CommentItemInterface::FORM_SEPARATE_PAGE) {
                $links['comment-add']['url'] = Url::fromRoute('comment.reply', [
                  'entity_type' => $entity->getEntityTypeId(),
                  'entity' => $entity->id(),
                  'field_name' => $field_name,
                ]);
              }
              else {
                $links['comment-add'] += ['url' => $entity->toUrl()];
              }
            }
            elseif ($this->currentUser->isAnonymous()) {
              $links['comment-forbidden'] = [
                'title' => $this->commentManager->forbiddenMessage($entity, $field_name),
              ];
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
                $links['comment-add'] = [
                  'title' => $this->t('Add new comment'),
                  'attributes' => ['title' => $this->t('Share your thoughts and opinions.')],
                  'fragment' => 'comment-form',
                ];
                if ($comment_form_location == CommentItemInterface::FORM_SEPARATE_PAGE) {
                  $links['comment-add']['url'] = Url::fromRoute('comment.reply', [
                    'entity_type' => $entity->getEntityTypeId(),
                    'entity' => $entity->id(),
                    'field_name' => $field_name,
                  ]);
                }
                else {
                  $links['comment-add']['url'] = $entity->toUrl();
                }
              }
            }
            elseif ($this->currentUser->isAnonymous()) {
              $links['comment-forbidden'] = [
                'title' => $this->commentManager->forbiddenMessage($entity, $field_name),
              ];
            }
          }
        }
      }

      if (!empty($links)) {
        $entity_links['comment__' . $field_name] = [
          '#theme' => 'links__entity__comment__' . $field_name,
          '#links' => $links,
          '#attributes' => ['class' => ['links', 'inline']],
        ];
      }
    }
    return $entity_links;
  }

}
