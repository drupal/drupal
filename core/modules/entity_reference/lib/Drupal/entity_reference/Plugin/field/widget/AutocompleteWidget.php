<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\field\widget\AutocompleteWidget.
 */

namespace Drupal\entity_reference\Plugin\field\widget;

use Drupal\Core\Entity\Field\FieldItemListInterface;
use Drupal\entity_reference\Plugin\field\widget\AutocompleteWidgetBase;

/**
 * Plugin implementation of the 'entity_reference autocomplete' widget.
 *
 * @todo: Check if the following statement is still correct
 * The autocomplete path doesn't have a default here, because it's not the
 * the two widgets, and the Field API doesn't update default settings when
 * the widget changes.
 *
 * @FieldWidget(
 *   id = "entity_reference_autocomplete",
 *   label = @Translation("Autocomplete"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   settings = {
 *     "match_operator" = "CONTAINS",
 *     "size" = 60,
 *     "autocomplete_type" = "single",
 *     "placeholder" = ""
 *   }
 * )
 */
class AutocompleteWidget extends AutocompleteWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    // We let the Field API handles multiple values for us, only take care of
    // the one matching our delta.
    if (isset($items[$delta])) {
      $items->setValue(array($items[$delta]->getValue()));
    }
    else {
      $items->setValue(array());
    }

    return parent::formElement($items, $delta, $element, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function elementValidate($element, &$form_state, $form) {
    $auto_create = $this->getSelectionHandlerSetting('auto_create');

    // If a value was entered into the autocomplete.
    $value = '';
    if (!empty($element['#value'])) {
      // Take "label (entity id)', match the id from parenthesis.
      // @todo: Lookup the entity type's ID data type and use it here.
      // https://drupal.org/node/2107249
      if ($this->isContentReferenced() && preg_match("/.+\((\d+)\)/", $element['#value'], $matches)) {
        $value = $matches[1];
      }
      elseif (preg_match("/.+\(([\w.]+)\)/", $element['#value'], $matches)) {
        $value = $matches[1];
      }
      if (!$value) {
        // Try to get a match from the input string when the user didn't use the
        // autocomplete but filled in a value manually.
        $handler = \Drupal::service('plugin.manager.entity_reference.selection')->getSelectionHandler($this->fieldDefinition);
        $value = $handler->validateAutocompleteInput($element['#value'], $element, $form_state, $form, !$auto_create);
      }

      if (!$value && $auto_create && (count($this->getSelectionHandlerSetting('target_bundles')) == 1)) {
        // Auto-create item. see entity_reference_field_presave().
        $value = array(
          'target_id' => 0,
          'entity' => $this->createNewEntity($element['#value'], $element['#autocreate_uid']),
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
