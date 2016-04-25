<?php

namespace Drupal\comment;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View builder handler for comments.
 */
class CommentViewBuilder extends EntityViewBuilder {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new CommentViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, AccountInterface $current_user) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);

    /** @var \Drupal\comment\CommentInterface $entity */
    // Store a threading field setting to use later in self::buildComponents().
    $build['#comment_threaded'] = $entity->getCommentedEntity()
      ->getFieldDefinition($entity->getFieldName())
      ->getSetting('default_mode') === CommentManagerInterface::COMMENT_MODE_THREADED;
    // If threading is enabled, don't render cache individual comments, but do
    // keep the cacheability metadata, so it can bubble up.
    if ($build['#comment_threaded']) {
      unset($build['#cache']['keys']);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * In addition to modifying the content key on entities, this implementation
   * will also set the comment entity key which all comments carry.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a comment is attached to an entity that no longer exists.
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    /** @var \Drupal\comment\CommentInterface[] $entities */
    if (empty($entities)) {
      return;
    }

    // Pre-load associated users into cache to leverage multiple loading.
    $uids = array();
    foreach ($entities as $entity) {
      $uids[] = $entity->getOwnerId();
    }
    $this->entityManager->getStorage('user')->loadMultiple(array_unique($uids));

    parent::buildComponents($build, $entities, $displays, $view_mode);

    // A counter to track the indentation level.
    $current_indent = 0;

    foreach ($entities as $id => $entity) {
      if ($build[$id]['#comment_threaded']) {
        $comment_indent = count(explode('.', $entity->getThread())) - 1;
        if ($comment_indent > $current_indent) {
          // Set 1 to indent this comment from the previous one (its parent).
          // Set only one extra level of indenting even if the difference in
          // depth is higher.
          $build[$id]['#comment_indent'] = 1;
          $current_indent++;
        }
        else {
          // Set zero if this comment is on the same level as the previous one
          // or negative value to point an amount indents to close.
          $build[$id]['#comment_indent'] = $comment_indent - $current_indent;
          $current_indent = $comment_indent;
        }
      }

      // Commented entities already loaded after self::getBuildDefaults().
      $commented_entity = $entity->getCommentedEntity();

      $build[$id]['#entity'] = $entity;
      $build[$id]['#theme'] = 'comment__' . $entity->getFieldName() . '__' . $commented_entity->bundle();

      $display = $displays[$entity->bundle()];
      if ($display->getComponent('links')) {
        $build[$id]['links'] = array(
          '#lazy_builder' => ['comment.lazy_builders:renderLinks', [
            $entity->id(),
            $view_mode,
            $entity->language()->getId(),
            !empty($entity->in_preview),
          ]],
          '#create_placeholder' => TRUE,
        );
      }

      if (!isset($build[$id]['#attached'])) {
        $build[$id]['#attached'] = array();
      }
      $build[$id]['#attached']['library'][] = 'comment/drupal.comment-by-viewer';
      if ($this->moduleHandler->moduleExists('history') && $this->currentUser->isAuthenticated()) {
        $build[$id]['#attached']['library'][] = 'comment/drupal.comment-new-indicator';

        // Embed the metadata for the comment "new" indicators on this node.
        $build[$id]['history'] = [
          '#lazy_builder' => ['history_attach_timestamp', [$commented_entity->id()]],
          '#create_placeholder' => TRUE,
        ];
      }
    }
    if ($build[$id]['#comment_threaded']) {
      // The final comment must close up some hanging divs.
      $build[$id]['#comment_indent_final'] = $current_indent;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $comment, EntityViewDisplayInterface $display, $view_mode) {
    parent::alterBuild($build, $comment, $display, $view_mode);
    if (empty($comment->in_preview)) {
      $prefix = '';

      // Add indentation div or close open divs as needed.
      if ($build['#comment_threaded']) {
        $prefix .= $build['#comment_indent'] <= 0 ? str_repeat('</div>', abs($build['#comment_indent'])) : "\n" . '<div class="indented">';
      }

      // Add anchor for each comment.
      $prefix .= "<a id=\"comment-{$comment->id()}\"></a>\n";
      $build['#prefix'] = $prefix;

      // Close all open divs.
      if (!empty($build['#comment_indent_final'])) {
        $build['#suffix'] = str_repeat('</div>', $build['#comment_indent_final']);
      }
    }
  }

}
