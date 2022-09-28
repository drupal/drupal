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
      '#element_validate' => [[static::class, 'validateFormElement']],
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
        '#title' => $this->t('URL path settings'),
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
    if ($alias !== '') {
      $form_state->setValueForElement($element['alias'], $alias);

      /** @var \Drupal\path_alias\PathAliasInterface $path_alias */
      $path_alias = \Drupal::entityTypeManager()->getStorage('path_alias')->create([
        'path' => $element['source']['#value'],
        'alias' => $alias,
        'langcode' => $element['langcode']['#value'],
      ]);
      $violations = $path_alias->validate();

      foreach ($violations as $violation) {
        // Newly created entities do not have a system path yet, so we need to
        // disregard some violations.
        if (!$path_alias->getPath() && $violation->getPropertyPath() === 'path') {
          continue;
        }
        $form_state->setError($element['alias'], $violation->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element['alias'];
  }

}
