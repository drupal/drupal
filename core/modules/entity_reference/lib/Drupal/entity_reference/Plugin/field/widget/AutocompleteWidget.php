<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\field\widget\AutocompleteWidget.
 */

namespace Drupal\entity_reference\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\entity_reference\Plugin\field\widget\AutocompleteWidgetBase;

/**
 * Plugin implementation of the 'entity_reference autocomplete' widget.
 *
 * @todo: Check if the following statement is still correct
 * The autocomplete path doesn't have a default here, because it's not the
 * the two widgets, and the Field API doesn't update default settings when
 * the widget changes.
 *
 * @Plugin(
 *   id = "entity_reference_autocomplete",
 *   module = "entity_reference",
 *   label = @Translation("Autocomplete"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   settings = {
 *     "match_operator" = "CONTAINS",
 *     "size" = 60,
 *     "autocomplete_path" = "entity_reference/autocomplete/single",
 *     "placeholder" = ""
 *   }
 * )
 */
class AutocompleteWidget extends AutocompleteWidgetBase {

  /**
   * Overrides \Drupal\entity_reference\Plugin\field\widget\AutocompleteWidgetBase::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    // We let the Field API handles multiple values for us, only take care of
    // the one matching our delta.
    if (isset($items[$delta])) {
      $items = array($items[$delta]);
    }
    else {
      $items = array();
    }

    return parent::formElement($items, $delta, $element, $langcode, $form, $form_state);
  }

  /**
   * Overrides \Drupal\entity_reference\Plugin\field\widget\AutocompleteWidgetBase::elementValidate()
   */
  public function elementValidate($element, &$form_state, $form) {
    $auto_create = isset($this->instance['settings']['handler_settings']['auto_create']) ? $this->instance['settings']['handler_settings']['auto_create'] : FALSE;

    // If a value was entered into the autocomplete.
    $value = '';
    if (!empty($element['#value'])) {
      // Take "label (entity id)', match the id from parenthesis.
      if (preg_match("/.+\((\d+)\)/", $element['#value'], $matches)) {
        $value = $matches[1];
      }
      else {
        // Try to get a match from the input string when the user didn't use the
        // autocomplete but filled in a value manually.
        $handler = entity_reference_get_selection_handler($this->field, $this->instance);
        $value = $handler->validateAutocompleteInput($element['#value'], $element, $form_state, $form, !$auto_create);
      }

      if (!$value && $auto_create && (count($this->instance['settings']['handler_settings']['target_bundles']) == 1)) {
        // Auto-create item. see entity_reference_field_presave().
        $value = array(
          'target_id' => 'auto_create',
          'label' => $element['#value'],
          // Keep the weight property.
          '_weight' => $element['#weight'],
        );
        // Change the element['#parents'], so in form_set_value() we
        // populate the correct key.
        array_pop($element['#parents']);
      }
    }
    form_set_value($element, $value, $form_state);
  }
}
