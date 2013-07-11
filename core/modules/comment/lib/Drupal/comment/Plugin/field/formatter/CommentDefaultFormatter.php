<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\field\formatter\CommentDefaultFormatter.
 */

namespace Drupal\comment\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a default comment formatter.
 *
 * @FieldFormatter(
 *   id = "comment_default",
 *   module = "comment",
 *   label = @Translation("Comment list"),
 *   field_types = {
 *     "comment"
 *   }
 * )
 */
class CommentDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    $field = $this->fieldDefinition;
    $field_name = $field->getFieldName();

    $commenting_status = _comment_get_default_status($items);
    if ($commenting_status != COMMENT_HIDDEN && empty($entity->in_preview)) {
      $comment_settings = $this->getFieldSettings();

      // Only attempt to render comments if the entity has visible comments.
      // Unpublished comments are not included in
      // $entity->comment_statistics[$field_name]->comment_count, but
      // unpublished comments should display if the user is an administrator.
      if (((!empty($entity->comment_statistics[$field_name]->comment_count) && user_access('access comments')) || user_access('administer comments')) &&
      !empty($entity->content['#view_mode']) &&
      !in_array($entity->content['#view_mode'], array('search_result', 'search_index'))) {

        // Comment threads aren't added to search results/indexes using the
        // formatter, @see comment_node_update_index().
        $mode = $comment_settings['default_mode'];
        $comments_per_page = $comment_settings['per_page'];
        if ($cids = comment_get_thread($entity, $field_name, $mode, $comments_per_page)) {
          $comments = comment_load_multiple($cids);
          comment_prepare_thread($comments);
          $build = comment_view_multiple($comments);
          $build['pager']['#theme'] = 'pager';
          $additions['comments'] = $build;
        }
      }

      // Append comment form if needed.
      if ($commenting_status == COMMENT_OPEN && $comment_settings['form_location'] == COMMENT_FORM_BELOW) {
        // Only show the add comment form if the user has permission and the
        // view mode is not search_result or search_index.
        if (user_access('post comments') && !empty($entity->content['#view_mode']) &&
          !in_array($entity->content['#view_mode'], array('search_result', 'search_index'))) {
          $additions['comment_form'] = comment_add($entity, $field_name);
        }
      }
    }

    if (!empty($additions)) {
      $elements[] = $additions + array(
        '#theme' => 'comment_wrapper__' . $entity->entityType() . '__' . $entity->bundle() . '__' . $field_name,
        '#entity' => $entity,
        '#display_mode' => $this->getFieldSetting('default_mode'),
        'comments' => array(),
        'comment_form' => array(),
      );
    }

    return $elements;
  }

}
