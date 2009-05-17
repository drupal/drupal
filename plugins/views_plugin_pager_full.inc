<?php

/**
 * @file
 * Definition of views_plugin_pager_full.
 */

/**
 * The plugin to handle full pager.
 *
 * @ingroup views_pager_plugins
 */
class views_plugin_pager_full extends views_plugin_pager {
  function summary_title() {
    if (!empty($this->options['offset'])) {
      return format_plural($this->options['items_per_page'], '@count item, skip @skip', 'Paged, @count items, skip @skip', array('@count' => $this->options['items_per_page'], '@skip' => $this->options['offset']));
    }
      return format_plural($this->options['items_per_page'], '@count item', 'Paged, @count items', array('@count' => $this->options['items_per_page']));
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['items_per_page'] = array('default' => 10);
    $options['offset'] = array('default' => 0);
    $options['id'] = array('default' => 0);
    $options['total_pages'] = array('default' => '');
    // Use the same default quantity that core uses by default.
    $options['quantity'] = array('default' => 9);
    $options['expose'] = array(
      'contains' => array(
        'items_per_page' => array('default' => FALSE, 'bool' => TRUE),
        'items_per_page_label' => array('default' => 'Items per page', 'translatable' => TRUE),
        'items_per_page_options' => array('default' => '5, 10, 20, 40, 60'),
        'items_per_page_options_all' => array('default' => FALSE, 'bool' => TRUE),
        'items_per_page_options_all_label' => array('default' => '- All -', 'translatable' => TRUE),

        'offset' => array('default' => FALSE, 'bool' => TRUE),
        'offset_label' => array('default' => 'Offset', 'translatable' => TRUE),
      ),
    );
    $options['tags'] = array(
      'contains' => array(
        'first' => array('default' => '« first', 'translatable' => TRUE),
        'previous' => array('default' => '‹ previous', 'translatable' => TRUE),
        'next' => array('default' => 'next ›', 'translatable' => TRUE),
        'last' => array('default' => 'last »', 'translatable' => TRUE),
      ),
    );
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

    $form['id'] = array(
      '#type' => 'textfield',
      '#title' => t('Pager ID'),
      '#description' => t("Unless you're experiencing problems with pagers related to this view, you should leave this at 0. If using multiple pagers on one page you may need to set this number to a higher value so as not to conflict within the ?page= array. Large values will add a lot of commas to your URLs, so avoid if possible."),
      '#default_value' => $this->options['id'],
    );

    $form['total_pages'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of pages'),
      '#description' => t('The total number of pages. Leave empty to show all pages.'),
      '#default_value' => $this->options['total_pages'],
    );

    $form['quantity'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of pager links visible'),
      '#description' => t('Specify the number of links to pages to display in the pager.'),
      '#default_value' => $this->options['quantity'],
    );

    $form['tags'] = array (
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
      '#title' => t('Tags'),
      '#input' => TRUE,
      '#description' => t('A lists of labels for the controls in the pager'),
    );

    $form['tags']['first'] = array(
      '#type' => 'textfield',
      '#title' => t('Text for "first"-link'),
      '#description' => t('Text for "first"-link'),
      '#default_value' => $this->options['tags']['first'],
    );

    $form['tags']['previous'] = array(
      '#type' => 'textfield',
      '#title' => t('Text for "previous"-link'),
      '#description' => t('Text for "previous"-link'),
      '#default_value' => $this->options['tags']['previous'],
    );

    $form['tags']['next'] = array(
      '#type' => 'textfield',
      '#title' => t('Text for "next"-link'),
      '#description' => t('Text for "next"-link'),
      '#default_value' => $this->options['tags']['next'],
    );

    $form['tags']['last'] = array(
      '#type' => 'textfield',
      '#title' => t('Text for "last"-link'),
      '#description' => t('Text for "last"-link'),
      '#default_value' => $this->options['tags']['last'],
    );

    $form['expose'] = array (
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
      '#title' => t('Exposed options'),
      '#input' => TRUE,
      '#description' => t('Exposing this options allows users to define their values in a exposed form when view is displayed'),
    );

    $form['expose']['items_per_page'] = array(
      '#type' => 'checkbox',
      '#title' => t('Expose items per page'),
      '#description' => t('When checked, users can determine how many items per page show in a view'),
      '#default_value' => $this->options['expose']['items_per_page'],
    );

    $form['expose']['items_per_page_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Items per page label'),
      '#required' => TRUE,
      '#description' => t('Label to use in the exposed items per page form element.'),
      '#default_value' => $this->options['expose']['items_per_page_label'],
      '#dependency' => array(
        'edit-pager-options-expose-items-per-page' => array(1)
      ),
    );

    $form['expose']['items_per_page_options'] = array(
      '#type' => 'textfield',
      '#title' => t('Exposed items per page options'),
      '#required' => TRUE,
      '#description' => t('Set between which values the user can choose when determining the items per page. Separated by comma.'),
      '#default_value' => $this->options['expose']['items_per_page_options'],
      '#dependency' => array(
        'edit-pager-options-expose-items-per-page' => array(1)
      ),
    );


    $form['expose']['items_per_page_options_all'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include all items option'),
      '#description' => t('If checked, an extra item will be included to items per page to display all items'),
      '#default_value' => $this->options['expose']['items_per_page_options_all'],
    );

    $form['expose']['items_per_page_options_all_label'] = array(
      '#type' => 'textfield',
      '#title' => t('All items label'),
      '#description' => t('Which label will be used to display all items'),
      '#default_value' => $this->options['expose']['items_per_page_options_all_label'],
      '#dependency' => array(
        'edit-items-per-page-options-all' => array(1),
      ),
    );

    $form['expose']['offset'] = array(
      '#type' => 'checkbox',
      '#title' => t('Expose Offset'),
      '#description' => t('When checked, users can determine how many items should be skipped at the beginning.'),
      '#default_value' => $this->options['expose']['offset'],
    );

    $form['expose']['offset_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Offset label'),
      '#required' => TRUE,
      '#description' => t('Label to use in the exposed offset form element.'),
      '#default_value' => $this->options['expose']['offset_label'],
      '#dependency' => array(
        'edit-pager-options-expose-offset' => array(1)
      ),
    );
  }

  function options_validate(&$form, &$form_state) {
    // Only accept integer values.
    $error = FALSE;
    $exposed_options = $form_state['values']['pager_options']['expose']['items_per_page_options'];
    if (strpos($exposed_options, '.') !== FALSE) {
      $error = TRUE;
    }
    $options = explode(',',$exposed_options);
    if (!$error && is_array($options)) {
      foreach ($options as $option) {
        if (!is_numeric($option) || intval($option) == 0) {
          $error = TRUE;
        }
      }
    }
    else {
      $error = TRUE;
    }
    if ($error) {
      form_set_error('pager_options][expose][items_per_page_options', t('Please insert a list of integer numeric values separated by commas: e.g: 10, 20, 50, 100'));
    }

    // Take sure that the items_per_page is part of the expose settings.
    if (!empty($form_state['values']['pager_options']['expose']['items_per_page']) && !empty($form_state['values']['pager_options']['items_per_page'])) {
      $items_per_page = $form_state['values']['pager_options']['items_per_page'];
      if (array_search($items_per_page, $options) === FALSE) {
        form_set_error('pager_options][expose][items_per_page_options', t('Please insert the items per page (@items_per_page) from above.',
          array('@items_per_page' => $items_per_page))
        );
      }
    }
  }

  function query() {
    if ($this->items_per_page_exposed()) {
      if (!empty($_GET['items_per_page']) && $_GET['items_per_page'] > 0) {
        $this->options['items_per_page'] = $_GET['items_per_page'];
      }
      elseif (!empty($_GET['items_per_page']) && $_GET['items_per_page'] == 'All' && $this->options['expose']['items_per_page_options_all']) {
        $this->options['items_per_page'] = 0;
      }
    }
    if ($this->offset_exposed()) {
      if (isset($_GET['offset']) && $_GET['offset'] >= 0) {
        $this->options['offset'] = $_GET['offset'];
      }
    }

    $limit = $this->options['items_per_page'];
    $offset = $this->current_page * $this->options['items_per_page'] + $this->options['offset'];
    if (!empty($this->options['total_pages'])) {
      if ($this->current_page >= $this->options['total_pages']) {
        $limit = $this->options['items_per_page'];
        $offset = $this->options['total_pages'] * $this->options['items_per_page'];
      }
    }

    $this->view->query->set_limit($limit);
    $this->view->query->set_offset($offset);
  }

  function render($input) {
    $pager_theme = views_theme_functions('pager', $this->view, $this->display);
    // The 0, 1, 3, 4 index are correct. See theme_pager documentation.
    $tags = array(
      0 => $this->options['tags']['first'],
      1 => $this->options['tags']['previous'],
      3 => $this->options['tags']['next'],
      4 => $this->options['tags']['last'],
    );
    $output = theme($pager_theme, array(
      'tags' => $tags,
      'element' => $this->options['id'],
      'parameters' => $input,
      'quantity' => $this->options['quantity'],
    ));
    return $output;
  }

  /**
   * Set the current page.
   *
   * @param $number
   *   If provided, the page number will be set to this. If NOT provided,
   *   the page number will be set from the global page array.
   */
  function set_current_page($number = NULL) {
    if (isset($number)) {
      $this->current_page = $number;
      return;
    }

    // If the current page number was not specified, extract it from the global
    // page array.
    global $pager_page_array;

    if (empty($pager_page_array)) {
      $pager_page_array = array();
    }

    // Fill in missing values in the global page array, in case the global page
    // array hasn't been initialized before.
    $page = isset($_GET['page']) ? explode(',', $_GET['page']) : array();

    for ($i = 0; $i <= $this->options['id'] || $i < count($pager_page_array); $i++) {
      $pager_page_array[$i] = empty($page[$i]) ? 0 : $page[$i];
    }

    $this->current_page = intval($pager_page_array[$this->options['id']]);

    if ($this->current_page < 0) {
      $this->current_page = 0;
    }
  }

  function get_pager_total() {
    if ($items_per_page = intval($this->get_items_per_page())) {
      return ceil($this->total_items / $items_per_page);
    }
    else {
      return 1;
    }
  }

  /**
   * Update global paging info.
   *
   * This is called after the count query has been run to set the total
   * items available and to update the current page if the requested
   * page is out of range.
   */
  function update_page_info() {
    if (!empty($this->options['total_pages'])) {
      if (($this->options['total_pages'] * $this->options['items_per_page']) < $this->total_items) {
        $this->total_items = $this->options['total_pages'] * $this->options['items_per_page'];
      }
    }

    // Don't set pager settings for items per page = 0.
    $items_per_page = $this->get_items_per_page();
    if (!empty($items_per_page)) {
      // Dump information about what we already know into the globals.
      global $pager_page_array, $pager_total, $pager_total_items, $pager_limits;
      // Set the limit.
      $pager_limits[$this->options['id']] = $this->options['items_per_page'];
      // Set the item count for the pager.
      $pager_total_items[$this->options['id']] = $this->total_items;
      // Calculate and set the count of available pages.
      $pager_total[$this->options['id']] = $this->get_pager_total();

      // See if the requested page was within range:
      if ($this->current_page < 0) {
        $this->current_page = 0;
      }
      else if ($this->current_page >= $pager_total[$this->options['id']]) {
        // Pages are numbered from 0 so if there are 10 pages, the last page is 9.
        $this->current_page = $pager_total[$this->options['id']] - 1;
      }

      // Put this number in to guarantee that we do not generate notices when the pager
      // goes to look for it later.
      $pager_page_array[$this->options['id']] = $this->current_page;
    }
  }

  function uses_exposed() {
    return $this->items_per_page_exposed() || $this->offset_exposed();
  }

  function items_per_page_exposed() {
    return !empty($this->options['expose']['items_per_page']);
  }

  function offset_exposed() {
    return !empty($this->options['expose']['offset']);
  }

  function exposed_form_alter(&$form, &$form_state) {
    if ($this->items_per_page_exposed()) {
      $options = explode(',', $this->options['expose']['items_per_page_options']);
      $sanitized_options = array();
      if (is_array($options)) {
        foreach ($options as $option) {
          $sanitized_options[intval($option)] = intval($option);
        }
        if (!empty($this->options['expose']['items_per_page_options_all']) && !empty($this->options['expose']['items_per_page_options_all_label'])) {
          $sanitized_options['All'] = $this->options['expose']['items_per_page_options_all_label'];
        }
        $form['items_per_page'] = array(
          '#type' => 'select',
          '#title' => $this->options['expose']['items_per_page_label'],
          '#options' => $sanitized_options,
          '#default_value' => $this->get_items_per_page(),
        );
      }
    }

    if ($this->offset_exposed()) {
      $form['offset'] = array(
        '#type' => 'textfield',
        '#size' => 10,
        '#maxlength' => 10,
        '#title' => $this->options['expose']['offset_label'],
        '#default_value' => $this->get_offset(),
      );
    }
  }

  function exposed_form_validate(&$form, &$form_state) {
    if (!empty($form_state['values']['offset']) && trim($form_state['values']['offset'])) {
      if (!is_numeric($form_state['values']['offset']) || $form_state['values']['offset'] < 0) {
        form_set_error('offset', t('Offset must be an number greather or equal than 0.'));
      }
    }
  }
}
