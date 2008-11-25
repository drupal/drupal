<?php
// $Id$

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
 * Content in Drupal is passed through all enabled filters before it is
 * output. This lets a module modify content to the site administrator's
 * liking.
 *
 * This hook contains all that is needed for having a module provide filtering
 * functionality.
 *
 * Depending on $op, different tasks are performed.
 *
 * A module can contain as many filters as it wants. The 'list' operation tells
 * the filter system which filters are available. Every filter has a numerical
 * 'delta' which is used to refer to it in every operation.
 *
 * Filtering is a two-step process. First, the content is 'prepared' by calling
 * the 'prepare' operation for every filter. The purpose of 'prepare' is to
 * escape HTML-like structures. For example, imagine a filter which allows the
 * user to paste entire chunks of programming code without requiring manual
 * escaping of special HTML characters like @< or @&. If the programming code
 * were left untouched, then other filters could think it was HTML and change
 * it. For most filters however, the prepare-step is not necessary, and they can
 * just return the input without changes.
 *
 * Filters should not use the 'prepare' step for anything other than escaping,
 * because that would short-circuits the control the user has over the order
 * in which filters are applied.
 *
 * The second step is the actual processing step. The result from the
 * prepare-step gets passed to all the filters again, this time with the
 * 'process' operation. It's here that filters should perform actual changing of
 * the content: transforming URLs into hyperlinks, converting smileys into
 * images, etc.
 *
 * An important aspect of the filtering system are 'input formats'. Every input
 * format is an entire filter setup: which filters to enable, in what order
 * and with what settings. Filters that provide settings should usually store
 * these settings per format.
 *
 * If the filter's behaviour depends on an extensive list and/or external data
 * (e.g. a list of smileys, a list of glossary terms) then filters are allowed
 * to provide a separate, global configuration page rather than provide settings
 * per format. In that case, there should be a link from the format-specific
 * settings to the separate settings page.
 *
 * For performance reasons content is only filtered once; the result is stored
 * in the cache table and retrieved the next time the piece of content is
 * displayed. If a filter's output is dynamic it can override the cache
 * mechanism, but obviously this feature should be used with caution: having one
 * 'no cache' filter in a particular input format disables caching for the
 * entire format, not just for one filter.
 *
 * Beware of the filter cache when developing your module: it is advised to set
 * your filter to 'no cache' while developing, but be sure to remove it again
 * if it's not needed. You can clear the cache by running the SQL query 'DELETE
 * FROM cache_filter';
 *
 * @param $op
 *  Which filtering operation to perform. Possible values:
 *   - list: provide a list of available filters.
 *     Returns an associative array of filter names with numerical keys.
 *     These keys are used for subsequent operations and passed back through
 *     the $delta parameter.
 *   - no cache: Return true if caching should be disabled for this filter.
 *   - description: Return a short description of what this filter does.
 *   - prepare: Return the prepared version of the content in $text.
 *   - process: Return the processed version of the content in $text.
 *   - settings: Return HTML form controls for the filter's settings. These
 *     settings are stored with variable_set() when the form is submitted.
 *     Remember to use the $format identifier in the variable and control names
 *     to store settings per input format (e.g. "mymodule_setting_$format").
 * @param $delta
 *   Which of the module's filters to use (applies to every operation except
 *   'list'). Modules that only contain one filter can ignore this parameter.
 * @param $format
 *   Which input format the filter is being used in (applies to 'prepare',
 *   'process' and 'settings').
 * @param $text
 *   The content to filter (applies to 'prepare' and 'process').
 * @param $langcode
 *   The language code associated with the content, e.g. 'en' for English.  This
 *   enables filters to be language aware and can be used to implement language
 *   specific text replacements.
 * @param $cache_id
 *   The cache id of the content.
 * @return
 *   The return value depends on $op. The filter hook is designed so that a
 *   module can return $text for operations it does not use/need.
 *
 * For a detailed usage example, see filter_example.module. For an example of
 * using multiple filters in one module, see filter_filter() and
 * filter_filter_tips().
 */
function hook_filter($op, $delta = 0, $format = -1, $text = '', $langcode = '', $cache_id = 0) {
  switch ($op) {
    case 'list':
      return array(0 => t('Code filter'));

    case 'description':
      return t('Allows users to post code verbatim using &lt;code&gt; and &lt;?php ?&gt; tags.');

    case 'prepare':
      // Note: we use the bytes 0xFE and 0xFF to replace < > during the
      // filtering process. These bytes are not valid in UTF-8 data and thus
      // least likely to cause problems.
      $text = preg_replace('@<code>(.+?)</code>@se', "'\xFEcode\xFF' . codefilter_escape('\\1') . '\xFE/code\xFF'", $text);
      $text = preg_replace('@<(\?(php)?|%)(.+?)(\?|%)>@se', "'\xFEphp\xFF' . codefilter_escape('\\3') . '\xFE/php\xFF'", $text);
      return $text;

    case "process":
      $text = preg_replace('@\xFEcode\xFF(.+?)\xFE/code\xFF@se', "codefilter_process_code('$1')", $text);
      $text = preg_replace('@\xFEphp\xFF(.+?)\xFE/php\xFF@se', "codefilter_process_php('$1')", $text);
      return $text;

    default:
      return $text;
  }
}

/**
 * Provide tips for using filters.
 *
 * A module's tips should be informative and to the point. Short tips are
 * preferably one-liners.
 *
 * @param $delta
 *   Which of this module's filters to use. Modules which only implement one
 *   filter can ignore this parameter.
 * @param $format
 *   Which format we are providing tips for.
 * @param $long
 *   If set to true, long tips are requested, otherwise short tips are needed.
 * @return
 *   The text of the filter tip.
 *
 *
 */
function hook_filter_tips($delta, $format, $long = false) {
  if ($long) {
    return t('To post pieces of code, surround them with &lt;code&gt;...&lt;/code&gt; tags. For PHP code, you can use &lt;?php ... ?&gt;, which will also colour it based on syntax.');
  }
  else {
    return t('You may post code using &lt;code&gt;...&lt;/code&gt; (generic) or &lt;?php ... ?&gt; (highlighted PHP) tags.');
  }
}

/**
 * @} End of "addtogroup hooks".
 */
