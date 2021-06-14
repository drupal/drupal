<?php

namespace Drupal\field_test\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'test_field_widget_multilingual' widget.
 *
 * @FieldWidget(
 *   id = "test_field_widget_multilingual",
 *   label = @Translation("Test widget - multilingual"),
 *   field_types = {
 *     "test_field",
 *   },
 * )
 */
class TestFieldWidgetMultilingual extends TestFieldWidget {

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $elements = parent::form($items, $form, $form_state, $get_delta);
    $elements['#multilingual'] = TRUE;
    return $elements;
  }

}
