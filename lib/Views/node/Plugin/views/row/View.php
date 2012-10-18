<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\row\View.
 */

namespace Views\node\Plugin\views\row;

use Drupal\views\ViewExecutable;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Views\system\Plugin\views\row\Entity;

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
class View extends Entity {

  /**
   * Overrides Views\system\Plugin\views\row\Entity::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode']['default'] = 'teaser';

    $options['links'] = array('default' => TRUE, 'bool' => TRUE);
    $options['comments'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Overrides Views\system\Plugin\views\row\Entity::buildOptionsForm().
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
