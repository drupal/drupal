<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\field\Node.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to provide simple renderer that allows linking to a node.
 * Definition terms:
 * - link_to_node default: Should this field have the checkbox "link to node" enabled by default.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("node")
 */
class Node extends FieldPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Don't add the additional fields to groupby
    if (!empty($this->options['link_to_node'])) {
      $this->additional_fields['nid'] = array('table' => 'node', 'field' => 'nid');
      if (module_exists('translation')) {
        $this->additional_fields['langcode'] = array('table' => 'node', 'field' => 'langcode');
      }
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_node'] = array('default' => isset($this->definition['link_to_node default']) ? $this->definition['link_to_node default'] : FALSE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Provide link to node option
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['link_to_node'] = array(
      '#title' => t('Link this field to the original piece of content'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_node']),
    );

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Render whatever the data is as a link to the node.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $values) {
    if (!empty($this->options['link_to_node']) && !empty($this->additional_fields['nid'])) {
      if ($data !== NULL && $data !== '') {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['path'] = "node/" . $this->getValue($values, 'nid');
        if (isset($this->aliases['langcode'])) {
          $languages = language_list();
          $langcode = $this->getValue($values, 'langcode');
          if (isset($languages[$langcode])) {
            $this->options['alter']['language'] = $languages[$langcode];
          }
          else {
            unset($this->options['alter']['language']);
          }
        }
      }
      else {
        $this->options['alter']['make_link'] = FALSE;
      }
    }
    return $data;
  }

  function render($values) {
    $value = $this->getValue($values);
    return $this->render_link($this->sanitizeValue($value), $values);
  }

}
