<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\pager\PagerPluginBase.
 */

namespace Drupal\views\Plugin\views\pager;

use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\ViewExecutable;

/**
 * @defgroup views_pager_plugins Views pager plugins
 * @{
 * The base plugin to handler pagers of a view.
 *
 * The pager takes care about altering the query for its needs, altering some
 * global information of pagers and finally rendering itself.
 */

/**
 * The base plugin to handle pager.
 *
 * Pager plugins take care of everything regarding pagers, including getting
 * and setting the total number of items to render the pager and setting the
 * global pager arrays.
 *
 * To define a pager type, extend this base class. The ViewsPluginManager (used
 * to create views plugins objects) adds annotated discovery for pager plugins.
 * Your pager plugin must have an annotation that includes the plugin's metadata,
 * for example:
 * @code
 * @ Plugin(
 *   id = "demo_pager",
 *   title = @ Translation("Display a demonstration pager"),
 *   help = @ Translation("Demonstrate pagination of views items."),
 *   theme = "views_demo_pager"
 * )
 * @endcode
 * Remove spaces after @ in your actual plugin - these are put into this sample
 * code so that it is not recognized as annotation.
 *
 * The plugin annotation contains these components:
 * - id: The unique identifier of your pager plugin.
 * - title: The "full" title for your pager type; used in the views UI.
 * - short_title: (optional) The "short" title for your pager type;
 *   used in the views UI when specified.
 * - help: (optional) A short help string; this is displayed in the views UI.
 * - theme: The theme function used to render the pager's output.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
abstract class PagerPluginBase extends PluginBase {

  var $current_page = NULL;

  var $total_items = 0;

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = TRUE;

  /**
   * Get how many items per page this pager will display.
   *
   * All but the leanest pagers should probably return a value here, so
   * most pagers will not need to override this method.
   */
  public function getItemsPerPage() {
    return isset($this->options['items_per_page']) ? $this->options['items_per_page'] : 0;
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
    return isset($this->options['offset']) ? $this->options['offset'] : 0;
  }

  /**
   * Set the page offset, or how many items to skip.
   */
  public function setOffset($offset) {
    $this->options['offset'] = $offset;
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
   * Get the pager id, if it exists
   */
  public function getPagerId() {
    return isset($this->options['id']) ? $this->options['id'] : 0;
  }

  /**
   * Provide the default form form for validating options
   */
  public function validateOptionsForm(&$form, &$form_state) { }

  /**
   * Provide the default form form for submitting options
   */
  public function submitOptionsForm(&$form, &$form_state) { }

  /**
   * Return a string to display as the clickable title for the
   * pager plugin.
   */
  public function summaryTitle() {
    return t('Unknown');
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
   * Execute the count query, which will be done just prior to the query
   * itself being executed.
   */
  public function executeCountQuery(&$count_query) {
    $this->total_items = $count_query->execute()->fetchField();
    if (!empty($this->options['offset'])) {
      $this->total_items -= $this->options['offset'];
    }

    return $this->total_items;
  }

  /**
   * If there are pagers that need global values set, this method can
   * be used to set them. It will be called after the query is run.
   */
  public function updatePageInfo() {

  }

  /**
   * Modify the query for paging
   *
   * This is called during the build phase and can directly modify the query.
   */
  public function query() { }

  /**
   * Perform any needed actions just prior to the query executing.
   */
  public function preExecute(&$query) { }

  /**
   * Perform any needed actions just after the query executing.
   */
  public function postExecute(&$result) { }

  /**
   * Perform any needed actions just before rendering.
   */
  public function preRender(&$result) { }

  /**
   * Return the renderable array of the pager.
   *
   * Called during the view render process.
   *
   * @param $input
   *   Any extra GET parameters that should be retained, such as exposed
   *   input.
   */
  public function render($input) { }

  /**
   * Determine if there are more records available.
   *
   * This is primarily used to control the display of a more link.
   */
  public function hasMoreRecords() {
    return $this->getItemsPerPage()
      && $this->total_items > (intval($this->current_page) + 1) * $this->getItemsPerPage();
  }

  public function exposedFormAlter(&$form, &$form_state) { }

  public function exposedFormValidate(&$form, &$form_state) { }

  public function exposedFormSubmit(&$form, &$form_state, &$exclude) { }

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
