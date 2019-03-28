<?php

namespace Drupal\layout_builder\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A widget to display the layout form.
 *
 * @FieldWidget(
 *   id = "layout_builder_widget",
 *   label = @Translation("Layout Builder Widget"),
 *   description = @Translation("A field widget for Layout Builder."),
 *   field_types = {
 *     "layout_section",
 *   },
 *   multiple_values = TRUE,
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class LayoutBuilderWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type' => 'layout_builder',
      '#section_storage' => $this->getSectionStorage($form_state),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    // @todo This isn't resilient to being set twice, during validation and
    //   save https://www.drupal.org/project/drupal/issues/2833682.
    if (!$form_state->isValidationComplete()) {
      return;
    }

    $items->setValue($this->getSectionStorage($form_state)->getSections());
  }

  /**
   * Gets the section storage.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface
   *   The section storage loaded from the tempstore.
   */
  private function getSectionStorage(FormStateInterface $form_state) {
    return $form_state->getFormObject()->getSectionStorage();
  }

}
