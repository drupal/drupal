<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Field\FieldWidget\AutocompleteTagsWidget.
 */

namespace Drupal\entity_reference\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference autocomplete-tags' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_autocomplete_tags",
 *   label = @Translation("Autocomplete (Tags style)"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class AutocompleteTagsWidget extends AutocompleteWidgetBase {

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
    $value = array();
    // If a value was entered into the autocomplete.
    $handler = \Drupal::service('plugin.manager.entity_reference.selection')->getSelectionHandler($this->fieldDefinition);
    $bundles = entity_get_bundles($this->getFieldSetting('target_type'));
    $auto_create = $this->getSelectionHandlerSetting('auto_create');

    if (!empty($element['#value'])) {
      $value = array();
      foreach (Tags::explode($element['#value']) as $input) {
        $match = FALSE;

        // Take "label (entity id)', match the ID from parenthesis when it's a
        // number.
        if (preg_match("/.+\((\d+)\)/", $input, $matches)) {
          $match = $matches[1];
        }
        // Match the ID when it's a string (e.g. for config entity types).
        elseif (preg_match("/.+\(([\w.]+)\)/", $input, $matches)) {
          $match = $matches[1];
        }
        else {
          // Try to get a match from the input string when the user didn't use
          // the autocomplete but filled in a value manually.
          $match = $handler->validateAutocompleteInput($input, $element, $form_state, $form, !$auto_create);
        }

        if ($match) {
          $value[] = array('target_id' => $match);
        }
        elseif ($auto_create && (count($this->getSelectionHandlerSetting('target_bundles')) == 1 || count($bundles) == 1)) {
          // Auto-create item. See
          // \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::presave().
          $value[] = array(
            'target_id' => NULL,
            'entity' => $this->createNewEntity($input, $element['#autocreate_uid']),
          );
        }
      }
    };
    // Change the element['#parents'], so in setValueForElement() we populate
    // the correct key.
    array_pop($element['#parents']);
    $form_state->setValueForElement($element, $value);
  }
}
