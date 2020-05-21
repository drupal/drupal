<?php

/**
 * @file
 * Hooks provided by the Filter module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Define content filters.
 *
 * User submitted content is passed through a group of filters before it is
 * output in HTML, in order to remove insecure or unwanted parts, correct or
 * enhance the formatting, transform special keywords, etc. A group of filters
 * is referred to as a "text format". Administrators can create as many text
 * formats as needed. Individual filters can be enabled and configured
 * differently for each text format.
 *
 * This hook is invoked by filter_get_filters() and allows modules to register
 * input filters they provide.
 *
 * Filtering is a two-step process. First, the content is 'prepared' by calling
 * the 'prepare callback' function for every filter. The purpose of the 'prepare
 * callback' is to escape HTML-like structures. For example, imagine a filter
 * which allows the user to paste entire chunks of programming code without
 * requiring manual escaping of special HTML characters like < or &. If the
 * programming code were left untouched, then other filters could think it was
 * HTML and change it. For many filters, the prepare step is not necessary.
 *
 * The second step is the actual processing step. The result from passing the
 * text through all the filters' prepare steps gets passed to all the filters
 * again, this time with the 'process callback' function. The process callbacks
 * should then actually change the content: transform URLs into hyperlinks,
 * convert smileys into images, etc.
 *
 * For performance reasons content is only filtered once; the result is stored
 * in the cache table and retrieved from the cache the next time the same piece
 * of content is displayed. If a filter's output is dynamic, it can override the
 * cache mechanism, but obviously this should be used with caution: having one
 * filter that does not support caching in a particular text format disables
 * caching for the entire format, not just for one filter.
 *
 * Beware of the filter cache when developing your module: it is advised to set
 * your filter to 'cache' => FALSE while developing, but be sure to remove that
 * setting if it's not needed, when you are no longer in development mode.
 *
 * @return
 *   An associative array of filters, whose keys are internal filter names,
 *   which should be unique and therefore prefixed with the name of the module.
 *   Each value is an associative array describing the filter, with the
 *   following elements (all are optional except as noted):
 *   - title: (required) An administrative summary of what the filter does.
 *   - description: Additional administrative information about the filter's
 *     behavior, if needed for clarification.
 *   - settings callback: The name of a function that returns configuration form
 *     elements for the filter. See callback_filter_settings() for details.
 *   - default settings: An associative array containing default settings for
 *     the filter, to be applied when the filter has not been configured yet.
 *   - prepare callback: The name of a function that escapes the content before
 *     the actual filtering happens. See callback_filter_prepare() for
 *     details.
 *   - process callback: (required) The name the function that performs the
 *     actual filtering. See callback_filter_process() for details.
 *   - cache (default TRUE): Specifies whether the filtered text can be cached.
 *     Note that setting this to FALSE makes the entire text format not
 *     cacheable, which may have an impact on the site's overall performance.
 *     See filter_format_allowcache() for details.
 *   - tips callback: The name of a function that returns end-user-facing filter
 *     usage guidelines for the filter. See callback_filter_tips() for
 *     details.
 *   - weight: A default weight for the filter in new text formats.
 *
 * @see filter_example.module
 * @see hook_filter_info_alter()
 */
function hook_filter_info() {
  $filters['filter_html'] = array(
    'title' => t('Limit allowed HTML tags'),
    'description' => t('Allows you to restrict the HTML tags the user can use. It will also remove harmful content such as JavaScript events, JavaScript URLs and CSS styles from those tags that are not removed.'),
    'process callback' => '_filter_html',
    'settings callback' => '_filter_html_settings',
    'default settings' => array(
      'allowed_html' => '<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd>',
      'filter_html_help' => 1,
      'filter_html_nofollow' => 0,
    ),
    'tips callback' => '_filter_html_tips',
  );
  $filters['filter_autop'] = array(
    'title' => t('Convert line breaks'),
    'description' => t('Converts line breaks into HTML (i.e. &lt;br&gt; and &lt;p&gt;) tags.'),
    'process callback' => '_filter_autop',
    'tips callback' => '_filter_autop_tips',
  );
  return $filters;
}

/**
 * Perform alterations on filter definitions.
 *
 * @param $info
 *   Array of information on filters exposed by hook_filter_info()
 *   implementations.
 */
function hook_filter_info_alter(&$info) {
  // Replace the PHP evaluator process callback with an improved
  // PHP evaluator provided by a module.
  $info['php_code']['process callback'] = 'my_module_php_evaluator';

  // Alter the default settings of the URL filter provided by core.
  $info['filter_url']['default settings'] = array(
    'filter_url_length' => 100,
  );
}

/**
 * @} End of "addtogroup hooks".
 */

/**
 * Provide a settings form for filter settings.
 *
 * Callback for hook_filter_info().
 *
 * This callback function is used to provide a settings form for filter
 * settings, for filters that need settings on a per-text-format basis. This
 * function should return the form elements for the settings; the filter
 * module will take care of saving the settings in the database.
 *
 * If the filter's behavior depends on an extensive list and/or external data
 * (e.g. a list of smileys, a list of glossary terms), then the filter module
 * can choose to provide a separate, global configuration page rather than
 * per-text-format settings. In that case, the settings callback function
 * should provide a link to the separate settings page.
 *
 * @param $form
 *   The prepopulated form array of the filter administration form.
 * @param $form_state
 *   The state of the (entire) configuration form.
 * @param $filter
 *   The filter object containing the current settings for the given format,
 *   in $filter->settings.
 * @param $format
 *   The format object being configured.
 * @param $defaults
 *   The default settings for the filter, as defined in 'default settings' in
 *   hook_filter_info(). These should be combined with $filter->settings to
 *   define the form element defaults.
 * @param $filters
 *   The complete list of filter objects that are enabled for the given format.
 *
 * @return
 *   An array of form elements defining settings for the filter. Array keys
 *   should match the array keys in $filter->settings and $defaults.
 *
 * @ingroup callbacks
 */
function callback_filter_settings($form, &$form_state, $filter, $format, $defaults, $filters) {
  $filter->settings += $defaults;

  $elements = array();
  $elements['nofollow'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add rel="nofollow" to all links'),
    '#default_value' => $filter->settings['nofollow'],
  );
  return $elements;
}

/**
 * Provide prepared text with special characters escaped.
 *
 * Callback for hook_filter_info().
 *
 * See hook_filter_info() for a description of the filtering process. Filters
 * should not use the 'prepare callback' step for anything other than escaping,
 * because that would short-circuit the control the user has over the order in
 * which filters are applied.
 *
 * @param $text
 *   The text string to be filtered.
 * @param $filter
 *   The filter object containing settings for the given format.
 * @param $format
 *   The text format object assigned to the text to be filtered.
 * @param $langcode
 *   The language code of the text to be filtered.
 * @param $cache
 *   A Boolean indicating whether the filtered text is going to be cached in
 *   {cache_filter}.
 * @param $cache_id
 *   The ID of the filtered text in {cache_filter}, if $cache is TRUE.
 *
 * @return
 *   The prepared, escaped text.
 *
 * @ingroup callbacks
 */
function callback_filter_prepare($text, $filter, $format, $langcode, $cache, $cache_id) {
  // Escape <code> and </code> tags.
  $text = preg_replace('|<code>(.+?)</code>|s', "[codefilter_code]$1[/codefilter_code]", $text);
  return $text;
}

/**
 * Provide text filtered to conform to the supplied format.
 *
 * Callback for hook_filter_info().
 *
 * See hook_filter_info() for a description of the filtering process. This step
 * is where the filter actually transforms the text.
 *
 * @param $text
 *   The text string to be filtered.
 * @param $filter
 *   The filter object containing settings for the given format.
 * @param $format
 *   The text format object assigned to the text to be filtered.
 * @param $langcode
 *   The language code of the text to be filtered.
 * @param $cache
 *   A Boolean indicating whether the filtered text is going to be cached in
 *   {cache_filter}.
 * @param $cache_id
 *   The ID of the filtered text in {cache_filter}, if $cache is TRUE.
 *
 * @return
 *   The filtered text.
 *
 * @ingroup callbacks
 */
function callback_filter_process($text, $filter, $format, $langcode, $cache, $cache_id) {
  $text = preg_replace('|\[codefilter_code\](.+?)\[/codefilter_code\]|s', "<pre>$1</pre>", $text);

  return $text;
}

/**
 * Return help text for a filter.
 *
 * Callback for hook_filter_info().
 *
 * A filter's tips should be informative and to the point. Short tips are
 * preferably one-liners.
 *
 * @param $filter
 *   An object representing the filter.
 * @param $format
 *   An object representing the text format the filter is contained in.
 * @param $long
 *   Whether this callback should return a short tip to display in a form
 *   (FALSE), or whether a more elaborate filter tips should be returned for
 *   theme_filter_tips() (TRUE).
 *
 * @return
 *   Translated text to display as a tip.
 *
 * @ingroup callbacks
 */
function callback_filter_tips($filter, $format, $long) {
 if ($long) {
    return t('Lines and paragraphs are automatically recognized. The &lt;br /&gt; line break, &lt;p&gt; paragraph and &lt;/p&gt; close paragraph tags are inserted automatically. If paragraphs are not recognized simply add a couple blank lines.');
  }
  else {
    return t('Lines and paragraphs break automatically.');
  }
}

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform actions when a new text format has been created.
 *
 * @param $format
 *   The format object of the format being updated.
 *
 * @see hook_filter_format_update()
 * @see hook_filter_format_disable()
 */
function hook_filter_format_insert($format) {
  mymodule_cache_rebuild();
}

/**
 * Perform actions when a text format has been updated.
 *
 * This hook allows modules to act when a text format has been updated in any
 * way. For example, when filters have been reconfigured, disabled, or
 * re-arranged in the text format.
 *
 * @param $format
 *   The format object of the format being updated.
 *
 * @see hook_filter_format_insert()
 * @see hook_filter_format_disable()
 */
function hook_filter_format_update($format) {
  mymodule_cache_rebuild();
}

/**
 * Perform actions when a text format has been disabled.
 *
 * @param $format
 *   The format object of the format being disabled.
 *
 * @see hook_filter_format_insert()
 * @see hook_filter_format_update()
 */
function hook_filter_format_disable($format) {
  mymodule_cache_rebuild();
}

/**
 * @} End of "addtogroup hooks".
 */
