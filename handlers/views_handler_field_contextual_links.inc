<?php

/**
 * @file
 * Definition of views_handler_field_contextual_links.
 */

/**
 * Provides a handler that adds contextual links.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_contextual_links extends views_handler_field {
  function option_definition() {
    $options = parent::option_definition();

    $options['fields'] = array('default' => array());
    $options['destination'] = array('default' => 1);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $all_fields = $this->view->display_handler->get_field_labels();
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
    $class = 'contextual-links-region';
    if (!empty($this->view->style_plugin->options['row_class'])) {
      $this->view->style_plugin->options['row_class'] .= " $class";
    }
    else {
      $this->view->style_plugin->options['row_class'] = $class;
    }
  }

  /**
   * Render the contextual fields.
   */
  function render($values) {
    $links = array();
    foreach ($this->options['fields'] as $field) {
      if (empty($this->view->field[$field]->last_render_text)) {
        continue;
      }
      $title = $this->view->field[$field]->last_render_text;
      $path = '';
      if (!empty($this->view->field[$field]->options['alter']['path'])) {
        $path = $this->view->field[$field]->options['alter']['path'];
      }
      if (!empty($title)) {
        // Make sure that tokens are replaced for this paths as well.
        $tokens = $this->get_render_tokens(array());
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

    if (!empty($links)) {
      $build = array(
        '#prefix' => '<div class="contextual-links-wrapper">',
        '#suffix' => '</div>',
        '#theme' => 'links__contextual',
        '#links' => $links,
        '#attributes' => array('class' => array('contextual-links')),
        '#attached' => array(
          'library' => array(array('contextual', 'contextual-links')),
        ),
        '#access' => user_access('access contextual links'),
      );
      return drupal_render($build);
    }
    else {
      return '';
    }
  }

  function query() { }
}
