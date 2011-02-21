<?php
// $Id: views_handler_field_search_score.inc,v 1.2.6.1 2010/12/09 21:31:10 merlinofchaos Exp $

/**
 * Field handler to provide simple renderer that allows linking to a node.
 */
class views_handler_field_search_score extends views_handler_field_numeric {
  function option_definition() {
    $options = parent::option_definition();

    $options['alternate_sort'] = array('default' => '');
    $options['alternate_order'] = array('default' => 'asc');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $style_options = $this->view->display_handler->get_option('style_options');
    if (isset($style_options['default']) && $style_options['default'] == $this->options['id']) {
      $handlers = $this->view->display_handler->get_handlers('field');
      $options = array('' => t('No alternate'));
      foreach ($handlers as $id => $handler) {
        $options[$id] = $handler->ui_name();
      }

      $form['alternate_sort'] = array(
        '#type' => 'select',
        '#title' => t('Alternative sort'),
        '#description' => t('If no search is performed and this field does not appear, pick an alternative default table sort field.'),
        '#options' => $options,
        '#default_value' => $this->options['alternate_sort'],
      );

      $form['alternate_order'] = array(
        '#type' => 'select',
        '#title' => t('Alternate sort order'),
        '#options' => array('asc' => t('Ascending'), 'desc' => t('Descending')),
        '#default_value' => $this->options['alternate_order'],
      );
    }

    parent::options_form($form, $form_state);
  }

  function query() {
    // Check to see if the search filter added 'score' to the table.
    // Our filter stores it as $handler->search_score -- and we also
    // need to check its relationship to make sure that we're using the same
    // one or obviously this won't work.
    foreach ($this->view->filter as $handler) {
      if (isset($handler->search_score) && $handler->relationship == $this->relationship) {
        $this->field_alias = $handler->search_score;
        $this->table_alias = $handler->table_alias;
        return;
      }
    }

    // Hide this field if no search filter is in place.
    $this->options['exclude'] = TRUE;
    if (!empty($this->options['alternate_sort'])) {
      if (isset($this->view->style_plugin->options['default']) && $this->view->style_plugin->options['default'] == $this->options['id']) {
        // Since the style handler initiates fields, we plug these values right into the active handler.
        $this->view->style_plugin->options['default'] = $this->options['alternate_sort'];
        $this->view->style_plugin->options['order'] = $this->options['alternate_order'];
      }
    }
  }

  function render($values) {
    // Only render if we exist.
    if (isset($this->table_alias)) {
      return parent::render($values);
    }
  }

}
