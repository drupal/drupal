<?php
// $Id: filter.api.php,v 1.20 2010/06/26 01:55:29 dries Exp $

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
 * Content in Drupal is passed through a group of filters before it is output.
 * This lets a module modify content to the site administrator's liking.
 *
 * This hook allows modules to declare input filters they provide. A module can
 * contain as many filters as it wants.
 *
 * The overall, logical flow is as follows:
 * - hook_filter_info() is invoked to retrieve one or more filter definitions.
 * - The site administrator enables and configures the filter, where the
 *   following properties may be used:
 *   - 'title': The filter's title.
 *   - 'description': The filter's short-description.
 *   Additionally, if a filter is configurable:
 *   - 'settings callback': A form builder function name providing a settings
 *     form for the filter.
 *   - 'default settings': An array containing default settings for the filter.
 * - When a form containing a text format-enabled text widget/textarea is
 *   rendered, the following property are checked:
 *   - 'tips callback': A function name providing filter guidelines to be
 *      displayed in the text format widget.
 * - When a content using a text format is rendered, the following properties
 *   may be used:
 *   - 'prepare callback': A name of a function that escapes the to be filtered
 *     content before the actual filtering happens.
 *   - 'process callback': The name the function that performs the actual
 *     filtering.
 *
 * Filtering is a two-step process. First, the content is 'prepared' by calling
 * the 'prepare callback' function for every filter. The purpose of the 'prepare
 * callback' is to escape HTML-like structures. For example, imagine a filter
 * which allows the user to paste entire chunks of programming code without
 * requiring manual escaping of special HTML characters like @< or @&. If the
 * programming code were left untouched, then other filters could think it was
 * HTML and change it. For most filters however, the prepare-step is not
 * necessary, and they can just return the input without changes.
 *
 * Filters should not use the 'prepare callback' step for anything other than
 * escaping, because that would short-circuit the control the user has over the
 * order in which filters are applied.
 *
 * The second step is the actual processing step. The result from the prepare
 * step gets passed to all the filters again, this time with the 'process
 * callback' function. It's here where filters should perform actual changing of
 * the content: transforming URLs into hyperlinks, converting smileys into
 * images, etc.
 *
 * An important aspect of the filtering system is 'text formats'. Every text
 * format is an entire filter setup: which filters to enable, in what order
 * and with what settings.
 *
 * Filters that require settings should provide the form controls to configure
 * the settings in a form builder function, specified in 'settings callback'.
 * The filter system stores the settings in the database per text format.
 *
 * If the filter's behavior depends on an extensive list and/or external data
 * (e.g. a list of smileys, a list of glossary terms) then filters are allowed
 * to provide a separate, global configuration page rather than provide settings
 * per format. In that case, there should be a link from the format-specific
 * settings to the separate settings page.
 *
 * The $filter object with the current settings is passed to the 'settings
 * callback' function. If 'default settings' were defined in hook_filter_info(),
 * those are passed as second argument to the 'settings callback'. Each filter
 * should apply either the default settings or the configured settings contained
 * in $filter->settings.
 *
 * 'settings callback' is invoked with the following arguments (most filter
 * implementations will only need $form_state, $filter and $defaults):
 * - $form: The prepopulated form array, which will usually have no use here.
 * - &$form_state: The form state of the (entire) configuration form.
 * - $filter: The filter object containing settings for the given format.
 * - $format: The format object being configured.
 * - $defaults: The default settings for the filter, as defined in 'default
 *   settings' in hook_filter_info().
 * - $filters: Complete list of filter objects that are enabled for the given
 *   format.
 *
 * @code
 *   function mymodule_filter_settings($form, &$form_state, $filter, $format, $defaults, $filters) {
 *     $settings['mymodule_url_length'] = array(
 *       '#type' => 'textfield',
 *       '#title' => t('Maximum link text length'),
 *       '#default_value' => isset($filter->settings['mymodule_url_length']) ? $filter->settings['mymodule_url_length'] : $defaults['mymodule_url_length'],
 *     );
 *     return $settings;
 *   }
 * @endcode
 *
 * 'prepare callback' and 'process callback' are invoked with the following
 * arguments:
 * - $text: The text to be filtered.
 * - $filter: The filter object containing settings for the given format.
 * - $format: The format object of the text to be filtered.
 * - $langcode: (optional) The language code of the text to be filtered.
 * - $cache: Boolean whether check_markup() will cache the filtered $text in
 *   {cache_filter}.
 * - $cache_id: The cache ID used for $text in {cache_filter} when $cache is
 *   TRUE.
 *
 * @see check_markup()
 *
 * 'prepare callback' and 'process callback' functions may access the filter
 * settings in $filter->settings['mymodule_url_length'].
 *
 * 'tips callback' is invoked with the following parameters:
 * - $filter: The filter object containing settings for the given format.
 * - $format: The format object of the text to be filtered.
 * - $long: Boolean whether to return long or short filter guidelines.
 *
 * For performance reasons content is only filtered once; the result is stored
 * in the cache table and retrieved from the cache the next time the same piece
 * of content is displayed. If a filter's output is dynamic, it can override the
 * cache mechanism, but obviously this should be used with caution: having one
 * filter that does not support caching in a particular text format disables
 * caching for the entire format, not just for one filter.
 *
 * Beware of the filter cache when developing your module: it is advised to set
 * your filter to 'cache' => FALSE while developing, but be sure to remove it
 * again if it's not needed. You can clear the cache by running the SQL query
 * 'DELETE * FROM cache_filter';
 *
 * @return
 *   An array of filter items. Each filter item has a unique name, prefixed
 *   with the name of the module that provides it. The item is an associative
 *   array that may contain the following key-value pairs:
 *   - 'title': (required) The administrative title of the filter.
 *   - 'description': A short, administrative description of what this filter
 *     does.
 *   - 'prepare callback': A callback function to call in the 'prepare' step
 *     of the filtering.
 *   - 'process callback': (required) The callback function to call in the
 *     'process' step of the filtering.
 *   - 'settings callback': A callback function that provides form controls
 *     for the filter's settings. Each filter should apply either the default
 *     settings or the configured settings contained in $filter->settings. The
 *     user submitted values are stored in the database.
 *   - 'default settings': An array containing default settings for a filter to
 *     be applied when the filter has not been configured yet.
 *   - 'tips callback': A callback function that provides tips for using the
 *     filter. A module's tips should be informative and to the point. Short
 *     tips are preferably one-liners.
 *   - 'cache': Specifies whether the filtered text can be cached. TRUE by
 *     default. Note that defining FALSE makes the entire text format not
 *     cacheable, which may have an impact on the site's overall performance.
 *
 * For a detailed usage example, see filter_example.module. For an example of
 * using multiple filters in one module, see filter_filter_info().
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
 * Perform actions when a new text format has been created.
 *
 * @param $format
 *   The format object of the format being updated.
 *
 * @see hook_filter_format_update()
 * @see hook_filter_format_delete()
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
 * @see hook_filter_format_delete()
 */
function hook_filter_format_update($format) {
  mymodule_cache_rebuild();
}

/**
 * Perform actions when a text format has been deleted.
 *
 * All modules storing references to text formats have to implement this hook.
 *
 * When a text format is deleted, all content that previously had that format
 * assigned needs to be switched to the passed fallback format.
 *
 * @param $format
 *   The format object of the format being deleted.
 * @param $fallback
 *   The format object of the format to use as replacement.
 *
 * @see hook_filter_format_insert()
 * @see hook_filter_format_update()
 */
function hook_filter_format_delete($format, $fallback) {
  // Replace the deleted format with the fallback format.
  db_update('my_module_table')
    ->fields(array('format' => $fallback->format))
    ->condition('format', $format->format)
    ->execute();
}

/**
 * @} End of "addtogroup hooks".
 */
