<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\field\Node.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Field handler to provide simple renderer that allows linking to a node.
 * Definition terms:
 * - link_to_node default: Should this field have the checkbox "link to node" enabled by default.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node")
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
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_node'] = array('default' => isset($this->definition['link_to_node default']) ? $this->definition['link_to_node default'] : FALSE);
    return $options;
  }

  /**
   * Provide link to node option
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_node'] = array(
      '#title' => $this->t('Link this field to the original piece of content'),
      '#description' => $this->t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_node']),
    );

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Prepares link to the node.
   *
   * @param string $data
   *   The XSS safe string for the link text.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    if (!empty($this->options['link_to_node']) && !empty($this->additional_fields['nid'])) {
      if ($data !== NULL && $data !== '') {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['url'] = Url::fromRoute('entity.node.canonical', ['node' => $this->getValue($values, 'nid')]);
        if (isset($this->aliases['langcode'])) {
          $languages = \Drupal::languageManager()->getLanguages();
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

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
