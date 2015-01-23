<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Field\FieldWidget\AutocompleteWidget.
 */

namespace Drupal\entity_reference\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;

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
 *   }
 * )
 */
class AutocompleteWidget extends AutocompleteWidgetBase {

  protected $usesOptions = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'match_operator' => 'CONTAINS',
      'size' => '60',
      'autocomplete_type' => 'tags',
      'placeholder' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function elementValidate($element, FormStateInterface $form_state, $form) {
    $auto_create = $this->getSelectionHandlerSetting('auto_create');

    // If a value was entered into the autocomplete.
    $value = NULL;
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
      if ($value === NULL) {
        // Try to get a match from the input string when the user didn't use the
        // autocomplete but filled in a value manually.
        $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getSelectionHandler($this->fieldDefinition);
        $value = $handler->validateAutocompleteInput($element['#value'], $element, $form_state, $form, !$auto_create);
      }

      if (!$value && $auto_create && (count($this->getSelectionHandlerSetting('target_bundles')) == 1)) {
        // Auto-create item. See
        // \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::presave().
        $value = array(
          'entity' => $this->createNewEntity($element['#value'], $element['#autocreate_uid']),
          // Keep the weight property.
          '_weight' => $element['#weight'],
        );
        // Change the element['#parents'], so in setValueForElement() we
        // populate the correct key.
        array_pop($element['#parents']);
      }
    }
    $form_state->setValueForElement($element, $value);
  }
}
