<?php

/**
 * @file
 * Definition of Drupal\comment\CommentRenderController.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRenderController;
use Drupal\entity\Entity\EntityDisplay;

/**
 * Render controller for comments.
 */
class CommentRenderController extends EntityRenderController {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::buildContent().
   *
   * In addition to modifying the content key on entities, this implementation
   * will also set the comment entity key which all comments carry.
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    $return = array();
    if (empty($entities)) {
      return $return;
    }

    // Pre-load associated users into cache to leverage multiple loading.
    $uids = array();
    foreach ($entities as $entity) {
      $uids[] = $entity->uid->target_id;
    }
    user_load_multiple(array_unique($uids));

    parent::buildContent($entities, $displays, $view_mode, $langcode);

    // Load all the entities that have comments attached.
    $comment_entity_ids = array();
    $comment_entities = array();
    foreach ($entities as $entity) {
      $comment_entity_ids[$entity->entity_type->value][] = $entity->entity_id->value;
    }
    // Load entities in bulk. This is more performant than using
    // $comment->entity_id->value as we can load them in bulk per type.
    foreach ($comment_entity_ids as $entity_type => $entity_ids) {
      $comment_entities[$entity_type] = entity_load_multiple($entity_type, $entity_ids);
    }

    foreach ($entities as $entity) {
      if (isset($comment_entities[$entity->entity_type->value][$entity->entity_id->value])) {
        $comment_entity = $comment_entities[$entity->entity_type->value][$entity->entity_id->value];
      }
      else {
        throw new \InvalidArgumentException(t('Invalid entity for comment.'));
      }
      $entity->content['#entity'] = $entity;
      $entity->content['#theme'] = 'comment__' . $entity->entity_type->value . '__' . $comment_entity->bundle() . '__' . $entity->field_name->value;
      $entity->content['links'] = array(
        '#theme' => 'links__comment',
        '#pre_render' => array('drupal_pre_render_links'),
        '#attributes' => array('class' => array('links', 'inline')),
      );
      if (empty($entity->in_preview)) {
        $entity->content['links'][$this->entityType] = array(
          '#theme' => 'links__comment__comment',
          // The "entity" property is specified to be present, so no need to
          // check.
          '#links' => comment_links($entity, $comment_entity, $entity->field_name->value),
          '#attributes' => array('class' => array('links', 'inline')),
        );
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::alterBuild().
   */
  protected function alterBuild(array &$build, EntityInterface $comment, EntityDisplay $display, $view_mode, $langcode = NULL) {
    parent::alterBuild($build, $comment, $display, $view_mode, $langcode);
    if (empty($comment->in_preview)) {
      $prefix = '';
      $comment_entity = entity_load($comment->entity_type->value, $comment->entity_id->value);
      $instance = field_info_instance($comment_entity->entityType(), $comment->field_name->value, $comment_entity->bundle());
      $is_threaded = isset($comment->divs)
        && $instance['settings']['default_mode'] == COMMENT_MODE_THREADED;

      // Add 'new' anchor if needed.
      if (!empty($comment->first_new)) {
        $prefix .= "<a id=\"new\"></a>\n";
      }

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

}
