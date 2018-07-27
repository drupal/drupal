<?php

namespace Drupal\path\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'path' widget.
 *
 * @FieldWidget(
 *   id = "path",
 *   label = @Translation("URL alias"),
 *   field_types = {
 *     "path"
 *   }
 * )
 */
class PathWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();

    $element += [
      '#element_validate' => [[get_class($this), 'validateFormElement']],
    ];
    $element['alias'] = [
      '#type' => 'textfield',
      '#title' => $element['#title'],
      '#default_value' => $items[$delta]->alias,
      '#required' => $element['#required'],
      '#maxlength' => 255,
      '#description' => $this->t('Specify an alternative path by which this data can be accessed. For example, type "/about" when writing an about page.'),
    ];
    $element['pid'] = [
      '#type' => 'value',
      '#value' => $items[$delta]->pid,
    ];
    $element['source'] = [
      '#type' => 'value',
      '#value' => !$entity->isNew() ? '/' . $entity->toUrl()->getInternalPath() : NULL,
     ];
    $element['langcode'] = [
      '#type' => 'value',
      '#value' => $items[$delta]->langcode,
    ];

    // If the advanced settings tabs-set is available (normally rendered in the
    // second column on wide-resolutions), place the field as a details element
    // in this tab-set.
    if (isset($form['advanced'])) {
      $element += [
        '#type' => 'details',
        '#title' => t('URL path settings'),
        '#open' => !empty($items[$delta]->alias),
        '#group' => 'advanced',
        '#access' => $entity->get('path')->access('edit'),
        '#attributes' => [
          'class' => ['path-form'],
        ],
        '#attached' => [
          'library' => ['path/drupal.path'],
        ],
      ];
      $element['#weight'] = 30;
    }

    return $element;
  }

  /**
   * Form element validation handler for URL alias form element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateFormElement(array &$element, FormStateInterface $form_state) {
    // Trim the submitted value of whitespace and slashes.
    $alias = rtrim(trim($element['alias']['#value']), " \\/");
    if (!empty($alias)) {
      $form_state->setValueForElement($element['alias'], $alias);

      // Validate that the submitted alias does not exist yet.
      $is_exists = \Drupal::service('path.alias_storage')->aliasExists($alias, $element['langcode']['#value'], $element['source']['#value']);
      if ($is_exists) {
        $form_state->setError($element['alias'], t('The alias is already in use.'));
      }
    }

    if ($alias && $alias[0] !== '/') {
      $form_state->setError($element['alias'], t('The alias needs to start with a slash.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element['alias'];
  }

}
