<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\field\formatter\TaxonomyFormatterBase.
 */

namespace Drupal\taxonomy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;

/**
 * Base class for the taxonomy_term formatters.
 */
abstract class TaxonomyFormatterBase extends FormatterBase {

  /**
   * {@inheritdoc}
   *
   * This preloads all taxonomy terms for multiple loaded objects at once and
   * unsets values for invalid terms that do not exist.
   */
  public function prepareView(array $entities_items) {
    $terms = array();

    // Collect every possible term attached to any of the fieldable entities.
    foreach ($entities_items as $items) {
      foreach ($items->referencedEntities() as $term) {
        $terms[$term->id()] = $term;
      }
    }
    if ($terms) {
      // Iterate through the fieldable entities again to attach the loaded term
      // data.
      foreach ($entities_items as $items) {
        $rekey = FALSE;

        foreach ($items as $item) {
          // Check whether the taxonomy term field instance value could be
          // loaded.
          if (isset($terms[$item->target_id])) {
            // Replace the instance value with the term data.
            $item->entity = $terms[$item->target_id];
          }
          // Terms to be created are not in $terms, but are still legitimate.
          elseif ($item->hasUnsavedEntity()) {
            // Leave the item in place.
          }
          // Otherwise, unset the instance value, since the term does not exist.
          else {
            $item->setValue(NULL);
            $rekey = TRUE;
          }
        }

        // Rekey the items array if needed.
        if ($rekey) {
          $items->filterEmptyItems();
        }
      }
    }
  }

}
