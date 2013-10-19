<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Field\FieldWidget\CommentWidget.
 */

namespace Drupal\comment\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;

/**
 * Provides a default comment widget.
 *
 * @FieldWidget(
 *   id = "comment_default",
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
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $field = $this->fieldDefinition;
    $entity = $items->getParent();

    // Get default value from the field instance.
    $field_default_values = $this->fieldDefinition->getFieldDefaultValue($entity);
    $status = $items->status;

    $element['status'] = array(
      '#type' => 'radios',
      '#title' => t('Comments'),
      '#title_display' => 'invisible',
      '#default_value' => $status,
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
    // If the entity doesn't have any comments, the "hidden" option makes no
    // sense, so don't even bother presenting it to the user unless this is the
    // default value widget on the field settings form.
    if ($element['#field_parents'] != array('default_value_input') && !$entity->get($field->getFieldName())->comment_count) {
      $element['status'][COMMENT_HIDDEN]['#access'] = FALSE;
      // Also adjust the description of the "closed" option.
      $element['status'][COMMENT_CLOSED]['#description'] = t('Users cannot post comments.');
    }
    // If the advanced settings tabs-set is available (normally rendered in the
    // second column on wide-resolutions), place the field as a details element
    // in this tab-set.
    if (isset($form['advanced'])) {
      $element += array(
        '#type' => 'details',
        // Collapse this field when the selected value is the same as stored in
        // default values for the field instance.
        '#collapsed' => ($items->status == $field_default_values[0]['status']),
        '#group' => 'advanced',
        '#attributes' => array(
          'class' => array('comment-' . drupal_html_class($element['#entity_type']) . '-settings-form'),
        ),
        '#attached' => array(
          'library' => array('comment', 'drupal.comment'),
        ),
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, array &$form_state) {
    // Add default values for statistics properties because we don't want to
    // have them in form.
    foreach ($values as &$value) {
      $value += array(
        'cid' => 0,
        'last_comment_timestamp' => 0,
        'last_comment_name' => '',
        'last_comment_uid' => 0,
        'comment_count' => 0,
      );
    }
    return $values;
  }

}
