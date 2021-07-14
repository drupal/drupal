<?php

namespace Drupal\Core\Entity;

/**
 * Provides a helper to implement the getMatches method.
 */
trait EntityAutocompleteMatcherTrait {

  /**
   * {@inheritDoc}
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    $entities = $this->getEntities($target_type, $selection_handler, $selection_settings, $string);
    return $this->formatMatches($entities);
  }

  /**
   * {@inheritDoc}
   */
  abstract public function getEntities($target_type, $selection_handler, $selection_settings, $string = '');

  /**
   * {@inheritDoc}
   */
  abstract public function formatMatches(array $entities);
}
