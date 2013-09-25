<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\field\widget\CommentWidget.
 */

namespace Drupal\comment\Plugin\field\widget;

use Drupal\field\Annotation\FieldWidget;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldInterface;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

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
  public function formElement(FieldInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $field = $this->fieldDefinition;
    $entity = $items->getParent();
    $default_value = $field->getFieldDefaultValue($entity);
    if (!isset($default_value->status)) {
      $default_value = (object) reset($field->default_value);
    }

    $element['status'] = array(
      '#type' => 'radios',
      '#title' => t('Comments'),
      '#title_display' => 'invisible',
      '#default_value' => !empty($default_value->status) ? $default_value->status : COMMENT_OPEN,
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
        // Collapse this field when the selected value is the same as the
        // defaults for the instance.
        // @todo Add $this->defaultStatus($field) and compare actual values.
        '#collapsed' => ($items->getValue() == $field->getFieldDefaultValue($entity)),
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
