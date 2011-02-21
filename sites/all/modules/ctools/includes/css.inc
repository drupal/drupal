<?php
/* $Id: css.inc,v 1.18 2010/10/15 20:54:24 merlinofchaos Exp $ */

/*
 * @file
 * CSS filtering functions. Contains a disassembler, filter, compressor, and
 * decompressor.
 *
 * The general usage of this tool is:
 *
 * To simply filter CSS:
 * @code
 *   $filtered_css = ctools_css_filter($css, TRUE);
 * @endcode
 *
 * In the above, if the second argument is TRUE, the returned CSS will
 * be compressed. Otherwise it will be returned in a well formatted
 * syntax.
 *
 * To cache unfiltered CSS in a file, which will be filtered:
 *
 * @code
 *   $filename = ctools_css_cache($css, TRUE);
 * @endcode
 *
 * In the above, if the second argument is FALSE, the CSS will not be filtered.
 *
 * This file will be cached within the Drupal files system. This system cannot
 * detect when this file changes, so it is YOUR responsibility to remove and
 * re-cache this file when the CSS is changed. Your system should also contain
 * a backup method of re-generating the CSS cache in case it is removed, so
 * that it is easy to force a re-cache by simply deleting the contents of the
 * directory.
 *
 * Finally, if for some reason your application cannot store the filename
 * (which is true of Panels where the style can't force the display to
 * resave unconditionally) you can use the ctools storage mechanism. You
 * simply have to come up with a unique Id:
 *
 * @code
 *   $filename = ctools_css_store($id, $css, TRUE);
 * @endcode
 *
 * Then later on:
 * @code
 *   $filename = ctools_css_retrieve($id);
 *   drupal_add_css($filename);
 * @endcode
 *
 * The CSS that was generated will be stored in the database, so even if the
 * file was removed the cached CSS will be used. If the CSS cache is
 * cleared you may be required to regenerate your CSS. This will normally
 * only be cleared by an administrator operation, not during normal usage.
 *
 * You may remove your stored CSS this way:
 *
 * @code
 *   ctools_css_clear($id);
 * @endcode
 */

/**
 * Store CSS with a given id and return the filename to use.
 *
 * This function associates a piece of CSS with an id, and stores the
 * cached filename and the actual CSS for later use with
 * ctools_css_retrieve.
 */
function ctools_css_store($id, $css, $filter = TRUE) {
  $filename = db_query('SELECT filename FROM {ctools_css_cache} WHERE cid = :cid', array(':cid' => $id))->fetchField();
  if ($filename && file_exists($filename)) {
    file_unmanaged_delete($filename);
  }
  // Remove any previous records.
  db_delete('ctools_css_cache')
    ->condition('cid', $id)
    ->execute();

  $filename = ctools_css_cache($css, $filter);

  db_insert('ctools_css_cache')
    ->fields(array(
      'cid' => $id,
      'filename' => $filename,
      'css' => $css,
      'filter' => intval($filter),
    ))
    ->execute();

  return $filename;
}

/**
 * Retrieve a filename associated with an id of previously cached CSS.
 *
 * This will ensure the file still exists and, if not, create it.
 */
function ctools_css_retrieve($id) {
  $cache = db_query('SELECT * FROM {ctools_css_cache} WHERE cid = :cid', array(':cid' => $id))->fetchObject();
  if (!$cache) {
    return;
  }

  if (!file_exists($cache->filename)) {
    $filename = ctools_css_cache($cache->css, $cache->filter);
    if ($filename != $cache->filename) {
      db_update('ctools_css_cache')
        ->fields(array('filename' => $filename))
        ->condition('cid', $id)
        ->execute();
      $cache->filename = $filename;
    }
  }

  return $cache->filename;
}

/**
 * Remove stored CSS and any associated file.
 */
function ctools_css_clear($id) {
  $cache = db_query('SELECT * FROM {ctools_css_cache} WHERE cid = :cid', array(':cid' => $id))->fetchObject();
  if (!$cache) {
    return;
  }

  if (file_exists($cache->filename)) {
    file_unmanaged_delete($cache->filename);
    // If we remove an existing file, there may be cached pages that refer
    // to it. We must get rid of them: FIXME same format in D7?
    cache_clear_all();
  }

  db_delete('ctools_css_cache')
    ->condition('cid', $id)
    ->execute();
}

/**
 * Write a chunk of CSS to a temporary cache file and return the file name.
 *
 * This function optionally filters the CSS (always compressed, if so) and
 * generates a unique filename based upon md5. It returns that filename that
 * can be used with drupal_add_css(). Note that as a cache file, technically
 * this file is volatile so it should be checked before it is used to ensure
 * that it exists.
 *
 * You can use file_exists() to test for the file and file_delete() to remove
 * it if it needs to be cleared.
 *
 * @param $css
 *   A chunk of well-formed CSS text to cache.
 * @param $filter
 *   If TRUE the css will be filtered. If FALSE the text will be cached
 *   as-is.
 *
 * @return $filename
 *   The filename the CSS will be cached in.
 */
function ctools_css_cache($css, $filter = TRUE) {
  if ($filter) {
    $css = ctools_css_filter($css);
  }

  // Create the css/ within the files folder.
  $path = 'public://ctools/css';
  if (!file_prepare_directory($path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
//  if (!file_prepare_directory($path, FILE_CREATE_DIRECTORY)) {
    drupal_set_message(t('Unable to create CTools CSS cache directory. Check the permissions on your files directory.'), 'error');
    return;
  }

  // @todo Is this slow? Does it matter if it is?
  $filename = $path . '/' . md5($css) . '.css';

  // This will do renames if the file already exists, ensuring we don't
  // accidentally overwrite other files who share the same md5. Yes this
  // is a very miniscule chance but it's safe.
  $filename = file_unmanaged_save_data($css, $filename);

  return $filename;
}

/**
 * Filter a chunk of CSS text.
 *
 * This function disassembles the CSS into a raw format that makes it easier
 * for our tool to work, then runs it through the filter and reassembles it.
 * If you find that you want the raw data for some reason or another, you
 * can use the disassemble/assemble functions yourself.
 *
 * @param $css
 *   The CSS text to filter.
 * @param $compressed
 *   If true, generate compressed output; if false, generate pretty output.
 *   Defaults to TRUE.
 */
function ctools_css_filter($css, $compressed = TRUE) {
  $css_data = ctools_css_disassemble($css);

  // Note: By using this function yourself you can control the allowed
  // properties and values list.
  $filtered = ctools_css_filter_css_data($css_data);

  return $compressed ? ctools_css_compress($filtered) : ctools_css_assemble($filtered);
}

/**
 * Re-assemble a css string and format it nicely.
 *
 * @param array $css_data
 *   An array of css data, as produced by @see ctools_css_disassemble()
 *   disassembler and the @see ctools_css_filter_css_data() filter.
 *
 * @return string $css
 *   css optimized for human viewing.
 */
function ctools_css_assemble($css_data) {
  // Initialize the output.
  $css = '';
  // Iterate through all the statements.
  foreach ($css_data as $selector_str => $declaration) {
    // Add the selectors, separating them with commas and line feeds.
    $css .= strpos($selector_str, ',') === FALSE ? $selector_str : str_replace(", ", ",\n", $selector_str);
    // Add the opening curly brace.
    $css .= " {\n";
    // Iterate through all the declarations.
    foreach ($declaration as $property => $value) {
      $css .= "  " . $property . ": " . $value . ";\n";
    }
    // Add the closing curly brace.
    $css .= "}\n\n";
  }
  // Return the output.
  return $css;
}

/**
 * Compress css data (filter it first!) to optimize for use on view.
 *
 * @param array $css_data
 *   An array of css data, as produced by @see ctools_css_disassemble()
 *   disassembler and the @see ctools_css_filter_css_data() filter.
 *
 * @return string $css
 *   css optimized for use.
 */
function ctools_css_compress($css_data) {
  // Initialize the output.
  $css = '';
  // Iterate through all the statements.
  foreach ($css_data as $selector_str => $declaration) {
    if (empty($declaration)) {
      // Skip this statement if filtering removed all parts of the declaration.
      continue;
    }
    // Add the selectors, separating them with commas.
    $css .= $selector_str;
    // And, the opening curly brace.
    $css .= "{";
    // Iterate through all the statement properties.
    foreach ($declaration as $property => $value) {
      $css .= $property . ':' . $value . ';';
    }
    // Add the closing curly brace.
    $css .= "}";
  }
  // Return the output.
  return $css;
}

/**
 * Disassemble the css string.
 *
 * Strip the css of irrelevant characters, invalid/malformed selectors and
 * declarations, and otherwise prepare it for processing.
 *
 * @param string $css
 *   A string containing the css to be disassembled.
 *
 * @return array $disassembled_css
 *   An array of disassembled, slightly cleaned-up/formatted css statements.
 */
function ctools_css_disassemble($css) {
  $disassembled_css = array();
  // Remove comments.
  $css = preg_replace("/\/\*(.*)?\*\//Usi", "", $css);
  // Split out each statement
  $statements = explode("}", $css);
  // If we have any statements, parse them.
  if (!empty($statements)) {
    // Iterate through all of the statements.
    foreach ($statements as $statement) {
      // Get the selector(s) and declaration.
      if (empty($statement) || !strpos($statement, '{')) {
        continue;
      }

      list($selector_str, $declaration) = explode('{', $statement);

      // If the selector exists, then disassemble it, check it, and regenerate
      // the selector string.
      $selector_str = empty($selector_str) ? FALSE : _ctools_css_disassemble_selector($selector_str);
      if (empty($selector_str)) {
        // No valid selectors. Bomb out and start the next item.
        continue;
      }

      // Disassemble the declaration, check it and tuck it into an array.
      $disassembled_css[$selector_str] = _ctools_css_disassemble_declaration($declaration);
    }
  }
  return $disassembled_css;
}

function _ctools_css_disassemble_selector($selector_str) {
  // Get all selectors individually.
  $selectors = explode(",", trim($selector_str));
  // Iterate through all the selectors, sanity check them and return if they
  // pass. Note that this handles 0, 1, or more valid selectors gracefully.
  foreach ($selectors as $key => $selector) {
    // Replace un-needed characters and do a little cleanup.
    $selector = preg_replace("/[\n|\t|\\|\s]+/", ' ', trim($selector));
    // Make sure this is still a real selector after cleanup.
    if (!empty($selector)) {
      $selectors[$key] = $selector;
    }
    else {
      // Selector is no good, so we scrap it.
      unset($selectors[$key]);
    }
  }
  // Check for malformed selectors; if found, we skip this declaration.
  if (empty($selectors)) {
    return FALSE;
  }
  return implode(', ', $selectors);
}

function _ctools_css_disassemble_declaration($declaration) {
  $formatted_statement = array();
  $propval_pairs = explode(";", $declaration);
  // Make sure we actually have some properties to work with.
  if (!empty($propval_pairs)) {
    // Iterate through the remains and parse them.
    foreach ($propval_pairs as $key => $propval_pair) {
      // Check that we have a ':', otherwise it's an invalid pair.
      if (strpos($propval_pair, ':') === FALSE) {
        continue;
      }
      // Clean up the current property-value pair.
      $propval_pair = preg_replace("/[\n|\t|\\|\s]+/", ' ', trim($propval_pair));
      // Explode the remaining fragements some more, but clean them up first.
      list($property, $value) = explode(':', $propval_pair, 2);
      // If the property survived, toss it onto the stack.
      if (!empty($property)) {
        $formatted_statement[trim($property)] = trim($value);
      }
    }
  }
  return $formatted_statement;
}

/**
 * Run disassembled $css through the filter.
 *
 * @param $css
 *   CSS code disassembled by ctools_dss_disassemble().
 * @param $allowed_properties
 *   A list of properties that are allowed by the filter. If empty
 *   ctools_css_filter_default_allowed_properties() will provide the
 *   list.
 * @param $allowed_values
 *   A list of values that are allowed by the filter. If empty
 *   ctools_css_filter_default_allowed_values() will provide the
 *   list.
 *
 * @return
 *   An array of disassembled, filtered CSS.
 */
function ctools_css_filter_css_data($css, $allowed_properties = array(), $allowed_values = array(), $allowed_values_regex = '', $disallowed_values_regex = '') {
//function ctools_css_filter_css_data($css, &$filtered = NULL, $allowed_properties = array(), $allowed_values = array(), $allowed_values_regex = '', $disallowed_values_regex = '') {
  // Retrieve the default list of allowed properties if none is provided.
  $allowed_properties = !empty($allowed_properties) ? $allowed_properties : ctools_css_filter_default_allowed_properties();
  // Retrieve the default list of allowed values if none is provided.
  $allowed_values = !empty($allowed_values) ? $allowed_values : ctools_css_filter_default_allowed_values();
  // Define allowed values regex if none is provided.
  $allowed_values_regex = !empty($allowed_values_regex) ? $allowed_values_regex : '/(#[0-9a-f]+|rgb\(\d+%?,\d*%?,?\d*%?\)?|\d{0,2}\.?\d{0,2}(cm|em|ex|in|mm|pc|pt|px|%|,|\))?)/';
  // Define disallowed url() value contents, if none is provided.
  // $disallowed_values_regex = !empty($disallowed_values_regex) ? $disallowed_values_regex : '/[url|expression]\s*\(\s*[^\s)]+?\s*\)\s*/';
  $disallowed_values_regex = !empty($disallowed_values_regex) ? $disallowed_values_regex : '/(url|expression)/';

  foreach ($css as $selector_str => $declaration) {
    foreach ($declaration as $property => $value) {
      if (!in_array($property, $allowed_properties)) {
        // $filtered['properties'][$selector_str][$property] = $value;
        unset($css[$selector_str][$property]);
        continue;
      }
      $value = str_replace('!important', '', $value);
      if (preg_match($disallowed_values_regex, $value) || !(in_array($value, $allowed_values) || preg_match($allowed_values_regex, $value))) {
        // $filtered['values'][$selector_str][$property] = $value;
        unset($css[$selector_str][$property]);
        continue;
      }
    }
  }
  return $css;
}

/**
 * Provide a deafult list of allowed properties by the filter.
 */
function ctools_css_filter_default_allowed_properties() {
  return array(
    'azimuth',
    'background',
    'background-color',
    'background-image',
    'background-repeat',
    'background-attachment',
    'background-position',
    'border',
    'border-top-width',
    'border-right-width',
    'border-bottom-width',
    'border-left-width',
    'border-width',
    'border-top-color',
    'border-right-color',
    'border-bottom-color',
    'border-left-color',
    'border-color',
    'border-top-style',
    'border-right-style',
    'border-bottom-style',
    'border-left-style',
    'border-style',
    'border-top',
    'border-right',
    'border-bottom',
    'border-left',
    'clear',
    'color',
    'cursor',
    'direction',
    'display',
    'elevation',
    'float',
    'font',
    'font-family',
    'font-size',
    'font-style',
    'font-variant',
    'font-weight',
    'height',
    'letter-spacing',
    'line-height',
    'margin',
    'margin-top',
    'margin-right',
    'margin-bottom',
    'margin-left',
    'overflow',
    'padding',
    'padding-top',
    'padding-right',
    'padding-bottom',
    'padding-left',
    'pause',
    'pause-after',
    'pause-before',
    'pitch',
    'pitch-range',
    'richness',
    'speak',
    'speak-header',
    'speak-numeral',
    'speak-punctuation',
    'speech-rate',
    'stress',
    'text-align',
    'text-decoration',
    'text-indent',
    'text-transform',
    'unicode-bidi',
    'vertical-align',
    'voice-family',
    'volume',
    'white-space',
    'width',
    'fill',
    'fill-opacity',
    'fill-rule',
    'stroke',
    'stroke-width',
    'stroke-linecap',
    'stroke-linejoin',
    'stroke-opacity',
  );
}

/**
 * Provide a default list of allowed values by the filter.
 */
function ctools_css_filter_default_allowed_values() {
  return array(
    'auto',
    'aqua',
    'black',
    'block',
    'blue',
    'bold',
    'both',
    'bottom',
    'brown',
    'capitalize',
    'center',
    'collapse',
    'dashed',
    'dotted',
    'fuchsia',
    'gray',
    'green',
    'italic',
    'inherit',
    'left',
    'lime',
    'lowercase',
    'maroon',
    'medium',
    'navy',
    'normal',
    'nowrap',
    'olive',
    'pointer',
    'purple',
    'red',
    'right',
    'solid',
    'silver',
    'teal',
    'top',
    'transparent',
    'underline',
    'uppercase',
    'white',
    'yellow',
  );
}

/**
 * Delegated implementation of hook_flush_caches()
 */
function ctools_css_flush_caches() {
  // Remove all generated files.
  // @see http://drupal.org/node/573292
  // file_unmanaged_delete_recursive('public://render');
  $filedir = file_default_scheme() . '://ctools/css';
  file_unmanaged_delete_recursive($filedir);

  db_delete('ctools_css_cache')->execute();
}
