<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter.
 */

namespace Drupal\entity_reference\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_label",
 *   label = @Translation("Label"),
 *   description = @Translation("Display the label of the referenced entities."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   settings = {
 *     "link" = TRUE
 *   }
 * )
 */
class EntityReferenceLabelFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $elements['link'] = array(
      '#title' => t('Link label to the referenced entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = $this->getSetting('link') ? t('Link to the referenced entity') : t('No link');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if (!$item->access) {
        // User doesn't have access to the referenced entity.
        continue;
      }
      /** @var $referenced_entity \Drupal\Core\Entity\EntityInterface */
      if ($referenced_entity = $item->entity) {
        $label = $referenced_entity->label();
        // If the link is to be displayed and the entity has a uri,
        // display a link.
        if ($this->getSetting('link') && $uri = $referenced_entity->urlInfo()) {
          $elements[$delta] = array(
            '#type' => 'link',
            '#title' => $label,
            '#route_name' => $uri['route_name'],
            '#route_parameters' => $uri['route_parameters'],
            '#options' => $uri['options'],
          );
        }
        else {
          $elements[$delta] = array('#markup' => check_plain($label));
        }
      }
    }

    return $elements;
  }

}
