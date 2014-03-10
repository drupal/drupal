<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Field\FieldWidget\CommentWidget.
 */

namespace Drupal\comment\Plugin\Field\FieldWidget;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
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
    $field_default_values = $this->fieldDefinition->getDefaultValue($entity);
    $status = $items->status;

    $element['status'] = array(
      '#type' => 'radios',
      '#title' => t('Comments'),
      '#title_display' => 'invisible',
      '#default_value' => $status,
      '#options' => array(
        CommentItemInterface::OPEN => t('Open'),
        CommentItemInterface::CLOSED => t('Closed'),
        CommentItemInterface::HIDDEN => t('Hidden'),
      ),
      CommentItemInterface::OPEN => array(
        '#description' => t('Users with the "Post comments" permission can post comments.'),
      ),
      CommentItemInterface::CLOSED => array(
        '#description' => t('Users cannot post comments, but existing comments will be displayed.'),
      ),
      CommentItemInterface::HIDDEN => array(
        '#description' => t('Comments are hidden from view.'),
      ),
    );
    // If the entity doesn't have any comments, the "hidden" option makes no
    // sense, so don't even bother presenting it to the user unless this is the
    // default value widget on the field settings form.
    if ($element['#field_parents'] != array('default_value_input') && !$entity->get($field->getName())->comment_count) {
      $element['status'][CommentItemInterface::HIDDEN]['#access'] = FALSE;
      // Also adjust the description of the "closed" option.
      $element['status'][CommentItemInterface::CLOSED]['#description'] = t('Users cannot post comments.');
    }
    // If the advanced settings tabs-set is available (normally rendered in the
    // second column on wide-resolutions), place the field as a details element
    // in this tab-set.
    if (isset($form['advanced'])) {
      $element += array(
        '#type' => 'details',
        // Open the details when the selected value is different to the stored
        // default values for the field instance.
        '#open' => ($items->status != $field_default_values[0]['status']),
        '#group' => 'advanced',
        '#attributes' => array(
          'class' => array('comment-' . drupal_html_class($element['#entity_type']) . '-settings-form'),
        ),
        '#attached' => array(
          'library' => array('comment/drupal.comment'),
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
