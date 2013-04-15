<?php

/**
 * @file
 * Contains \Drupal\rest\Plugin\views\row\DataEntityRow.
 */

namespace Drupal\rest\Plugin\views\row;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Plugin which displays entities as raw data.
 *
 * @ingroup views_row_plugins
 *
 * @Plugin(
 *   id = "data_entity",
 *   module = "rest",
 *   title = @Translation("Entity"),
 *   help = @Translation("Use entities as row data."),
 *   display_types = {"data"}
 * )
 */
class DataEntityRow extends RowPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\row\RowPluginBase::render().
   */
  public function render($row) {
    return $row->_entity;
  }

}
