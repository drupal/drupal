<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Filter\FilterInterface.
 */

namespace Drupal\filter\Plugin;

/**
 * Defines the interface for text processing filter plugins.
 *
 * User submitted content is passed through a group of filters before it is
 * output in HTML, in order to remove insecure or unwanted parts, correct or
 * enhance the formatting, transform special keywords, etc. A group of filters
 * is referred to as a "text format". Administrators can create as many text
 * formats as needed. Individual filters can be enabled and configured
 * differently for each text format.
 *
 * @see \Drupal\filter\Plugin\Core\Entity\FilterFormat
 *
 * Filtering is a two-step process. First, the content is 'prepared' by calling
 * the FilterInterface::prepare() method for every filter. The purpose is to
 * escape HTML-like structures. For example, imagine a filter which allows the
 * user to paste entire chunks of programming code without requiring manual
 * escaping of special HTML characters like < or &. If the programming code were
 * left untouched, then other filters could think it was HTML and change it. For
 * many filters, the prepare step is not necessary.
 *
 * The second step is the actual processing step. The result from passing the
 * text through all the filters' prepare steps gets passed to all the filters
 * again, this time to the FilterInterface::process() method. The process method
 * should then actually change the content: transform URLs into hyperlinks,
 * convert smileys into images, etc.
 *
 * For performance reasons, content is only filtered once; the result is stored
 * in the cache table and retrieved from the cache the next time the same piece
 * of content is displayed. If a filter's output is dynamic, it can override
 * the cache mechanism, but obviously this should be used with caution: having
 * one filter that does not support caching in a collection of filters disables
 * caching for the entire collection, not just for one filter.
 *
 * Beware of the filter cache when developing your module: it is advised to set
 * your filter to 'cache' to FALSE while developing, but be sure to remove that
 * setting if it's not needed, when you are no longer in development mode.
 *
 * @see check_markup()
 *
 * Filters are discovered through annotations, which may contain the following
 * definition properties:
 * - title: (required) An administrative summary of what the filter does.
 *   - type: (required) A classification of the filter's purpose. This is one
 *     of the following:
 *     - FILTER_TYPE_HTML_RESTRICTOR: HTML tag and attribute restricting
 *       filters.
 *     - FILTER_TYPE_MARKUP_LANGUAGE: Non-HTML markup language filters that
 *       generate HTML.
 *     - FILTER_TYPE_TRANSFORM_IRREVERSIBLE: Irreversible transformation
 *       filters.
 *     - FILTER_TYPE_TRANSFORM_REVERSIBLE: Reversible transformation filters.
 * - description: Additional administrative information about the filter's
 *   behavior, if needed for clarification.
 * - status: The default status for new instances of the filter. Defaults to
 *   FALSE.
 * - weight: A default weight for new instances of the filter. Defaults to 0.
 * - cache: Whether the filtered text can be cached. Defaults to TRUE.
 *   Note that setting this to FALSE disables caching for an entire text format,
 *   which can have a negative impact on the site's overall performance.
 * - settings: An associative array containing default settings for new
 *   instances of the filter.
 *
 * Most implementations want to extend the generic basic implementation for
 * filter plugins.
 *
 * @see \Drupal\filter\Plugin\Filter\FilterBase
 */
interface FilterInterface {

  /**
   * Sets the configuration for this filter plugin instance.
   *
   * @param array $configuration
   *   An associative array containing:
   *   - status: A Boolean indicating whether the plugin is enabled.
   *   - weight: The weight of the filter compared to others.
   *   - settings: An associative array containing configured settings for this
   *     filter implementation.
   */
  public function setPluginConfiguration(array $configuration);

  /**
   * Exports the complete configuration of this filter plugin instance.
   *
   * @return array
   */
  public function export();

  /**
   * Returns the processing type of this filter plugin.
   *
   * @return int
   *   One of:
   *   - FILTER_TYPE_MARKUP_LANGUAGE
   *   - FILTER_TYPE_HTML_RESTRICTOR
   *   - FILTER_TYPE_TRANSFORM_REVERSIBLE
   *   - FILTER_TYPE_TRANSFORM_IRREVERSIBLE
   */
  public function getType();

  /**
   * Returns the administrative label for this filter plugin.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Returns the administrative description for this filter plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Generates a filter's settings form.
   *
   * @param array $form
   *   A minimally prepopulated form array.
   * @param array $form_state
   *   The state of the (entire) configuration form.
   *
   * @return array
   *   The $form array with additional form elements for the settings of this
   *   filter. The submitted form values should match $this->settings.
   */
  public function settingsForm(array $form, array &$form_state);

  /**
   * Prepares the text for processing.
   *
   * Filters should not use the prepare method for anything other than escaping,
   * because that would short-circuit the control the user has over the order in
   * which filters are applied.
   *
   * @param string $text
   *   The text string to be filtered.
   * @param string $langcode
   *   The language code of the text to be filtered.
   * @param bool $cache
   *   A Boolean indicating whether the filtered text is going to be cached in
   *   {cache_filter}.
   * @param string $cache_id
   *   The ID of the filtered text in {cache_filter}, if $cache is TRUE.
   *
   * @return string
   *   The prepared, escaped text.
   */
  public function prepare($text, $langcode, $cache, $cache_id);

  /**
   * Performs the filter processing.
   *
   * @param string $text
   *   The text string to be filtered.
   * @param string $langcode
   *   The language code of the text to be filtered.
   * @param bool $cache
   *   A Boolean indicating whether the filtered text is going to be cached in
   *   {cache_filter}.
   * @param string $cache_id
   *   The ID of the filtered text in {cache_filter}, if $cache is TRUE.
   *
   * @return string
   *   The filtered text.
   */
  public function process($text, $langcode, $cache, $cache_id);

  /**
   * Generates a filter's tip.
   *
   * A filter's tips should be informative and to the point. Short tips are
   * preferably one-liners.
   *
   * @param bool $long
   *   Whether this callback should return a short tip to display in a form
   *   (FALSE), or whether a more elaborate filter tips should be returned for
   *   theme_filter_tips() (TRUE).
   *
   * @return string|null
   *   Translated text to display as a tip, or NULL if this filter has no tip.
   *
   * @todo Split into getSummaryItem() and buildGuidelines().
   */
  public function tips($long = FALSE);

}
