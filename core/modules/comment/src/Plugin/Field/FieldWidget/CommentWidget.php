<?php

namespace Drupal\comment\Plugin\Field\FieldWidget;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

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
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();

    $element['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Comments'),
      '#title_display' => 'invisible',
      '#default_value' => $items->status,
      '#required' => TRUE,
      '#options' => [
        CommentItemInterface::OPEN => $this->t('Open'),
        CommentItemInterface::CLOSED => $this->t('Closed'),
        CommentItemInterface::HIDDEN => $this->t('Hidden'),
      ],
      CommentItemInterface::OPEN => [
        '#description' => $this->t('Users with the "Post comments" permission can post comments.'),
      ],
      CommentItemInterface::CLOSED => [
        '#description' => $this->t('Users cannot post comments, but existing comments will be displayed.'),
      ],
      CommentItemInterface::HIDDEN => [
        '#description' => $this->t('Comments are hidden from view.'),
      ],
    ];
    // If the entity doesn't have any comments, the "hidden" option makes no
    // sense, so don't even bother presenting it to the user unless this is the
    // default value widget on the field settings form.
    if (!$this->isDefaultValueWidget($form_state) && !$items->comment_count) {
      $element['status'][CommentItemInterface::HIDDEN]['#access'] = FALSE;
      // Also adjust the description of the "closed" option.
      $element['status'][CommentItemInterface::CLOSED]['#description'] = $this->t('Users cannot post comments.');
    }
    // If the advanced settings tabs-set is available (normally rendered in the
    // second column on wide-resolutions), place the field as a details element
    // in this tab-set.
    if (isset($form['advanced'])) {
      // Get default value from the field.
      $field_default_values = $this->fieldDefinition->getDefaultValue($entity);

      // Override widget title to be helpful for end users.
      $element['#title'] = $this->t('Comment settings');

      $element += [
        '#type' => 'details',
        // Open the details when the selected value is different to the stored
        // default values for the field.
        '#open' => ($items->status != $field_default_values[0]['status']),
        '#group' => 'advanced',
        '#attributes' => [
          'class' => ['comment-' . Html::getClass($entity->getEntityTypeId()) . '-settings-form'],
        ],
        '#attached' => [
          'library' => ['comment/drupal.comment'],
        ],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Add default values for statistics properties because we don't want to
    // have them in form.
    foreach ($values as &$value) {
      $value += [
        'cid' => 0,
        'last_comment_timestamp' => 0,
        'last_comment_name' => '',
        'last_comment_uid' => 0,
        'comment_count' => 0,
      ];
    }
    return $values;
  }

}
