<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Type\Selection\SelectionBroken.
 */

namespace Drupal\entity_reference\Plugin\Type\Selection;

use Drupal\Core\Database\Query\SelectInterface;

/**
 * A null implementation of SelectionInterface.
 */
class SelectionBroken implements SelectionInterface {

  /**
   * Implements SelectionInterface::settingsForm().
   */
  public static function settingsForm(&$field, &$instance) {
    $form['selection_handler'] = array(
      '#markup' => t('The selected selection handler is broken.'),
    );
    return $form;
  }

  /**
   * Implements SelectionInterface::getReferencableEntities().
   */
  public function getReferencableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    return array();
  }

  /**
   * Implements SelectionInterface::countReferencableEntities().
   */
  public function countReferencableEntities($match = NULL, $match_operator = 'CONTAINS') {
    return 0;
  }

  /**
   * Implements SelectionInterface::validateReferencableEntities().
   */
  public function validateReferencableEntities(array $ids) {
    return array();
  }

  /**
   * Implements SelectionInterface::validateAutocompleteInput().
   */
  public function validateAutocompleteInput($input, &$element, &$form_state, $form, $strict = TRUE) { }

  /**
   * Implements SelectionInterface::entityQueryAlter().
   */
  public function entityQueryAlter(SelectInterface $query) { }
}
