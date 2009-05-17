<?php

/**
 * @file
 * Definition of views_plugin_pager_some.
 */

/**
 * Plugin for views without pagers.
 *
 * @ingroup views_pager_plugins
 */
class views_plugin_pager_some extends views_plugin_pager {
  function summary_title() {
    if (!empty($this->options['offset'])) {
      return format_plural($this->options['items_per_page'], '@count item, skip @skip', '@count items, skip @skip', array('@count' => $this->options['items_per_page'], '@skip' => $this->options['offset']));
    }
      return format_plural($this->options['items_per_page'], '@count item', '@count items', array('@count' => $this->options['items_per_page']));
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['items_per_page'] = array('default' => 10);
    $options['offset'] = array('default' => 0);

    return $options;
  }

  /**
   * Provide the default form for setting options.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $pager_text = $this->display->handler->get_pager_text();
    $form['items_per_page'] = array(
      '#title' => $pager_text['items per page title'],
      '#type' => 'textfield',
      '#description' => $pager_text['items per page description'],
      '#default_value' => $this->options['items_per_page'],
    );

    $form['offset'] = array(
      '#type' => 'textfield',
      '#title' => t('Offset'),
      '#description' => t('The number of items to skip. For example, if this field is 3, the first 3 items will be skipped and not displayed.'),
      '#default_value' => $this->options['offset'],
    );
  }

  function use_pager() {
    return FALSE;
  }

  function use_count_query() {
    return FALSE;
  }

  function query() {
    $this->view->query->set_limit($this->options['items_per_page']);
    $this->view->query->set_offset($this->options['offset']);
  }
}
