<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\pager\None.
 */

namespace Drupal\views\Plugin\views\pager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * Plugin for views without pagers.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "none",
 *   title = @Translation("Display all items"),
 *   help = @Translation("Display all items that this view might find."),
 *   display_types = {"basic"}
 * )
 */
class None extends PagerPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // If the pager is set to none, then it should show all items.
    $this->setItemsPerPage(0);
  }

  public function summaryTitle() {
    if (!empty($this->options['offset'])) {
      return $this->t('All items, skip @skip', array('@skip' => $this->options['offset']));
    }
    return $this->t('All items');
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['offset'] = array('default' => 0);

    return $options;
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['offset'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Offset (number of items to skip)'),
      '#description' => $this->t('For example, set this to 3 and the first 3 items will not be displayed.'),
      '#default_value' => $this->options['offset'],
    );
  }

  public function usePager() {
    return FALSE;
  }

  public function useCountQuery() {
    return FALSE;
  }

  public function getItemsPerPage() {
    return 0;
  }

  public function executeCountQuery(&$count_query) {
    // If we are displaying all items, never count. But we can update the count in post_execute.
  }

  public function postExecute(&$result) {
    $this->total_items = count($result);
  }

  public function query() {
    // The only query modifications we might do are offsets.
    if (!empty($this->options['offset'])) {
      $this->view->query->setOffset($this->options['offset']);
    }
  }

}
