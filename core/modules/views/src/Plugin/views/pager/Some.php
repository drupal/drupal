<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\pager\Some.
 */

namespace Drupal\views\Plugin\views\pager;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin for views without pagers.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "some",
 *   title = @Translation("Display a specified number of items"),
 *   help = @Translation("Display a limited number items that this view might find."),
 *   display_types = {"basic"}
 * )
 */
class Some extends PagerPluginBase {

  public function summaryTitle() {
    if (!empty($this->options['offset'])) {
      return $this->formatPlural($this->options['items_per_page'], '@count item, skip @skip', '@count items, skip @skip', array('@count' => $this->options['items_per_page'], '@skip' => $this->options['offset']));
    }
      return $this->formatPlural($this->options['items_per_page'], '@count item', '@count items', array('@count' => $this->options['items_per_page']));
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['items_per_page'] = array('default' => 10);
    $options['offset'] = array('default' => 0);

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
      '#type' => 'textfield',
      '#description' => $pager_text['items per page description'],
      '#default_value' => $this->options['items_per_page'],
    );

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

  public function query() {
    $this->view->query->setLimit($this->options['items_per_page']);
    $this->view->query->setOffset($this->options['offset']);
  }

}
