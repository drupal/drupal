<?php

/**
 * @file
 * Contains \Drupal\search\Plugin\SearchInterface.
 */

namespace Drupal\search\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines a common interface for all SearchPlugin objects.
 */
interface SearchInterface extends PluginInspectionInterface {

  /**
   * Sets the keywords, parameters, and attributes to be used by execute().
   *
   * @param string $keywords
   *   The keywords to use in a search.
   * @param array $parameters
   *   Array of parameters as am associative array. This is expected to
   *   be the query string from the current request.
   * @param array $attributes
   *   Array of attributes, usually from the current request object.
   *
   * @return \Drupal\search\Plugin\SearchInterface
   *   A search plugin object for chaining.
   */
  public function setSearch($keywords, array $parameters, array $attributes);

  /**
   * Returns the currently set keywords of the plugin instance.
   *
   * @return string
   *   The keywords.
   */
  public function getKeywords();

  /**
   * Returns the current parameters set using setSearch().
   *
   * @return array
   *   The parameters.
   */
  public function getParameters();

  /**
   * Returns the currently set attributes (from the request).
   *
   * @return array
   *   The attributes.
   */
  public function getAttributes();

  /**
   * Verifies if the values set via setSearch() are valid and sufficient.
   *
   * @return bool
   *   TRUE if the search settings are valid and sufficient to execute a search,
   *   and FALSE if not.
   */
  public function isSearchExecutable();

  /**
   * Executes the search.
   *
   * @return array
   *   A structured list of search results.
   */
  public function execute();

  /**
   * Executes the search and builds render arrays for the result items.
   *
   * @return array
   *   An array of render arrays of search result items (generally each item
   *   has '#theme' set to 'search_result'), or an empty array if there are no
   *   results.
   */
  public function buildResults();

  /**
   * Alters the search form when being built for a given plugin.
   *
   * The core search module only invokes this method on active module plugins
   * when building a form for them in search_form(). A plugin implementing
   * this needs to add validate and submit callbacks to the form if it needs
   * to act after form submission.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param array $form_state
   *   A keyed array containing the current state of the form. The arguments
   *   that drupal_get_form() was originally called with are available in the
   *   array $form_state['build_info']['args'].
   */
  public function searchFormAlter(array &$form, array &$form_state);

}
