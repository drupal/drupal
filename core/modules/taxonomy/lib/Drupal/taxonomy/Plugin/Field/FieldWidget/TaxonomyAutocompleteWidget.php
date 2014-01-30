<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\Field\FieldWidget\TaxonomyAutocompleteWidget.
 */

namespace Drupal\taxonomy\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;

/**
 * Plugin implementation of the 'taxonomy_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "taxonomy_autocomplete",
 *   label = @Translation("Autocomplete term widget (tagging)"),
 *   field_types = {
 *     "taxonomy_term_reference"
 *   },
 *   settings = {
 *     "size" = "60",
 *     "autocomplete_route_name" = "taxonomy.autocomplete",
 *     "placeholder" = ""
 *   },
 *   multiple_values = TRUE
 * )
 */
class TaxonomyAutocompleteWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $summary[] = t('Textfield size: !size', array('!size' => $this->getSetting('size')));
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $placeholder));
    }
    else {
      $summary[] = t('No placeholder');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $tags = array();
    foreach ($items as $item) {
      $tags[] = isset($item->entity) ? $item->entity : entity_load('taxonomy_term', $item->target_id);
    }
    $element += array(
      '#type' => 'textfield',
      '#default_value' => taxonomy_implode_tags($tags),
      '#autocomplete_route_name' => $this->getSetting('autocomplete_route_name'),
      '#autocomplete_route_parameters' => array(
        'entity_type' => $items->getEntity()->getEntityTypeId(),
        'field_name' => $this->fieldDefinition->getName(),
      ),
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => 1024,
      '#element_validate' => array('taxonomy_autocomplete_validate'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, array &$form_state) {
    // Autocomplete widgets do not send their tids in the form, so we must detect
    // them here and process them independently.
    $items = array();

    // Collect candidate vocabularies.
    foreach ($this->getFieldSetting('allowed_values') as $tree) {
      if ($vocabulary = entity_load('taxonomy_vocabulary', $tree['vocabulary'])) {
        $vocabularies[$vocabulary->id()] = $vocabulary;
      }
    }

    // Translate term names into actual terms.
    foreach($values as $value) {
      // See if the term exists in the chosen vocabulary and return the tid;
      // otherwise, create a new term.
      if ($possibilities = entity_load_multiple_by_properties('taxonomy_term', array('name' => trim($value), 'vid' => array_keys($vocabularies)))) {
        $term = array_pop($possibilities);
        $item = array('target_id' => $term->id());
      }
      else {
        $vocabulary = reset($vocabularies);
        $term = entity_create('taxonomy_term', array(
          'vid' => $vocabulary->id(),
          'name' => $value,
        ));
        $item = array('target_id' => NULL, 'entity' => $term);
      }
      $items[] = $item;
    }

    return $items;
  }

}
