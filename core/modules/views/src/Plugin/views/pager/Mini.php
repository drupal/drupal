<?php

namespace Drupal\views\Plugin\views\pager;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsPager;

/**
 * The plugin to handle mini pager.
 *
 * @ingroup views_pager_plugins
 */
#[ViewsPager(
  id: "mini",
  title: new TranslatableMarkup("Paged output, mini pager"),
  short_title: new TranslatableMarkup("Mini"),
  help: new TranslatableMarkup("A simple pager containing previous and next links."),
  theme: "views_mini_pager",
)]
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
   * {@inheritdoc}
   */
  public function summaryTitle() {
    if (!empty($this->options['offset'])) {
      return $this->formatPlural($this->options['items_per_page'], 'Mini pager, @count item, skip @skip', 'Mini pager, @count items, skip @skip', ['@count' => $this->options['items_per_page'], '@skip' => $this->options['offset']]);
    }
    return $this->formatPlural($this->options['items_per_page'], 'Mini pager, @count item', 'Mini pager, @count items', ['@count' => $this->options['items_per_page']]);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();

    // Only modify the query if we don't want to do a total row count.
    if (!$this->view->get_total_rows) {
      // Don't query for the next page if we have a pager that has a limited
      // amount of pages.
      if ($this->getItemsPerPage() > 0 && (empty($this->options['total_pages']) || ($this->getCurrentPage() < $this->options['total_pages']))) {
        // Increase the items in the query in order to be able to find out
        // whether there is another page.
        $limit = $this->view->query->getLimit();
        $limit += 1;
        $this->view->query->setLimit($limit);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function useCountQuery() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function postExecute(&$result) {
    // Only modify the result if we didn't do a total row count.
    if (!$this->view->get_total_rows) {
      $this->total_items = $this->getCurrentPage() * $this->getItemsPerPage() + count($result);
      // query() checks if we need a next link by setting limit 1 record past
      // this page If we got the extra record we need to remove it before we
      // render the result.
      if ($this->getItemsPerPage() > 0 && count($result) > $this->getItemsPerPage()) {
        array_pop($result);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($input) {
    // The 1, 3 indexes are correct, see template_preprocess_pager().
    $tags = [
      1 => $this->options['tags']['previous'],
      3 => $this->options['tags']['next'],
    ];

    return [
      '#theme' => $this->themeFunctions(),
      '#tags' => $tags,
      '#element' => $this->options['id'],
      '#pagination_heading_level' => parent::getHeadingLevel(),
      '#parameters' => $input,
      '#route_name' => !empty($this->view->live_preview) ? '<current>' : '<none>',
    ];
  }

}
