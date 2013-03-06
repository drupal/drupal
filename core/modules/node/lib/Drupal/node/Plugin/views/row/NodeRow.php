<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\row\NodeRow.
 */

namespace Drupal\node\Plugin\views\row;

use Drupal\views\ViewExecutable;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\system\Plugin\views\row\EntityRow;

/**
 * Plugin which performs a node_view on the resulting object.
 *
 * Most of the code on this object is in the theme function.
 *
 * @ingroup views_row_plugins
 *
 * @Plugin(
 *   id = "node",
 *   module = "node",
 *   title = @Translation("Content"),
 *   help = @Translation("Display the content with standard node view."),
 *   base = {"node"},
 *   entity_type = "node",
 *   type = "normal"
 * )
 */
class NodeRow extends EntityRow {

  /**
   * Overrides Drupal\system\Plugin\views\row\Entity::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode']['default'] = 'teaser';

    $options['links'] = array('default' => TRUE, 'bool' => TRUE);
    $options['comments'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Overrides Drupal\system\Plugin\views\row\Entity::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['links'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display links'),
      '#default_value' => $this->options['links'],
    );
    $form['comments'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display comments'),
      '#default_value' => $this->options['comments'],
    );
  }

}
