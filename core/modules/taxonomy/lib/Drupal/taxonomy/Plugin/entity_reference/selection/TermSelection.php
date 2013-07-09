<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\entity_reference\selection\TermSelection.
 */

namespace Drupal\taxonomy\Plugin\entity_reference\selection;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\entity_reference\Annotation\EntityReferenceSelection;
use Drupal\entity_reference\Plugin\entity_reference\selection\SelectionBase;

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
  public static function settingsForm(&$field, &$instance) {
    $form = parent::settingsForm($field, $instance);

    // @todo: Currently allow auto-create only on taxonomy terms.
    $form['auto_create'] = array(
      '#type' => 'checkbox',
      '#title' => t("Create referenced entities if they don't already exist"),
      '#default_value' => $instance['settings']['handler_settings']['auto_create'],
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
    $bundle_names = !empty($this->instance['settings']['handler_settings']['target_bundles']) ? $this->instance['settings']['handler_settings']['target_bundles'] : array_keys($bundles);

    foreach ($bundle_names as $bundle) {
      if ($vocabulary = entity_load('taxonomy_vocabulary', $bundle)) {
        if ($terms = taxonomy_get_tree($vocabulary->id(), 0)) {
          foreach ($terms as $term) {
            $options[$vocabulary->id()][$term->id()] = str_repeat('-', $term->depth) . check_plain($term->name);
          }
        }
      }
    }

    return $options;
  }
}
