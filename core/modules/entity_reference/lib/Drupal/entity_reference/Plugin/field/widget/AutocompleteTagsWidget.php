<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\field\widget\AutocompleteTagsWidget.
 */

namespace Drupal\entity_reference\Plugin\field\widget;

use Drupal\field\Annotation\FieldWidget;
use Drupal\Core\Annotation\Translation;
use Drupal\entity_reference\Plugin\field\widget\AutocompleteWidgetBase;

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
 *   settings = {
 *     "match_operator" = "CONTAINS",
 *     "size" = 60,
 *     "autocomplete_type" = "tags",
 *     "placeholder" = ""
 *   },
 *   multiple_values = TRUE
 * )
 */
class AutocompleteTagsWidget extends AutocompleteWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function elementValidate($element, &$form_state, $form) {
    $value = array();
    // If a value was entered into the autocomplete.
    $handler = \Drupal::service('plugin.manager.entity_reference.selection')->getSelectionHandler($this->fieldDefinition);
    $bundles = entity_get_bundles($this->getFieldSetting('target_type'));
    $auto_create = $this->getSelectionHandlerSetting('auto_create');

    if (!empty($element['#value'])) {
      $value = array();
      foreach (drupal_explode_tags($element['#value']) as $input) {
        $match = FALSE;

        // Take "label (entity id)', match the id from parenthesis.
        if (preg_match("/.+\((\d+)\)/", $input, $matches)) {
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
          // Auto-create item. see entity_reference_field_presave().
          $value[] = array(
            'target_id' => 0,
            'entity' => $this->createNewEntity($input, $element['#autocreate_uid']),
          );
        }
      }
    };
    // Change the element['#parents'], so in form_set_value() we
    // populate the correct key.
    array_pop($element['#parents']);
    form_set_value($element, $value, $form_state);
  }
}
