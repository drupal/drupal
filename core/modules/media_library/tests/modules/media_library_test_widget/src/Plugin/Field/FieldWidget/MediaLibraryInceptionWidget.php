<?php

namespace Drupal\media_library_test_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\media_library\Plugin\Field\FieldWidget\MediaLibraryWidget;

/**
 * Plugin implementation of the 'media_library_inception_widget' widget.
 *
 * This widget is used to simulate the media library widget nested inside
 * another widget that performs validation of required fields before there is
 * an opportunity to add media.
 *
 * @FieldWidget(
 *   id = "media_library_inception_widget",
 *   label = @Translation("Media library inception widget"),
 *   description = @Translation("Puts a widget in a widget for testing purposes."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE,
 * )
 */
class MediaLibraryInceptionWidget extends MediaLibraryWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if (empty($element['#element_validate'])) {
      $element['#element_validate'] = [];
    }
    $element['#element_validate'][] = [$this, 'elementValidate'];
    return parent::formElement($items, $delta, $element, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function elementValidate($element, FormStateInterface $form_state, $form) {
    $field_name = $element['#field_name'];
    $entity = $form_state->getFormObject()->getEntity();
    $input = $form_state->getUserInput();
    if (!empty($input['_triggering_element_name']) && str_contains($input['_triggering_element_name'], 'media-library-update')) {
      // This will validate a required field before an upload is completed.
      $display = EntityFormDisplay::collectRenderDisplay($entity, 'edit');
      $display->extractFormValues($entity, $form, $form_state);
      $display->validateFormValues($entity, $form, $form_state);
    }
    $form_value = $form_state->getValue($field_name);
    if (!empty($form_value['media_library_selection'])) {
      $entity->set($field_name, $form_value['media_library_selection']);
    }
  }

}
