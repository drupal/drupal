<?php

/**
 * @file
 * Definition of Drupal\comment\CommentRenderController.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRenderController;

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
  public function buildContent(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    $return = array();
    if (empty($entities)) {
      return $return;
    }

    // Attach user account.
    user_attach_accounts($entities);

    parent::buildContent($entities, $view_mode, $langcode);

    foreach ($entities as $entity) {
      $comment_entity = entity_load($entity->entity_type, $entity->entity_id);
      if (!$comment_entity) {
        throw new \InvalidArgumentException(t('Invalid entity for comment.'));
      }
      $entity->content['#entity'] = $entity;
      $entity->content['#theme'] = 'comment__' . $entity->entity_type . '__' . $comment_entity->bundle() . '__' . $entity->field_name;
      $entity->content['links'] = array(
        '#theme' => 'links__comment',
        '#pre_render' => array('drupal_pre_render_links'),
        '#attributes' => array('class' => array('links', 'inline')),
      );
      if (empty($entity->in_preview)) {
        $entity->content['links'][$this->entityType] = array(
          '#theme' => 'links__comment__comment',
          // The "entity" property is specified to be present, so no need to check.
          '#links' => comment_links($entity, $comment_entity, $entity->field_name),
          '#attributes' => array('class' => array('links', 'inline')),
        );
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::alterBuild().
   */
  protected function alterBuild(array &$build, EntityInterface $comment, $view_mode, $langcode = NULL) {
    parent::alterBuild($build, $comment, $view_mode, $langcode);
    if (empty($comment->in_preview)) {
      $prefix = '';
      $comment_entity = entity_load($comment->entity_type, $comment->entity_id);
      $instance = field_info_instance($comment_entity->entityType(), $comment->field_name, $comment_entity->bundle());
      $is_threaded = isset($comment->divs)
        && $instance['settings']['comment']['comment_default_mode'] == COMMENT_MODE_THREADED;

      // Add 'new' anchor if needed.
      if (!empty($comment->first_new)) {
        $prefix .= "<a id=\"new\"></a>\n";
      }

      // Add indentation div or close open divs as needed.
      if ($is_threaded) {
        $build['#attached']['css'][] = drupal_get_path('module', 'comment') . '/comment.theme.css';
        $prefix .= $comment->divs <= 0 ? str_repeat('</div>', abs($comment->divs)) : "\n" . '<div class="indented">';
      }

      // Add anchor for each comment.
      $prefix .= "<a id=\"comment-$comment->cid\"></a>\n";
      $build['#prefix'] = $prefix;

      // Close all open divs.
      if ($is_threaded && !empty($comment->divs_final)) {
        $build['#suffix'] = str_repeat('</div>', $comment->divs_final);
      }
    }
  }
}
