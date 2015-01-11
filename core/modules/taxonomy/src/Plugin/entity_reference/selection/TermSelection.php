<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\entity_reference\selection\TermSelection.
 */

namespace Drupal\taxonomy\Plugin\entity_reference\selection;

use Drupal\Component\Utility\String;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\entity_reference\Plugin\entity_reference\selection\SelectionBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides specific access control for the taxonomy_term entity type.
 *
 * @EntityReferenceSelection(
 *   id = "taxonomy_term_default",
 *   label = @Translation("Taxonomy Term selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "default",
 *   weight = 1
 * )
 */
class TermSelection extends SelectionBase {

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {
    // @todo: How to set access, as vocabulary is now config?
  }

  /**
   * {@inheritdoc}
   */
  public static function settingsForm(FieldDefinitionInterface $field_definition) {
    $form = parent::settingsForm($field_definition);
    $selection_handler_settings = $field_definition->getSetting('handler_settings');

    // @todo: Currently allow auto-create only on taxonomy terms.
    $form['auto_create'] = array(
      '#type' => 'checkbox',
      '#title' => t("Create referenced entities if they don't already exist"),
      '#default_value' => isset($selection_handler_settings['auto_create']) ? $selection_handler_settings['auto_create'] : FALSE,
    );
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    if ($match || $limit) {
      return parent::getReferenceableEntities($match , $match_operator, $limit);
    }

    $options = array();

    $bundles = entity_get_bundles('taxonomy_term');
    $handler_settings = $this->fieldDefinition->getSetting('handler_settings');
    $bundle_names = !empty($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : array_keys($bundles);

    foreach ($bundle_names as $bundle) {
      if ($vocabulary = Vocabulary::load($bundle)) {
        if ($terms = taxonomy_get_tree($vocabulary->id(), 0, NULL, TRUE)) {
          foreach ($terms as $term) {
            $options[$vocabulary->id()][$term->id()] = str_repeat('-', $term->depth) . String::checkPlain($term->getName());
          }
        }
      }
    }

    return $options;
  }
}
