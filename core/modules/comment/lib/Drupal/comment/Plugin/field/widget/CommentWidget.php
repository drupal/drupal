<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\field\widget\CommentWidget.
 */

namespace Drupal\comment\Plugin\field\widget;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the default comment widget.
 *
 * @Plugin(
 *   id = "comment_default",
 *   module = "comment",
 *   label = @Translation("Comment"),
 *   field_types = {
 *     "comment"
 *   }
 * )
 */
class CommentWidget extends WidgetBase {

  /**
   * Implements \Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $field = $this->field;
    $entity = $element['#entity'];
    $default_value = isset($items[0]['comment']) ? $items[0]['comment'] : COMMENT_OPEN;

    $comment_count = empty($entity->comment_statistics[$field['field_name']]->comment_count) ? 0 : $entity->comment_statistics[$field['field_name']]->comment_count;
    $commenting_enabled = ($default_value == COMMENT_HIDDEN && empty($comment_count)) ? COMMENT_CLOSED : $default_value;
    $element['comment'] = array(
      '#type' => 'radios',
      '#title' => t('Comments'),
      '#title_display' => 'invisible',
      '#default_value' => $commenting_enabled,
      '#options' => array(
        COMMENT_OPEN => t('Open'),
        COMMENT_CLOSED => t('Closed'),
        COMMENT_HIDDEN => t('Hidden'),
      ),
      COMMENT_OPEN => array(
        '#description' => t('Users with the "Post comments" permission can post comments.'),
      ),
      COMMENT_CLOSED => array(
        '#description' => t('Users cannot post comments, but existing comments will be displayed.'),
      ),
      COMMENT_HIDDEN => array(
        '#description' => t('Comments are hidden from view.'),
      ),
    );
    // If the node doesn't have any comments, the "hidden" option makes no
    // sense, so don't even bother presenting it to the user.
    if (empty($comment_count)) {
      $element['comment'][COMMENT_HIDDEN]['#access'] = FALSE;
      // Also adjust the description of the "closed" option.
      $element['comment'][COMMENT_CLOSED]['#description'] = t('Users cannot post comments.');
    }
    // Integrate with advanced settings, if available.
    if (isset($form['advanced'])) {
      $element += array(
        '#type' => 'details',
        '#group' => 'advanced',
        '#access' => user_access('administer comments'),
        '#collapsed' => TRUE,
        '#attributes' => array(
          'class' => array('comment-node-settings-form'),
        ),
        '#attached' => array(
          'library' => array('comment', 'drupal.comment'),
        ),
        '#weight' => 30,
      );
    }

    return $element;
  }

}
