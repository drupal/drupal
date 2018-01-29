<?php

namespace Drupal\image_module_test\Plugin\Field\FieldWidget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default widget for Dummy AJAX test.
 *
 * @FieldWidget(
 *   id = "image_module_test_dummy_ajax_widget",
 *   label = @Translation("Dummy AJAX widget"),
 *   field_types = {
 *     "image_module_test_dummy_ajax"
 *   },
 *   multiple_values = TRUE,
 * )
 */
class DummyAjaxWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['select_widget'] = [
      '#type' => 'select',
      '#title' => $this->t('Dummy select'),
      '#options' => ['pow' => 'Pow!', 'bam' => 'Bam!'],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => get_called_class() . '::dummyAjaxCallback',
        'effect' => 'fade',
      ],
    ];

    return $element;
  }

  /**
   * Ajax callback for Dummy AJAX test.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public static function dummyAjaxCallback(array &$form, FormStateInterface $form_state) {
    return new AjaxResponse();
  }

}
