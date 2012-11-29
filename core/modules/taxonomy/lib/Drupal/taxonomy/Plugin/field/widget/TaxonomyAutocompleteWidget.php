<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\field\widget\TaxonomyAutocompleteWidget.
 */

namespace Drupal\taxonomy\Plugin\field\widget;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'taxonomy_autocomplete' widget.
 *
 * @Plugin(
 *   id = "taxonomy_autocomplete",
 *   module = "taxonomy",
 *   label = @Translation("Autocomplete term widget (tagging)"),
 *   field_types = {
 *     "taxonomy_term_reference"
 *   },
 *   settings = {
 *     "size" = "60",
 *     "autocomplete_path" = "taxonomy/autocomplete",
 *     "placeholder" = ""
 *   },
 *   multiple_values = TRUE
 * )
 */
class TaxonomyAutocompleteWidget extends WidgetBase {

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('The placeholder is a short hint (a word or short phrase) intended to aid the user with data entry. A hint could be a sample value or a brief description of the expected format.'),
    );
    return $element;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $field = $this->field;

    $tags = array();
    foreach ($items as $item) {
      $tags[$item['tid']] = isset($item['taxonomy_term']) ? $item['taxonomy_term'] : taxonomy_term_load($item['tid']);
    }
    $element += array(
      '#type' => 'textfield',
      '#default_value' => taxonomy_implode_tags($tags),
      '#autocomplete_path' => $this->getSetting('autocomplete_path') . '/' . $field['field_name'],
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => 1024,
      '#element_validate' => array('taxonomy_autocomplete_validate'),
    );

    return $element;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::massageFormValues()
   */
  public function massageFormValues(array $values, array $form, array &$form_state) {
    // Autocomplete widgets do not send their tids in the form, so we must detect
    // them here and process them independently.
    $terms = array();
    $field = $this->field;

    // Collect candidate vocabularies.
    foreach ($field['settings']['allowed_values'] as $tree) {
      if ($vocabulary = taxonomy_vocabulary_machine_name_load($tree['vocabulary'])) {
        $vocabularies[$vocabulary->vid] = $vocabulary;
      }
    }

    // Translate term names into actual terms.
    foreach($values as $value) {
      // See if the term exists in the chosen vocabulary and return the tid;
      // otherwise, create a new 'autocreate' term for insert/update.
      if ($possibilities = entity_load_multiple_by_properties('taxonomy_term', array('name' => trim($value), 'vid' => array_keys($vocabularies)))) {
        $term = array_pop($possibilities);
      }
      else {
        $vocabulary = reset($vocabularies);
        $term = array(
          'tid' => 'autocreate',
          'vid' => $vocabulary->vid,
          'name' => $value,
          'vocabulary_machine_name' => $vocabulary->machine_name,
        );
      }
      $terms[] = (array)$term;
    }

    return $terms;
  }

}
