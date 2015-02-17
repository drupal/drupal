<?php

/**
 * @file
 * Contents Drupal\comment\CommentViewBuilder.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for comments.
 */
class CommentViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    $build = parent::getBuildDefaults($entity, $view_mode, $langcode);

    // If threading is enabled, don't render cache individual comments, but do
    // keep the cache tags, so they can bubble up.
    if ($entity->getCommentedEntity()->getFieldDefinition($entity->getFieldName())->getSetting('default_mode') === CommentManagerInterface::COMMENT_MODE_THREADED) {
      $cache_tags = $build['#cache']['tags'];
      $build['#cache'] = [];
      $build['#cache']['tags'] = $cache_tags;
    }

    return $build;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityViewBuilder::buildComponents().
   *
   * In addition to modifying the content key on entities, this implementation
   * will also set the comment entity key which all comments carry.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a comment is attached to an entity that no longer exists.
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode, $langcode = NULL) {
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

    parent::buildComponents($build, $entities, $displays, $view_mode, $langcode);

    // Load all the entities that have comments attached.
    $commented_entity_ids = array();
    $commented_entities = array();
    foreach ($entities as $entity) {
      $commented_entity_ids[$entity->getCommentedEntityTypeId()][] = $entity->getCommentedEntityId();
    }
    // Load entities in bulk. This is more performant than using
    // $comment->getCommentedEntity() as we can load them in bulk per type.
    foreach ($commented_entity_ids as $entity_type => $entity_ids) {
      $commented_entities[$entity_type] = $this->entityManager->getStorage($entity_type)->loadMultiple($entity_ids);
    }

    foreach ($entities as $id => $entity) {
      if (isset($commented_entities[$entity->getCommentedEntityTypeId()][$entity->getCommentedEntityId()])) {
        $commented_entity = $commented_entities[$entity->getCommentedEntityTypeId()][$entity->getCommentedEntityId()];
      }
      else {
        throw new \InvalidArgumentException(t('Invalid entity for comment.'));
      }
      $build[$id]['#entity'] = $entity;
      $build[$id]['#theme'] = 'comment__' . $entity->getFieldName() . '__' . $commented_entity->bundle();

      $display = $displays[$entity->bundle()];
      if ($display->getComponent('links')) {
        $callback = 'comment.post_render_cache:renderLinks';
        $context = array(
          'comment_entity_id' => $entity->id(),
          'view_mode' => $view_mode,
          'langcode' => $langcode,
          'commented_entity_type' => $commented_entity->getEntityTypeId(),
          'commented_entity_id' => $commented_entity->id(),
          'in_preview' => !empty($entity->in_preview),
        );
        $placeholder = drupal_render_cache_generate_placeholder($callback, $context);
        $build[$id]['links'] = array(
          '#post_render_cache' => array(
            $callback => array(
              $context,
            ),
          ),
          '#markup' => $placeholder,
        );
      }

      $account = comment_prepare_author($entity);
      if (\Drupal::config('user.settings')->get('signatures') && $account->getSignature()) {
        $build[$id]['signature'] = array(
          '#type' => 'processed_text',
          '#text' => $account->getSignature(),
          '#format' => $account->getSignatureFormat(),
          '#langcode' => $entity->language()->getId(),
        );
      }

      if (!isset($build[$id]['#attached'])) {
        $build[$id]['#attached'] = array();
      }
      $build[$id]['#attached']['library'][] = 'comment/drupal.comment-by-viewer';
      if ($this->moduleHandler->moduleExists('history') &&  \Drupal::currentUser()->isAuthenticated()) {
        $build[$id]['#attached']['library'][] = 'comment/drupal.comment-new-indicator';

        // Embed the metadata for the comment "new" indicators on this node.
        $build[$id]['#post_render_cache']['history_attach_timestamp'] = array(
          array('node_id' => $commented_entity->id()),
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $comment, EntityViewDisplayInterface $display, $view_mode, $langcode = NULL) {
    parent::alterBuild($build, $comment, $display, $view_mode, $langcode);
    if (empty($comment->in_preview)) {
      $prefix = '';
      $commented_entity = $comment->getCommentedEntity();
      $field_definition = $this->entityManager->getFieldDefinitions($commented_entity->getEntityTypeId(), $commented_entity->bundle())[$comment->getFieldName()];
      $is_threaded = isset($comment->divs)
        && $field_definition->getSetting('default_mode') == CommentManagerInterface::COMMENT_MODE_THREADED;

      // Add indentation div or close open divs as needed.
      if ($is_threaded) {
        $build['#attached']['library'][] = 'comment/drupal.comment.threaded';
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

}
