<?php

/**
 * @file
 * Definition of Drupal\contextual\Plugin\views\field\ContextualLinks.
 */

namespace Drupal\contextual\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Provides a handler that adds contextual links.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("contextual_links")
 */
class ContextualLinks extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['fields'] = array('default' => array());
    $options['destination'] = array('default' => 1);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $all_fields = $this->view->display_handler->getFieldLabels();
    // Offer to include only those fields that follow this one.
    $field_options = array_slice($all_fields, 0, array_search($this->options['id'], array_keys($all_fields)));
    $form['fields'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Fields'),
      '#description' => t('Fields to be included as contextual links.'),
      '#options' => $field_options,
      '#default_value' => $this->options['fields'],
    );
    $form['destination'] = array(
      '#type' => 'select',
      '#title' => t('Include destination'),
      '#description' => t('Include a "destination" parameter in the link to return the user to the original view upon completing the contextual action.'),
      '#options' => array(
        '0' => t('No'),
        '1' => t('Yes'),
      ),
      '#default_value' => $this->options['destination'],
    );
  }

  function pre_render(&$values) {
    // Add a row plugin css class for the contextual link.
    $class = 'contextual-region';
    if (!empty($this->view->style_plugin->options['row_class'])) {
      $this->view->style_plugin->options['row_class'] .= " $class";
    }
    else {
      $this->view->style_plugin->options['row_class'] = $class;
    }
  }

  /**
   * Render the contextual fields.
   *
   * @see contextual_preprocess()
   * @see contextual_contextual_links_view_alter()
   */
  function render($values) {
    $links = array();
    foreach ($this->options['fields'] as $field) {
      $rendered_field = $this->view->style_plugin->get_field($this->view->row_index, $field);
      if (empty($rendered_field)) {
        continue;
      }
      $title = $this->view->field[$field]->last_render_text;
      $path = '';
      if (!empty($this->view->field[$field]->options['alter']['path'])) {
        $path = $this->view->field[$field]->options['alter']['path'];
      }
      if (!empty($title) && !empty($path)) {
        // Make sure that tokens are replaced for this paths as well.
        $tokens = $this->getRenderTokens(array());
        $path = strip_tags(decode_entities(strtr($path, $tokens)));

        $links[$field] = array(
          'href' => $path,
          'title' => $title,
        );
        if (!empty($this->options['destination'])) {
          $links[$field]['query'] = drupal_get_destination();
        }
      }
    }

    // Renders a contextual links placeholder.
    if (!empty($links)) {
      $contextual_links = array(
        'contextual' => array(
          '',
          array(),
          array(
            'contextual-views-field-links' => drupal_encode_path(drupal_json_encode($links)),
          )
        )
      );

      $element = array(
        '#type' => 'contextual_links_placeholder',
        '#id' => _contextual_links_to_id($contextual_links),
      );
      return drupal_render($element);
    }
    else {
      return '';
    }
  }

  public function query() { }

}
