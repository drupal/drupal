<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\argument_validator\Term.
 */

namespace Drupal\taxonomy\Plugin\views\argument_validator;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\argument_validator\Entity;

/**
 * Adds legacy vocabulary handling to standard Entity Argument validation..
 */
class Term extends Entity {

  /**
   * Overrides \Drupal\views\Plugin\views\Plugin\views\PluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // @todo Remove the legacy code.
    // Convert legacy vids option to machine name vocabularies.
    if (!empty($this->options['vids'])) {
      $vocabularies = taxonomy_vocabulary_get_names();
      foreach ($this->options['vids'] as $vid) {
        if (isset($vocabularies[$vid], $vocabularies[$vid]->machine_name)) {
          $this->options['vocabularies'][$vocabularies[$vid]->machine_name] = $vocabularies[$vid]->machine_name;
        }
      }
    }
  }
}
