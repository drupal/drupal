<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\pager\SqlBase
 */

namespace Drupal\views\Plugin\views\pager;

use Drupal\Core\Form\FormStateInterface;

/**
 * A common base class for sql based pager.
 */
abstract class SqlBase extends PagerPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['items_per_page'] = array('default' => 10);
    $options['offset'] = array('default' => 0);
    $options['id'] = array('default' => 0);
    $options['total_pages'] = array('default' => '');
    $options['expose'] = array(
      'contains' => array(
        'items_per_page' => array('default' => FALSE),
        'items_per_page_label' => array('default' => $this->t('Items per page')),
        'items_per_page_options' => array('default' => '5, 10, 25, 50'),
        'items_per_page_options_all' => array('default' => FALSE),
        'items_per_page_options_all_label' => array('default' => $this->t('- All -')),

        'offset' => array('default' => FALSE),
        'offset_label' => array('default' => $this->t('Offset')),
      ),
    );
    $options['tags'] = array(
      'contains' => array(
        'previous' => array('default' => $this->t('‹ previous')),
        'next' => array('default' => $this->t('next ›')),
      ),
    );
    return $options;
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $pager_text = $this->displayHandler->getPagerText();
    $form['items_per_page'] = array(
      '#title' => $pager_text['items per page title'],
      '#type' => 'number',
      '#description' => $pager_text['items per page description'],
      '#default_value' => $this->options['items_per_page'],
    );

    $form['offset'] = array(
      '#type' => 'number',
      '#title' => $this->t('Offset (number of items to skip)'),
      '#description' => $this->t('For example, set this to 3 and the first 3 items will not be displayed.'),
      '#default_value' => $this->options['offset'],
    );

    $form['id'] = array(
      '#type' => 'number',
      '#title' => $this->t('Pager ID'),
      '#description' => $this->t("Unless you're experiencing problems with pagers related to this view, you should leave this at 0. If using multiple pagers on one page you may need to set this number to a higher value so as not to conflict within the ?page= array. Large values will add a lot of commas to your URLs, so avoid if possible."),
      '#default_value' => $this->options['id'],
    );

    $form['total_pages'] = array(
      '#type' => 'number',
      '#title' => $this->t('Number of pages'),
      '#description' => $this->t('The total number of pages. Leave empty to show all pages.'),
      '#default_value' => $this->options['total_pages'],
    );

    $form['tags'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Pager link labels'),
      '#input' => TRUE,
    );

    $form['tags']['previous'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Previous page link text'),
      '#default_value' => $this->options['tags']['previous'],
    );

    $form['tags']['next'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Next page link text'),
      '#default_value' => $this->options['tags']['next'],
    );

    $form['expose'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Exposed options'),
      '#input' => TRUE,
      '#description' => $this->t('Exposing this options allows users to define their values in a exposed form when view is displayed'),
    );

    $form['expose']['items_per_page'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Expose items per page'),
      '#description' => $this->t('When checked, users can determine how many items per page show in a view'),
      '#default_value' => $this->options['expose']['items_per_page'],
    );

    $form['expose']['items_per_page_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Items per page label'),
      '#required' => TRUE,
      '#description' => $this->t('Label to use in the exposed items per page form element.'),
      '#default_value' => $this->options['expose']['items_per_page_label'],
      '#states' => array(
        'invisible' => array(
          'input[name="pager_options[expose][items_per_page]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['expose']['items_per_page_options'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Exposed items per page options'),
      '#required' => TRUE,
      '#description' => $this->t('Set between which values the user can choose when determining the items per page. Separated by comma.'),
      '#default_value' => $this->options['expose']['items_per_page_options'],
      '#states' => array(
        'invisible' => array(
          'input[name="pager_options[expose][items_per_page]"]' => array('checked' => FALSE),
        ),
      ),
    );


    $form['expose']['items_per_page_options_all'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Include all items option'),
      '#description' => $this->t('If checked, an extra item will be included to items per page to display all items'),
      '#default_value' => $this->options['expose']['items_per_page_options_all'],
    );

    $form['expose']['items_per_page_options_all_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('All items label'),
      '#description' => $this->t('Which label will be used to display all items'),
      '#default_value' => $this->options['expose']['items_per_page_options_all_label'],
      '#states' => array(
        'invisible' => array(
          'input[name="pager_options[expose][items_per_page_options_all]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['expose']['offset'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Expose Offset'),
      '#description' => $this->t('When checked, users can determine how many items should be skipped at the beginning.'),
      '#default_value' => $this->options['expose']['offset'],
    );

    $form['expose']['offset_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Offset label'),
      '#required' => TRUE,
      '#description' => $this->t('Label to use in the exposed offset form element.'),
      '#default_value' => $this->options['expose']['offset_label'],
      '#states' => array(
        'invisible' => array(
          'input[name="pager_options[expose][offset]"]' => array('checked' => FALSE),
        ),
      ),
    );
  }

  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    // Only accept integer values.
    $error = FALSE;
    $exposed_options = $form_state->getValue(array('pager_options', 'expose', 'items_per_page_options'));
    if (strpos($exposed_options, '.') !== FALSE) {
      $error = TRUE;
    }
    $options = explode(',', $exposed_options);
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
      $form_state->setErrorByName('pager_options][expose][items_per_page_options', $this->t('Insert a list of integer numeric values separated by commas: e.g: 10, 20, 50, 100'));
    }

    // Make sure that the items_per_page is part of the expose settings.
    if (!$form_state->isValueEmpty(array('pager_options', 'expose', 'items_per_page')) && !$form_state->isValueEmpty(array('pager_options', 'items_per_page'))) {
      $items_per_page = $form_state->getValue(array('pager_options', 'items_per_page'));
      if (array_search($items_per_page, $options) === FALSE) {
        $form_state->setErrorByName('pager_options][expose][items_per_page_options', $this->t("The <em>Exposed items per page</em> field's options must include the value from the <em>Items per page</em> field (@items_per_page).",
          array('@items_per_page' => $items_per_page))
        );
      }
    }
  }

  public function query() {
    if ($this->itemsPerPageExposed()) {
      $query = $this->view->getRequest()->query;
      $items_per_page = $query->get('items_per_page');
      if ($items_per_page > 0) {
        $this->options['items_per_page'] = $items_per_page;
      }
      elseif ($items_per_page == 'All' && $this->options['expose']['items_per_page_options_all']) {
        $this->options['items_per_page'] = 0;
      }
    }
    if ($this->isOffsetExposed()) {
      $query = $this->view->getRequest()->query;
      $offset = $query->get('offset');
      if (isset($offset) && $offset >= 0) {
        $this->options['offset'] = $offset;
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

    $this->view->query->setLimit($limit);
    $this->view->query->setOffset($offset);
  }


  /**
   * Set the current page.
   *
   * @param $number
   *   If provided, the page number will be set to this. If NOT provided,
   *   the page number will be set from the global page array.
   */
  public function setCurrentPage($number = NULL) {
    if (isset($number)) {
      $this->current_page = max(0, $number);
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
    $page = $this->view->getRequest()->query->get('page');
    $page = isset($page) ? explode(',', $page) : array();

    for ($i = 0; $i <= $this->options['id'] || $i < count($pager_page_array); $i++) {
      $pager_page_array[$i] = empty($page[$i]) ? 0 : $page[$i];
    }

    // Don't allow the number to be less than zero.
    $this->current_page = max(0, intval($pager_page_array[$this->options['id']]));
  }

  public function getPagerTotal() {
    if ($items_per_page = intval($this->getItemsPerPage())) {
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
  public function updatePageInfo() {
    if (!empty($this->options['total_pages'])) {
      if (($this->options['total_pages'] * $this->options['items_per_page']) < $this->total_items) {
        $this->total_items = $this->options['total_pages'] * $this->options['items_per_page'];
      }
    }

    // Don't set pager settings for items per page = 0.
    $items_per_page = $this->getItemsPerPage();
    if (!empty($items_per_page)) {
      // Dump information about what we already know into the globals.
      global $pager_page_array, $pager_total, $pager_total_items, $pager_limits;
      // Set the limit.
      $pager_limits[$this->options['id']] = $this->options['items_per_page'];
      // Set the item count for the pager.
      $pager_total_items[$this->options['id']] = $this->total_items;
      // Calculate and set the count of available pages.
      $pager_total[$this->options['id']] = $this->getPagerTotal();

      // See if the requested page was within range:
      if ($this->current_page >= $pager_total[$this->options['id']]) {
        // Pages are numbered from 0 so if there are 10 pages, the last page is 9.
        $this->setCurrentPage($pager_total[$this->options['id']] - 1);
      }

      // Put this number in to guarantee that we do not generate notices when the pager
      // goes to look for it later.
      $pager_page_array[$this->options['id']] = $this->current_page;
    }
  }

  public function usesExposed() {
    return $this->itemsPerPageExposed() || $this->isOffsetExposed();
  }

  protected function itemsPerPageExposed() {
    return !empty($this->options['expose']['items_per_page']);
  }

  protected function isOffsetExposed() {
    return !empty($this->options['expose']['offset']);
  }

  public function exposedFormAlter(&$form, FormStateInterface $form_state) {
    if ($this->itemsPerPageExposed()) {
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
          '#default_value' => $this->getItemsPerPage(),
        );
      }
    }

    if ($this->isOffsetExposed()) {
      $form['offset'] = array(
        '#type' => 'textfield',
        '#size' => 10,
        '#maxlength' => 10,
        '#title' => $this->options['expose']['offset_label'],
        '#default_value' => $this->getOffset(),
      );
    }
  }

  public function exposedFormValidate(&$form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('offset') && trim($form_state->getValue('offset'))) {
      if (!is_numeric($form_state->getValue('offset')) || $form_state->getValue('offset') < 0) {
        $form_state->setErrorByName('offset', $this->t('Offset must be an number greather or equal than 0.'));
      }
    }
  }

}
