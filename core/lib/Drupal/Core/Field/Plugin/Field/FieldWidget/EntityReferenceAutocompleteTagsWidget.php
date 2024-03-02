<?php

namespace Drupal\Core\Field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'entity_reference_autocomplete_tags' widget.
 */
#[FieldWidget(
  id: 'entity_reference_autocomplete_tags',
  label: new TranslatableMarkup('Autocomplete (Tags style)'),
  description: new TranslatableMarkup('An autocomplete text field with tagging support.'),
  field_types: ['entity_reference'],
  multiple_values: TRUE,
)]
class EntityReferenceAutocompleteTagsWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['target_id']['#tags'] = TRUE;
    $element['target_id']['#default_value'] = $items->referencedEntities();

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $values['target_id'];
  }

}
