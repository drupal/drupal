<?php

namespace Drupal\views\Plugin\views\pager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\PluginBase;

/**
 * @defgroup views_pager_plugins Views pager plugins
 * @{
 * Plugins to handle paging in views.
 *
 * Pager plugins take care of everything regarding pagers, including figuring
 * out the total number of items to render, setting up the query for paging,
 * and setting up the pager.
 *
 * Pager plugins extend \Drupal\views\Plugin\views\pager\PagerPluginBase. They
 * must be attributed with \Drupal\views\Annotation\ViewsPager attribute,
 * and they must be in namespace directory Plugin\views\pager.
 *
 * @ingroup views_plugins
 * @see plugin_api
 */

/**
 * Base class for views pager plugins.
 */
abstract class PagerPluginBase extends PluginBase {

  /**
   * The current page.
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  public $current_page = NULL;

  /**
   * The total number of lines.
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  public $total_items = 0;

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * Options available for setting pagination headers.
   */
  protected array $headingOptions = [
    'h1' => 'H1',
    'h2' => 'H2',
    'h3' => 'H3',
    'h4' => 'H4',
    'h5' => 'H5',
    'h6' => 'H6',
  ];

  /**
   * Get how many items per page this pager will display.
   *
   * All but the leanest pagers should probably return a value here, so
   * most pagers will not need to override this method.
   */
  public function getItemsPerPage() {
    return $this->options['items_per_page'] ?? 0;
  }

  /**
   * Set how many items per page this pager will display.
   *
   * This is mostly used for things that will override the value.
   */
  public function setItemsPerPage($items) {
    $this->options['items_per_page'] = $items;
  }

  /**
   * Get the page offset, or how many items to skip.
   *
   * Even pagers that don't actually page can skip items at the beginning,
   * so few pagers will need to override this method.
   */
  public function getOffset() {
    return $this->options['offset'] ?? 0;
  }

  /**
   * Set the page offset, or how many items to skip.
   */
  public function setOffset($offset) {
    $this->options['offset'] = $offset;
  }

  /**
   * Get the pager heading tag.
   *
   * @return string
   *   Heading level for the pager.
   */
  public function getHeadingLevel(): string {
    return $this->options['pagination_heading_level'] ?? 'h4';
  }

  /**
   * Set the pager heading.
   */
  public function setHeadingLevel($headingLevel): void {
    $this->options['pagination_heading_level'] = $headingLevel;
  }

  /**
   * Get the current page.
   *
   * If NULL, we do not know what the current page is.
   */
  public function getCurrentPage() {
    return $this->current_page;
  }

  /**
   * Set the current page.
   *
   * @param $number
   *   If provided, the page number will be set to this. If NOT provided,
   *   the page number will be set from the global page array.
   */
  public function setCurrentPage($number = NULL) {
    if (!is_numeric($number) || $number < 0) {
      $number = 0;
    }
    $this->current_page = $number;
  }

  /**
   * Get the total number of items.
   *
   * If NULL, we do not yet know what the total number of items are.
   */
  public function getTotalItems() {
    return $this->total_items;
  }

  /**
   * Get the pager id, if it exists.
   */
  public function getPagerId() {
    return $this->options['id'] ?? 0;
  }

  /**
   * Provide the default form for validating options.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {}

  /**
   * Provide the default form for submitting options.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {}

  /**
   * Returns a string to display as the clickable title for the pager plugin.
   */
  public function summaryTitle() {
    return $this->t('Unknown');
  }

  /**
   * Determine if this pager actually uses a pager.
   *
   * Only a couple of very specific pagers will set this to false.
   */
  public function usePager() {
    return TRUE;
  }

  /**
   * Determine if a pager needs a count query.
   *
   * If a pager needs a count query, a simple query
   */
  public function useCountQuery() {
    return TRUE;
  }

  /**
   * Executes the count query.
   *
   * This will be done just prior to the query itself being executed.
   */
  public function executeCountQuery(&$count_query) {
    $this->total_items = $count_query->execute()->fetchField();
    if (!empty($this->options['offset'])) {
      $this->total_items -= $this->options['offset'];
    }
    // Prevent from being negative.
    $this->total_items = max(0, $this->total_items);

    return $this->total_items;
  }

  /**
   * Updates the pager information.
   *
   * If there are pagers that need global values set, this method can
   * be used to set them. It will be called after the query is run.
   */
  public function updatePageInfo() {

  }

  /**
   * Modify the query for paging.
   *
   * This is called during the build phase and can directly modify the query.
   */
  public function query() {}

  /**
   * Perform any needed actions just prior to the query executing.
   */
  public function preExecute(&$query) {}

  /**
   * Perform any needed actions just after the query executing.
   */
  public function postExecute(&$result) {}

  /**
   * Perform any needed actions just before rendering.
   */
  public function preRender(&$result) {}

  /**
   * Return the renderable array of the pager.
   *
   * Called during the view render process.
   *
   * @param $input
   *   Any extra GET parameters that should be retained, such as exposed
   *   input.
   */
  public function render($input) {}

  /**
   * Determine if there are more records available.
   *
   * This is primarily used to control the display of a more link.
   */
  public function hasMoreRecords() {
    return $this->getItemsPerPage()
      && $this->total_items > (intval($this->current_page) + 1) * $this->getItemsPerPage();
  }

  public function exposedFormAlter(&$form, FormStateInterface $form_state) {}

  public function exposedFormValidate(&$form, FormStateInterface $form_state) {}

  public function exposedFormSubmit(&$form, FormStateInterface $form_state, &$exclude) {}

  public function usesExposed() {
    return FALSE;
  }

  protected function itemsPerPageExposed() {
    return FALSE;
  }

  protected function isOffsetExposed() {
    return FALSE;
  }

}

/**
 * @}
 */
