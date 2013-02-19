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

    $element['status'] = array(
      '#type' => 'radios',
      '#title' => t('Comments'),
      '#title_display' => 'invisible',
      // @todo Field instance should provide always default value http://drupal.org/node/1919834
      '#default_value' => isset($items[0]['status']) ? $items[0]['status'] : COMMENT_OPEN,
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
    // If used for the field settings form or the entity doesn't have any
    // comments, the "hidden" option makes no sense, so don't even bother
    // presenting it to the user.
    if (!empty($entity->field_ui_default_value) || empty($entity->comment_statistics[$field['field_name']]->comment_count)) {
      $element['status'][COMMENT_HIDDEN]['#access'] = FALSE;
      // Also adjust the description of the "closed" option.
      $element['status'][COMMENT_CLOSED]['#description'] = t('Users cannot post comments.');
    }
    // Integrate with advanced settings, if available.
    if (isset($form['advanced'])) {
      $element += array(
        '#type' => 'details',
        '#group' => 'advanced',
        '#attributes' => array(
          'class' => array('comment-' . drupal_html_class($element['#entity_type']) . '-settings-form'),
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
