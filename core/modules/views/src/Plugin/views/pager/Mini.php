<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\pager\Mini.
 */

namespace Drupal\views\Plugin\views\pager;

/**
 * The plugin to handle mini pager.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "mini",
 *   title = @Translation("Paged output, mini pager"),
 *   short_title = @Translation("Mini"),
 *   help = @Translation("A simple pager containing previous and next links."),
 *   theme = "views_mini_pager"
 * )
 */
class Mini extends SqlBase {

  /**
   * Overrides \Drupal\views\Plugin\views\pager\PagerPlugin::defineOptions().
   *
   * Provides sane defaults for the next/previous links.
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['tags']['contains']['previous']['default'] = '‹‹';
    $options['tags']['contains']['next']['default'] = '››';

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\pager\PagerPluginBase::summaryTitle().
   */
  public function summaryTitle() {
    if (!empty($this->options['offset'])) {
      return $this->formatPlural($this->options['items_per_page'], 'Mini pager, @count item, skip @skip', 'Mini pager, @count items, skip @skip', array('@count' => $this->options['items_per_page'], '@skip' => $this->options['offset']));
    }
      return $this->formatPlural($this->options['items_per_page'], 'Mini pager, @count item', 'Mini pager, @count items', array('@count' => $this->options['items_per_page']));
  }

  /**
   * Overrides \Drupal\views\Plugin\views\pager\SqlBase::query().
   */
  public function query() {
    parent::query();

    // Don't query for the next page if we have a pager that has a limited
    // amount of pages.
    if ($this->getItemsPerPage() > 0 && (empty($this->options['total_pages']) || ($this->getCurrentPage() < $this->options['total_pages']))) {
      // Increase the items in the query in order to be able to find out whether
      // there is another page.
      $limit = $this->view->query->getLimit();
      $limit += 1;
      $this->view->query->setLimit($limit);
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\pager\PagerPluginBase::useCountQuery().
   */
  public function useCountQuery() {
    return FALSE;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\pager\PagerPluginBase::postExecute().
   */
  public function postExecute(&$result) {
    // In query() one more item might have been retrieved than necessary. If so,
    // the next link needs to be displayed and the item removed.
    if ($this->getItemsPerPage() > 0 && count($result) > $this->getItemsPerPage()) {
      array_pop($result);
      // Make sure the pager shows the next link by setting the total items to
      // the biggest possible number but prevent failing calculations like
      // ceil(PHP_INT_MAX) we take PHP_INT_MAX / 2.
      $total = PHP_INT_MAX / 2;
    }
    else {
      $total = $this->getCurrentPage() * $this->getItemsPerPage() + count($result);
    }
    $this->total_items = $total;
  }

  /**
   * {@inheritdoc}
   */
  public function render($input) {
    // The 1, 3 indexes are correct, see template_preprocess_pager().
    $tags = array(
      1 => $this->options['tags']['previous'],
      3 => $this->options['tags']['next'],
    );
    return array(
      '#theme' => $this->themeFunctions(),
      '#tags' => $tags,
      '#element' => $this->options['id'],
      '#parameters' => $input,
      '#route_name' => !empty($this->view->live_preview) ? '<current>' : '<none>',
    );
  }

}
