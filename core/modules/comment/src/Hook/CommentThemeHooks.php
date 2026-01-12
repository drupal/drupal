<?php

namespace Drupal\comment\Hook;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Hook implementations for comment.
 */
class CommentThemeHooks {

  use StringTranslationTrait;

  public function __construct(
    protected DateFormatterInterface $dateFormatter,
    protected RendererInterface $renderer,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ThemeSettingsProvider $themeSettingsProvider,
  ) {

  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'comment' => [
        'render element' => 'elements',
        'initial preprocess' => static::class . ':preprocessComment',
      ],
      'field__comment' => [
        'base hook' => 'field',
      ],
    ];
  }

  /**
   * Prepares variables for comment templates.
   *
   * By default this function performs special preprocessing of some base fields
   * so they are available as variables in the template. For example 'subject'
   * appears as 'title'. This preprocessing is skipped if:
   * - a module makes the field's display configurable via the field UI by means
   *   of BaseFieldDefinition::setDisplayConfigurable()
   * - AND the additional entity type property
   *   'enable_base_field_custom_preprocess_skipping' has been set using
   *   hook_entity_type_build().
   *
   * Default template: comment.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - elements: An associative array containing the comment and entity
   *     objects. Array keys: #comment, #commented_entity.
   */
  public function preprocessComment(array &$variables): void {
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $variables['elements']['#comment'];
    $commented_entity = $comment->getCommentedEntity();
    $variables['comment'] = $comment;
    $variables['commented_entity'] = $commented_entity;
    $variables['threaded'] = $variables['elements']['#comment_threaded'];

    $skip_custom_preprocessing = $comment->getEntityType()->get('enable_base_field_custom_preprocess_skipping');

    // Make created, uid, pid and subject fields available separately. Skip this
    // custom preprocessing if the field display is configurable and skipping
    // has been enabled.
    // @todo https://www.drupal.org/project/drupal/issues/3015623
    //   Eventually delete this code and matching template lines. Using
    //   $variables['content'] is more flexible and consistent.
    $submitted_configurable = $comment->getFieldDefinition('created')->isDisplayConfigurable('view') || $comment->getFieldDefinition('uid')->isDisplayConfigurable('view');

    if (!$skip_custom_preprocessing || !$submitted_configurable) {
      $account = $comment->getOwner();
      $username = [
        '#theme' => 'username',
        '#account' => $account,
      ];
      $variables['author'] = $this->renderer->render($username);
      $variables['author_id'] = $comment->getOwnerId();
      $variables['new_indicator_timestamp'] = $comment->getChangedTime();
      $variables['created'] = $this->dateFormatter->format($comment->getCreatedTime());
      // Avoid calling DateFormatterInterface::format() twice on the same
      // timestamp.
      if ($comment->getChangedTime() == $comment->getCreatedTime()) {
        $variables['changed'] = $variables['created'];
      }
      else {
        $variables['changed'] = $this->dateFormatter->format($comment->getChangedTime());
      }

      if ($this->themeSettingsProvider->getSetting('features.comment_user_picture')) {
        // To change user picture settings (for instance, image style), edit the
        // 'compact' view mode on the User entity.
        $variables['user_picture'] = $this->entityTypeManager
          ->getViewBuilder('user')
          ->view($account, 'compact');
      }
      else {
        $variables['user_picture'] = [];
      }

      $variables['submitted'] = $this->t('Submitted by @username on @datetime', [
        '@username' => $variables['author'],
        '@datetime' => $variables['created'],
      ]);
    }

    if (isset($comment->in_preview)) {
      $variables['permalink'] = Link::fromTextAndUrl($this->t('Permalink'), Url::fromRoute('<front>'))->toString();
    }
    else {
      $variables['permalink'] = Link::fromTextAndUrl($this->t('Permalink'), $comment->permalink())->toString();
    }

    if (($comment_parent = $comment->getParentComment()) && (!$skip_custom_preprocessing || !$comment->getFieldDefinition('pid')->isDisplayConfigurable('view'))) {
      // Fetch and store the parent comment information for use in templates.
      $account_parent = $comment_parent->getOwner();
      $variables['parent_comment'] = $comment_parent;
      $username = [
        '#theme' => 'username',
        '#account' => $account_parent,
      ];
      $variables['parent_author'] = $this->renderer->render($username);
      $variables['parent_created'] = $this->dateFormatter->format($comment_parent->getCreatedTime());
      // Avoid calling DateFormatterInterface::format() twice on same timestamp.
      if ($comment_parent->getChangedTime() == $comment_parent->getCreatedTime()) {
        $variables['parent_changed'] = $variables['parent_created'];
      }
      else {
        $variables['parent_changed'] = $this->dateFormatter->format($comment_parent->getChangedTime());
      }
      $permalink_uri_parent = $comment_parent->permalink();
      $attributes = $permalink_uri_parent->getOption('attributes') ?: [];
      $attributes += ['class' => ['permalink'], 'rel' => 'bookmark'];
      $permalink_uri_parent->setOption('attributes', $attributes);
      $variables['parent_title'] = Link::fromTextAndUrl($comment_parent->getSubject(), $permalink_uri_parent)->toString();
      $variables['parent_permalink'] = Link::fromTextAndUrl($this->t('Parent permalink'), $permalink_uri_parent)->toString();
      $variables['parent'] = $this->t('In reply to @parent_title by @parent_username',
        ['@parent_username' => $variables['parent_author'], '@parent_title' => $variables['parent_title']]);
    }
    else {
      $variables['parent_comment'] = '';
      $variables['parent_author'] = '';
      $variables['parent_created'] = '';
      $variables['parent_changed'] = '';
      $variables['parent_title'] = '';
      $variables['parent_permalink'] = '';
      $variables['parent'] = '';
    }

    if (!$skip_custom_preprocessing || !$comment->getFieldDefinition('subject')->isDisplayConfigurable('view')) {
      if (isset($comment->in_preview)) {
        $variables['title'] = Link::fromTextAndUrl($comment->getSubject(), Url::fromRoute('<front>'))->toString();
      }
      else {
        $uri = $comment->permalink();
        $attributes = $uri->getOption('attributes') ?: [];
        $attributes += ['class' => ['permalink'], 'rel' => 'bookmark'];
        $uri->setOption('attributes', $attributes);
        $variables['title'] = Link::fromTextAndUrl($comment->getSubject(), $uri)->toString();
      }
    }

    // Helpful $content variable for templates.
    foreach (Element::children($variables['elements']) as $key) {
      $variables['content'][$key] = $variables['elements'][$key];
    }

    // Set status to a string representation of comment->status.
    if (isset($comment->in_preview)) {
      $variables['status'] = 'preview';
    }
    else {
      $variables['status'] = $comment->isPublished() ? 'published' : 'unpublished';
    }

    // Add comment author user ID. Necessary for the comment-by-viewer library.
    $variables['attributes']['data-comment-user-id'] = $comment->getOwnerId();
    // Add anchor for each comment.
    $variables['attributes']['id'] = 'comment-' . $comment->id();
  }

  /**
   * Implements hook_preprocess_HOOK() for block templates.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    if ($variables['configuration']['provider'] == 'comment') {
      $variables['attributes']['role'] = 'navigation';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for field templates.
   *
   * Prepares variables for comment field templates.
   *
   * Default template: field--comment.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing render arrays for the list of
   *     comments, and the comment form. Array keys: comments, comment_form.
   *
   * @todo Rename to preprocess_field__comment() once
   *   https://www.drupal.org/node/3566850 is resolved.
   */
  #[Hook('preprocess_field')]
  public function preprocessField(&$variables): void {
    $element = $variables['element'];
    // We need to check for the field type even though we are using the comment
    // theme hook suggestion. This is because there may be a bundle or field
    // with the same name.
    if ($element['#field_type'] == 'comment') {
      // Provide contextual information.
      $variables['comment_display_mode'] = $element[0]['#comment_display_mode'];
      $variables['comment_type'] = $element[0]['#comment_type'];

      // Append additional attributes from the first field item.
      $variables['attributes'] += $variables['items'][0]['attributes']->storage();

      // Create separate variables for the comments and comment form.
      $variables['comments'] = $element[0]['comments'];
      $variables['comment_form'] = $element[0]['comment_form'];
    }
  }

}
