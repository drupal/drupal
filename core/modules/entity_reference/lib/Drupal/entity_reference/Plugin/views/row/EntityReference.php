<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\views\row\EntityReference.
 */

namespace Drupal\entity_reference\Plugin\views\row;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\row\Fields;

/**
 * EntityReference row plugin.
 *
 * @ingroup views_row_plugins
 *
 * @Plugin(
 *   id = "entity_reference",
 *   title = @Translation("Entity Reference inline fields"),
 *   help = @Translation("Displays the fields with an optional template."),
 *   theme = "views_view_fields",
 *   display_types = {"entity_reference"}
 * )
 */
class EntityReference extends Fields {

  /**
   * Overrides \Drupal\views\Plugin\views\row\Fields::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['separator'] = array('default' => '-');

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\row\Fields::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Expand the description of the 'Inline field' checkboxes.
    $form['inline']['#description'] .= '<br />' . t("<strong>Note:</strong> In 'Entity Reference' displays, all fields will be displayed inline unless an explicit selection of inline fields is made here." );
  }

  /**
   * {@inheritdoc}
   */
  public function preRender($row) {
    // Force all fields to be inline by default.
    if (empty($this->options['inline'])) {
      $fields = $this->view->getItems('field', $this->displayHandler->display['id']);
      $this->options['inline'] = drupal_map_assoc(array_keys($fields));
    }

    return parent::preRender($row);
  }
}
