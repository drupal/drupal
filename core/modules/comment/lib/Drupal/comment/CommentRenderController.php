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
   * will also set the node key which all comments carry.
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

    // Load all nodes of all comments at once.
    $nids = array();
    foreach ($entities as $entity) {
      $nids[$entity->nid->target_id] = $entity->nid->target_id;
    }
    $nodes = node_load_multiple($nids);

    global $user;

    foreach ($entities as $entity) {
      if (isset($nodes[$entity->nid->target_id])) {
        $node = $nodes[$entity->nid->target_id];
      }
      else {
        throw new \InvalidArgumentException(t('Invalid node for comment.'));
      }
      $entity->content['#node'] = $node;
      $entity->content['#theme'] = 'comment__node_' . $node->bundle();
      $entity->content['links'] = array(
        '#theme' => 'links__comment',
        '#pre_render' => array('drupal_pre_render_links'),
        '#attributes' => array('class' => array('links', 'inline')),
      );
      if (empty($entity->in_preview)) {
        $entity->content['links'][$this->entityType] = array(
          '#theme' => 'links__comment__comment',
          // The "node" property is specified to be present, so no need to check.
          '#links' => comment_links($entity, $node),
          '#attributes' => array('class' => array('links', 'inline')),
        );
      }

      if (!isset($entity->content['#attached'])) {
        $entity->content['#attached'] = array();
      }
      $entity->content['#attached']['library'][] = array('comment', 'drupal.comment-by-viewer');
      if (\Drupal::moduleHandler()->moduleExists('history') && $user->isAuthenticated()) {
        $entity->content['#attached']['library'][] = array('comment', 'drupal.comment-new-indicator');
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
      $is_threaded = isset($comment->divs)
        && variable_get('comment_default_mode_' . $comment->bundle(), COMMENT_MODE_THREADED) == COMMENT_MODE_THREADED;

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
