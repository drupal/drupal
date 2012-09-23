<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\pager\None.
 */

namespace Drupal\views\Plugin\views\pager;

use Drupal\views\ViewExecutable;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Plugin for views without pagers.
 *
 * @ingroup views_pager_plugins
 *
 * @Plugin(
 *   id = "none",
 *   title = @Translation("Display all items"),
 *   help = @Translation("Display all items that this view might find."),
 *   type = "basic"
 * )
 */
class None extends PagerPluginBase {

  public function init(ViewExecutable $view, &$display, $options = array()) {
    parent::init($view, $display, $options);

    // If the pager is set to none, then it should show all items.
    $this->set_items_per_page(0);
  }

  public function summaryTitle() {
    if (!empty($this->options['offset'])) {
      return t('All items, skip @skip', array('@skip' => $this->options['offset']));
    }
    return t('All items');
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['offset'] = array('default' => 0);

    return $options;
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
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

  function get_items_per_page() {
    return 0;
  }

  function execute_count_query(&$count_query) {
    // If we are displaying all items, never count. But we can update the count in post_execute.
  }

  public function postExecute(&$result) {
    $this->total_items = count($result);
  }

  public function query() {
    // The only query modifications we might do are offsets.
    if (!empty($this->options['offset'])) {
      $this->view->query->set_offset($this->options['offset']);
    }
  }

}
