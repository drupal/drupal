<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\field\widget\CommentWidget.
 */

namespace Drupal\comment\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Provides a default comment widget.
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
   * {@inheritdoc}
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $field = $this->fieldDefinition;
    $entity = $element['#entity'];

    $element['status'] = array(
      '#type' => 'radios',
      '#title' => t('Comments'),
      '#title_display' => 'invisible',
      '#default_value' => _comment_get_default_status($items),
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
    if (!empty($entity->field_ui_default_value) || empty($entity->comment_statistics[$field->getFieldName()]->comment_count)) {
      $element['status'][COMMENT_HIDDEN]['#access'] = FALSE;
      // Also adjust the description of the "closed" option.
      $element['status'][COMMENT_CLOSED]['#description'] = t('Users cannot post comments.');
    }
    // Integrate with advanced settings, if available.
    if (isset($form['advanced'])) {
      $element += array(
        '#type' => 'details',
        // Collapse the advanced settings when they are the same 
        // as the defaults for the instance.
        '#collapsed' => (_comment_get_default_status($items) == _comment_get_default_status($field->default_value)),
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
