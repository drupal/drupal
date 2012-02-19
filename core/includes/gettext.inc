<?php

/**
 * @file
 * Gettext parsing and generating API.
 *
 * @todo Decouple these functions from Locale API and put to gettext_ namespace.
 */

/**
 * @defgroup locale-api-import-export Translation import/export API.
 * @{
 * Functions to import and export translations.
 *
 * These functions provide the ability to import translations from
 * external files and to export translations and translation templates.
 */

/**
 * Parses Gettext Portable Object information and inserts it into the database.
 *
 * @param $file
 *   Drupal file object corresponding to the PO file to import.
 * @param $langcode
 *   Language code.
 * @param $mode
 *   Should existing translations be replaced LOCALE_IMPORT_KEEP or
 *   LOCALE_IMPORT_OVERWRITE.
 */
function _locale_import_po($file, $langcode, $mode) {
  // Try to allocate enough time to parse and import the data.
  drupal_set_time_limit(240);

  // Check if we have the language already in the database.
  if (!language_load($langcode)) {
    drupal_set_message(t('The language selected for import is not supported.'), 'error');
    return FALSE;
  }

  // Get strings from file (returns on failure after a partial import, or on success)
  $status = _locale_import_read_po('db-store', $file, $mode, $langcode);
  if ($status === FALSE) {
    // Error messages are set in _locale_import_read_po().
    return FALSE;
  }

  // Get status information on import process.
  list($header_done, $additions, $updates, $deletes, $skips) = _locale_import_one_string('db-report');

  if (!$header_done) {
    drupal_set_message(t('The translation file %filename appears to have a missing or malformed header.', array('%filename' => $file->filename)), 'error');
  }

  // Clear cache and force refresh of JavaScript translations.
  _locale_invalidate_js($langcode);
  cache()->deletePrefix('locale:');

  // Rebuild the menu, strings may have changed.
  menu_rebuild();

  drupal_set_message(t('The translation was successfully imported. There are %number newly created translated strings, %update strings were updated and %delete strings were removed.', array('%number' => $additions, '%update' => $updates, '%delete' => $deletes)));
  watchdog('locale', 'Imported %file into %locale: %number new strings added, %update updated and %delete removed.', array('%file' => $file->filename, '%locale' => $langcode, '%number' => $additions, '%update' => $updates, '%delete' => $deletes));
  if ($skips) {
    if (module_exists('dblog')) {
      $skip_message = format_plural($skips, 'A translation string was skipped because of disallowed or malformed HTML. <a href="@url">See the log</a> for details.', '@count translation strings were skipped because of disallowed or malformed HTML. <a href="@url">See the log</a> for details.', array('@url' => url('admin/reports/dblog')));
    }
    else {
      $skip_message = format_plural($skips, 'A translation string was skipped because of disallowed or malformed HTML. See the log for details.', '@count translation strings were skipped because of disallowed or malformed HTML. See the log for details.');
    }
    drupal_set_message($skip_message, 'error');
    watchdog('locale', '@count disallowed HTML string(s) in %file', array('@count' => $skips, '%file' => $file->uri), WATCHDOG_WARNING);
  }
  return TRUE;
}

/**
 * Parses a Gettext Portable Object file into an array.
 *
 * @param $op
 *   Storage operation type: db-store or mem-store.
 * @param $file
 *   Drupal file object corresponding to the PO file to import.
 * @param $mode
 *   Should existing translations be replaced LOCALE_IMPORT_KEEP or
 *   LOCALE_IMPORT_OVERWRITE.
 * @param $lang
 *   Language code.
 */
function _locale_import_read_po($op, $file, $mode = NULL, $lang = NULL) {

  // The file will get closed by PHP on returning from this function.
  $fd = fopen($file->uri, 'rb');
  if (!$fd) {
    _locale_import_message('The translation import failed because the file %filename could not be read.', $file);
    return FALSE;
  }

  /*
   * The parser context. Can be:
   *  - 'COMMENT' (#)
   *  - 'MSGID' (msgid)
   *  - 'MSGID_PLURAL' (msgid_plural)
   *  - 'MSGCTXT' (msgctxt)
   *  - 'MSGSTR' (msgstr or msgstr[])
   *  - 'MSGSTR_ARR' (msgstr_arg)
   */
  $context = 'COMMENT';

  // Current entry being read.
  $current = array();

  // Current plurality for 'msgstr[]'.
  $plural = 0;

  // Current line.
  $lineno = 0;

  while (!feof($fd)) {
    // A line should not be longer than 10 * 1024.
    $line = fgets($fd, 10 * 1024);

    if ($lineno == 0) {
      // The first line might come with a UTF-8 BOM, which should be removed.
      $line = str_replace("\xEF\xBB\xBF", '', $line);
    }

    $lineno++;

    // Trim away the linefeed.
    $line = trim(strtr($line, array("\\\n" => "")));

    if (!strncmp('#', $line, 1)) {
      // Lines starting with '#' are comments.

      if ($context == 'COMMENT') {
        // Already in comment token, insert the comment.
        $current['#'][] = substr($line, 1);
      }
      elseif (($context == 'MSGSTR') || ($context == 'MSGSTR_ARR')) {
        // We are currently in string token, close it out.
        _locale_import_one_string($op, $current, $mode, $lang, $file);

        // Start a new entry for the comment.
        $current         = array();
        $current['#'][]  = substr($line, 1);

        $context = 'COMMENT';
      }
      else {
        // A comment following any other token is a syntax error.
        _locale_import_message('The translation file %filename contains an error: "msgstr" was expected but not found on line %line.', $file, $lineno);
        return FALSE;
      }
    }
    elseif (!strncmp('msgid_plural', $line, 12)) {
      // A plural form for the current message.

      if ($context != 'MSGID') {
        // A plural form cannot be added to anything else but the id directly.
        _locale_import_message('The translation file %filename contains an error: "msgid_plural" was expected but not found on line %line.', $file, $lineno);
        return FALSE;
      }

      // Remove 'msgid_plural' and trim away whitespace.
      $line = trim(substr($line, 12));
      // At this point, $line should now contain only the plural form.

      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        // The plural form must be wrapped in quotes.
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }

      // Append the plural form to the current entry.
      $current['msgid'] .= "\0" . $quoted;

      $context = 'MSGID_PLURAL';
    }
    elseif (!strncmp('msgid', $line, 5)) {
      // Starting a new message.

      if (($context == 'MSGSTR') || ($context == 'MSGSTR_ARR')) {
        // We are currently in a message string, close it out.
        _locale_import_one_string($op, $current, $mode, $lang, $file);

        // Start a new context for the id.
        $current = array();
      }
      elseif ($context == 'MSGID') {
        // We are currently already in the context, meaning we passed an id with no data.
        _locale_import_message('The translation file %filename contains an error: "msgid" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }

      // Remove 'msgid' and trim away whitespace.
      $line = trim(substr($line, 5));
      // At this point, $line should now contain only the message id.

      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        // The message id must be wrapped in quotes.
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }

      $current['msgid'] = $quoted;
      $context = 'MSGID';
    }
    elseif (!strncmp('msgctxt', $line, 7)) {
      // Starting a new context.

      if (($context == 'MSGSTR') || ($context == 'MSGSTR_ARR')) {
        // We are currently in a message, start a new one.
        _locale_import_one_string($op, $current, $mode, $lang, $file);
        $current = array();
      }
      elseif (!empty($current['msgctxt'])) {
        // A context cannot apply to another context.
        _locale_import_message('The translation file %filename contains an error: "msgctxt" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }

      // Remove 'msgctxt' and trim away whitespaces.
      $line = trim(substr($line, 7));
      // At this point, $line should now contain the context.

      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        // The context string must be quoted.
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }

      $current['msgctxt'] = $quoted;

      $context = 'MSGCTXT';
    }
    elseif (!strncmp('msgstr[', $line, 7)) {
      // A message string for a specific plurality.

      if (($context != 'MSGID') && ($context != 'MSGCTXT') && ($context != 'MSGID_PLURAL') && ($context != 'MSGSTR_ARR')) {
        // Message strings must come after msgid, msgxtxt, msgid_plural, or other msgstr[] entries.
        _locale_import_message('The translation file %filename contains an error: "msgstr[]" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }

      // Ensure the plurality is terminated.
      if (strpos($line, ']') === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }

      // Extract the plurality.
      $frombracket = strstr($line, '[');
      $plural = substr($frombracket, 1, strpos($frombracket, ']') - 1);

      // Skip to the next whitespace and trim away any further whitespace, bringing $line to the message data.
      $line = trim(strstr($line, " "));

      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        // The string must be quoted.
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }

      $current['msgstr'][$plural] = $quoted;

      $context = 'MSGSTR_ARR';
    }
    elseif (!strncmp("msgstr", $line, 6)) {
      // A string for the an id or context.

      if (($context != 'MSGID') && ($context != 'MSGCTXT')) {
        // Strings are only valid within an id or context scope.
        _locale_import_message('The translation file %filename contains an error: "msgstr" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }

      // Remove 'msgstr' and trim away away whitespaces.
      $line = trim(substr($line, 6));
      // At this point, $line should now contain the message.

      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        // The string must be quoted.
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }

      $current['msgstr'] = $quoted;

      $context = 'MSGSTR';
    }
    elseif ($line != '') {
      // Anything that is not a token may be a continuation of a previous token.

      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        // The string must be quoted.
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }

      // Append the string to the current context.
      if (($context == 'MSGID') || ($context == 'MSGID_PLURAL')) {
        $current['msgid'] .= $quoted;
      }
      elseif ($context == 'MSGCTXT') {
        $current['msgctxt'] .= $quoted;
      }
      elseif ($context == 'MSGSTR') {
        $current['msgstr'] .= $quoted;
      }
      elseif ($context == 'MSGSTR_ARR') {
        $current['msgstr'][$plural] .= $quoted;
      }
      else {
        // No valid context to append to.
        _locale_import_message('The translation file %filename contains an error: there is an unexpected string on line %line.', $file, $lineno);
        return FALSE;
      }
    }
  }

  // End of PO file, closed out the last entry.
  if (($context == 'MSGSTR') || ($context == 'MSGSTR_ARR')) {
    _locale_import_one_string($op, $current, $mode, $lang, $file);
  }
  elseif ($context != 'COMMENT') {
    _locale_import_message('The translation file %filename ended unexpectedly at line %line.', $file, $lineno);
    return FALSE;
  }
}

/**
 * Sets an error message if an error occurred during locale file parsing.
 *
 * @param $message
 *   The message to be translated.
 * @param $file
 *   Drupal file object corresponding to the PO file to import.
 * @param $lineno
 *   An optional line number argument.
 */
function _locale_import_message($message, $file, $lineno = NULL) {
  $vars = array('%filename' => $file->filename);
  if (isset($lineno)) {
    $vars['%line'] = $lineno;
  }
  $t = get_t();
  drupal_set_message($t($message, $vars), 'error');
}

/**
 * Performs the specified operation for one string.
 *
 * @param $op
 *   Operation to perform: 'db-store', 'db-report', 'mem-store' or 'mem-report'.
 * @param $value
 *   Details of the string stored.
 * @param $mode
 *   Should existing translations be replaced LOCALE_IMPORT_KEEP or
 *   LOCALE_IMPORT_OVERWRITE.
 * @param $lang
 *   Language to store the string in.
 * @param $file
 *   Object representation of file being imported, only required when op is
 *   'db-store'.
 */
function _locale_import_one_string($op, $value = NULL, $mode = NULL, $lang = NULL, $file = NULL) {
  $report = &drupal_static(__FUNCTION__, array('additions' => 0, 'updates' => 0, 'deletes' => 0, 'skips' => 0));
  $header_done = &drupal_static(__FUNCTION__ . ':header_done', FALSE);
  $strings = &drupal_static(__FUNCTION__ . ':strings', array());

  switch ($op) {
    // Return stored strings
    case 'mem-report':
      return $strings;

    // Store string in memory (only supports single strings)
    case 'mem-store':
      $strings[isset($value['msgctxt']) ? $value['msgctxt'] : ''][$value['msgid']] = $value['msgstr'];
      return;

    // Called at end of import to inform the user
    case 'db-report':
      return array($header_done, $report['additions'], $report['updates'], $report['deletes'], $report['skips']);

    // Store the string we got in the database.
    case 'db-store':
      // We got header information.
      if ($value['msgid'] == '') {
        $locale_plurals = variable_get('locale_translation_plurals', array());
        if (($mode != LOCALE_IMPORT_KEEP) || empty($locale_plurals[$lang]['plurals'])) {
          // Since we only need to parse the header if we ought to update the
          // plural formula, only run this if we don't need to keep existing
          // data untouched or if we don't have an existing plural formula.
          $header = _locale_import_parse_header($value['msgstr']);

          // Get the plural formula and update in database.
          if (isset($header["Plural-Forms"]) && $p = _locale_import_parse_plural_forms($header["Plural-Forms"], $file->uri)) {
            list($nplurals, $formula) = $p;
          }
          else {
            $nplurals = 0;
            $formula = '';
          }
          $locale_plurals[$lang] = array(
            'plurals' => $nplurals,
            'formula' => $formula,
          );
          variable_set('locale_translation_plurals', $locale_plurals);
        }
        $header_done = TRUE;
      }

      else {
        // Some real string to import.
        $comments = _locale_import_shorten_comments(empty($value['#']) ? array() : $value['#']);

        if (strpos($value['msgid'], "\0")) {
          // This string has plural versions.
          $english = explode("\0", $value['msgid'], 2);
          $entries = array_keys($value['msgstr']);
          for ($i = 3; $i <= count($entries); $i++) {
            $english[] = $english[1];
          }
          $translation = array_map('_locale_import_append_plural', $value['msgstr'], $entries);
          $english = array_map('_locale_import_append_plural', $english, $entries);
          foreach ($translation as $key => $trans) {
            if ($key == 0) {
              $plid = 0;
            }
            $plid = _locale_import_one_string_db($report, $lang, isset($value['msgctxt']) ? $value['msgctxt'] : '', $english[$key], $trans, $comments, $mode, $plid, $key);
          }
        }

        else {
          // A simple string to import.
          $english = $value['msgid'];
          $translation = $value['msgstr'];
          _locale_import_one_string_db($report, $lang, isset($value['msgctxt']) ? $value['msgctxt'] : '', $english, $translation, $comments, $mode);
        }
      }
  } // end of db-store operation
}

/**
 * Imports one string into the database.
 *
 * @param $report
 *   Report array summarizing the number of changes done in the form:
 *   array(inserts, updates, deletes).
 * @param $langcode
 *   Language code to import string into.
 * @param $context
 *   The context of this string.
 * @param $source
 *   Source string.
 * @param $translation
 *   Translation to language specified in $langcode.
 * @param $location
 *   Location value to save with source string.
 * @param $mode
 *   Import mode to use, LOCALE_IMPORT_KEEP or LOCALE_IMPORT_OVERWRITE.
 * @param $plid
 *   Optional plural ID to use.
 * @param $plural
 *   Optional plural value to use.
 *
 * @return
 *   The string ID of the existing string modified or the new string added.
 */
function _locale_import_one_string_db(&$report, $langcode, $context, $source, $translation, $location, $mode, $plid = 0, $plural = 0) {
  $lid = db_query("SELECT lid FROM {locales_source} WHERE source = :source AND context = :context", array(':source' => $source, ':context' => $context))->fetchField();

  if (!empty($translation)) {
    // Skip this string unless it passes a check for dangerous code.
    if (!locale_string_is_safe($translation)) {
      watchdog('locale', 'Import of string "%string" was skipped because of disallowed or malformed HTML.', array('%string' => $translation), WATCHDOG_ERROR);
      $report['skips']++;
      $lid = 0;
    }
    elseif ($lid) {
      // We have this source string saved already.
      db_update('locales_source')
        ->fields(array(
          'location' => $location,
        ))
        ->condition('lid', $lid)
        ->execute();

      $exists = db_query("SELECT COUNT(lid) FROM {locales_target} WHERE lid = :lid AND language = :language", array(':lid' => $lid, ':language' => $langcode))->fetchField();

      if (!$exists) {
        // No translation in this language.
        db_insert('locales_target')
          ->fields(array(
            'lid' => $lid,
            'language' => $langcode,
            'translation' => $translation,
            'plid' => $plid,
            'plural' => $plural,
          ))
          ->execute();

        $report['additions']++;
      }
      elseif ($mode == LOCALE_IMPORT_OVERWRITE) {
        // Translation exists, only overwrite if instructed.
        db_update('locales_target')
          ->fields(array(
            'translation' => $translation,
            'plid' => $plid,
            'plural' => $plural,
          ))
          ->condition('language', $langcode)
          ->condition('lid', $lid)
          ->execute();

        $report['updates']++;
      }
    }
    else {
      // No such source string in the database yet.
      $lid = db_insert('locales_source')
        ->fields(array(
          'location' => $location,
          'source' => $source,
          'context' => (string) $context,
        ))
        ->execute();

      db_insert('locales_target')
        ->fields(array(
           'lid' => $lid,
           'language' => $langcode,
           'translation' => $translation,
           'plid' => $plid,
           'plural' => $plural
        ))
        ->execute();

      $report['additions']++;
    }
  }
  elseif ($mode == LOCALE_IMPORT_OVERWRITE) {
    // Empty translation, remove existing if instructed.
    db_delete('locales_target')
      ->condition('language', $langcode)
      ->condition('lid', $lid)
      ->condition('plid', $plid)
      ->condition('plural', $plural)
      ->execute();

    $report['deletes']++;
  }

  return $lid;
}

/**
 * Parses a Gettext Portable Object file header.
 *
 * @param $header
 *   A string containing the complete header.
 *
 * @return
 *   An associative array of key-value pairs.
 */
function _locale_import_parse_header($header) {
  $header_parsed = array();
  $lines = array_map('trim', explode("\n", $header));
  foreach ($lines as $line) {
    if ($line) {
      list($tag, $contents) = explode(":", $line, 2);
      $header_parsed[trim($tag)] = trim($contents);
    }
  }
  return $header_parsed;
}

/**
 * Parses a Plural-Forms entry from a Gettext Portable Object file header.
 *
 * @param $pluralforms
 *   A string containing the Plural-Forms entry.
 * @param $filepath
 *   A string containing the filepath.
 *
 * @return
 *   An array containing the number of plurals and a
 *   formula in PHP for computing the plural form.
 */
function _locale_import_parse_plural_forms($pluralforms, $filepath) {
  // First, delete all whitespace
  $pluralforms = strtr($pluralforms, array(" " => "", "\t" => ""));

  // Select the parts that define nplurals and plural
  $nplurals = strstr($pluralforms, "nplurals=");
  if (strpos($nplurals, ";")) {
    $nplurals = substr($nplurals, 9, strpos($nplurals, ";") - 9);
  }
  else {
    return FALSE;
  }
  $plural = strstr($pluralforms, "plural=");
  if (strpos($plural, ";")) {
    $plural = substr($plural, 7, strpos($plural, ";") - 7);
  }
  else {
    return FALSE;
  }

  // Get PHP version of the plural formula
  $plural = _locale_import_parse_arithmetic($plural);

  if ($plural !== FALSE) {
    return array($nplurals, $plural);
  }
  else {
    drupal_set_message(t('The translation file %filepath contains an error: the plural formula could not be parsed.', array('%filepath' => $filepath)), 'error');
    return FALSE;
  }
}

/**
 * Parses and sanitizes an arithmetic formula into a PHP expression.
 *
 * While parsing, we ensure, that the operators have the right
 * precedence and associativity.
 *
 * @param $string
 *   A string containing the arithmetic formula.
 *
 * @return
 *   The PHP version of the formula.
 */
function _locale_import_parse_arithmetic($string) {
  // Operator precedence table
  $precedence = array("(" => -1, ")" => -1, "?" => 1, ":" => 1, "||" => 3, "&&" => 4, "==" => 5, "!=" => 5, "<" => 6, ">" => 6, "<=" => 6, ">=" => 6, "+" => 7, "-" => 7, "*" => 8, "/" => 8, "%" => 8);
  // Right associativity
  $right_associativity = array("?" => 1, ":" => 1);

  $tokens = _locale_import_tokenize_formula($string);

  // Parse by converting into infix notation then back into postfix
  // Operator stack - holds math operators and symbols
  $operator_stack = array();
  // Element Stack - holds data to be operated on
  $element_stack = array();

  foreach ($tokens as $token) {
    $current_token = $token;

    // Numbers and the $n variable are simply pushed into $element_stack
    if (is_numeric($token)) {
      $element_stack[] = $current_token;
    }
    elseif ($current_token == "n") {
      $element_stack[] = '$n';
    }
    elseif ($current_token == "(") {
      $operator_stack[] = $current_token;
    }
    elseif ($current_token == ")") {
      $topop = array_pop($operator_stack);
      while (isset($topop) && ($topop != "(")) {
        $element_stack[] = $topop;
        $topop = array_pop($operator_stack);
      }
    }
    elseif (!empty($precedence[$current_token])) {
      // If it's an operator, then pop from $operator_stack into $element_stack until the
      // precedence in $operator_stack is less than current, then push into $operator_stack
      $topop = array_pop($operator_stack);
      while (isset($topop) && ($precedence[$topop] >= $precedence[$current_token]) && !(($precedence[$topop] == $precedence[$current_token]) && !empty($right_associativity[$topop]) && !empty($right_associativity[$current_token]))) {
        $element_stack[] = $topop;
        $topop = array_pop($operator_stack);
      }
      if ($topop) {
        $operator_stack[] = $topop;   // Return element to top
      }
      $operator_stack[] = $current_token;      // Parentheses are not needed
    }
    else {
      return FALSE;
    }
  }

  // Flush operator stack
  $topop = array_pop($operator_stack);
  while ($topop != NULL) {
    $element_stack[] = $topop;
    $topop = array_pop($operator_stack);
  }

  // Now extract formula from stack
  $previous_size = count($element_stack) + 1;
  while (count($element_stack) < $previous_size) {
    $previous_size = count($element_stack);
    for ($i = 2; $i < count($element_stack); $i++) {
      $op = $element_stack[$i];
      if (!empty($precedence[$op])) {
        $f = "";
        if ($op == ":") {
          $f = $element_stack[$i - 2] . "):" . $element_stack[$i - 1] . ")";
        }
        elseif ($op == "?") {
          $f = "(" . $element_stack[$i - 2] . "?(" . $element_stack[$i - 1];
        }
        else {
          $f = "(" . $element_stack[$i - 2] . $op . $element_stack[$i - 1] . ")";
        }
        array_splice($element_stack, $i - 2, 3, $f);
        break;
      }
    }
  }

  // If only one element is left, the number of operators is appropriate
  if (count($element_stack) == 1) {
    return $element_stack[0];
  }
  else {
    return FALSE;
  }
}

/**
 * Provides backward-compatible formula parsing for token_get_all().
 *
 * @param $string
 *   A string containing the arithmetic formula.
 *
 * @return
 *   The PHP version of the formula.
 */
function _locale_import_tokenize_formula($formula) {
  $formula = str_replace(" ", "", $formula);
  $tokens = array();
  for ($i = 0; $i < strlen($formula); $i++) {
    if (is_numeric($formula[$i])) {
      $num = $formula[$i];
      $j = $i + 1;
      while ($j < strlen($formula) && is_numeric($formula[$j])) {
        $num .= $formula[$j];
        $j++;
      }
      $i = $j - 1;
      $tokens[] = $num;
    }
    elseif ($pos = strpos(" =<>!&|", $formula[$i])) { // We won't have a space
      $next = $formula[$i + 1];
      switch ($pos) {
        case 1:
        case 2:
        case 3:
        case 4:
          if ($next == '=') {
            $tokens[] = $formula[$i] . '=';
            $i++;
          }
          else {
            $tokens[] = $formula[$i];
          }
          break;
        case 5:
          if ($next == '&') {
            $tokens[] = '&&';
            $i++;
          }
          else {
            $tokens[] = $formula[$i];
          }
          break;
        case 6:
          if ($next == '|') {
            $tokens[] = '||';
            $i++;
          }
          else {
            $tokens[] = $formula[$i];
          }
          break;
      }
    }
    else {
      $tokens[] = $formula[$i];
    }
  }
  return $tokens;
}

/**
 * Adds count indices to a string.
 *
 * Callback for array_map() within _locale_import_one_string().
 *
 * @param $entry
 *   An array element.
 * @param $key
 *   Index of the array element.
 */
function _locale_import_append_plural($entry, $key) {
  // No modifications for 0, 1
  if ($key == 0 || $key == 1) {
    return $entry;
  }

  // First remove any possibly false indices, then add new ones
  $entry = preg_replace('/(@count)\[[0-9]\]/', '\\1', $entry);
  return preg_replace('/(@count)/', "\\1[$key]", $entry);
}

/**
 * Generates a short, one-string version of the passed comment array.
 *
 * @param $comment
 *   An array of strings containing a comment.
 *
 * @return
 *   Short one-string version of the comment.
 */
function _locale_import_shorten_comments($comment) {
  $comm = '';
  while (count($comment)) {
    $test = $comm . substr(array_shift($comment), 1) . ', ';
    if (strlen($comm) < 130) {
      $comm = $test;
    }
    else {
      break;
    }
  }
  return trim(substr($comm, 0, -2));
}

/**
 * Parses a string in quotes.
 *
 * @param $string
 *   A string specified with enclosing quotes.
 *
 * @return
 *   The string parsed from inside the quotes.
 */
function _locale_import_parse_quoted($string) {
  if (substr($string, 0, 1) != substr($string, -1, 1)) {
    return FALSE;   // Start and end quotes must be the same
  }
  $quote = substr($string, 0, 1);
  $string = substr($string, 1, -1);
  if ($quote == '"') {        // Double quotes: strip slashes
    return stripcslashes($string);
  }
  elseif ($quote == "'") {  // Simple quote: return as-is
    return $string;
  }
  else {
    return FALSE;             // Unrecognized quote
  }
}

/**
 * Generates a structured array of all translated strings for the language.
 *
 * @param $language
 *   Language object to generate the output for, or NULL if generating
 *   translation template.
 *
 * @return
 *   An array of translated strings that can be used to generate an export.
 */
function _locale_export_get_strings($language = NULL) {
  if (isset($language)) {
    $result = db_query("SELECT s.lid, s.source, s.context, s.location, t.translation, t.plid, t.plural FROM {locales_source} s LEFT JOIN {locales_target} t ON s.lid = t.lid AND t.language = :language ORDER BY t.plid, t.plural", array(':language' => $language->langcode));
  }
  else {
    $result = db_query("SELECT s.lid, s.source, s.context, s.location, t.plid, t.plural FROM {locales_source} s LEFT JOIN {locales_target} t ON s.lid = t.lid ORDER BY t.plid, t.plural");
  }
  $strings = array();
  foreach ($result as $child) {
    $string = array(
      'comment'     => $child->location,
      'source'      => $child->source,
      'context'     => $child->context,
      'translation' => isset($child->translation) ? $child->translation : '',
    );
    if ($child->plid) {
      // Has a parent lid. Since we process in the order of plids,
      // we already have the parent in the array, so we can add the
      // lid to the next plural version to it. This builds a linked
      // list of plurals.
      $string['child'] = TRUE;
      $strings[$child->plid]['plural'] = $child->lid;
    }
    $strings[$child->lid] = $string;
  }
  return $strings;
}

/**
 * Generates the PO(T) file contents for the given strings.
 *
 * @param $language
 *   Language object to generate the output for, or NULL if generating
 *   translation template.
 * @param $strings
 *   Array of strings to export. See _locale_export_get_strings()
 *   on how it should be formatted.
 * @param $header
 *   The header portion to use for the output file. Defaults
 *   are provided for PO and POT files.
 */
function _locale_export_po_generate($language = NULL, $strings = array(), $header = NULL) {
  global $user;

  $locale_plurals = variable_get('locale_translation_plurals', array());

  if (!isset($header)) {
    if (isset($language)) {
      $header = '# ' . $language->name . ' translation of ' . variable_get('site_name', 'Drupal') . "\n";
      $header .= '# Generated by ' . $user->name . ' <' . $user->mail . ">\n";
      $header .= "#\n";
      $header .= "msgid \"\"\n";
      $header .= "msgstr \"\"\n";
      $header .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
      $header .= "\"POT-Creation-Date: " . date("Y-m-d H:iO") . "\\n\"\n";
      $header .= "\"PO-Revision-Date: " . date("Y-m-d H:iO") . "\\n\"\n";
      $header .= "\"Last-Translator: NAME <EMAIL@ADDRESS>\\n\"\n";
      $header .= "\"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\n\"\n";
      $header .= "\"MIME-Version: 1.0\\n\"\n";
      $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
      $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
      if (!empty($locale_plurals[$language->langcode]['formula'])) {
        $header .= "\"Plural-Forms: nplurals=" . $locale_plurals[$language->langcode]['plurals'] . "; plural=" . strtr($locale_plurals[$language->langcode]['formula'], array('$' => '')) . ";\\n\"\n";
      }
    }
    else {
      $header = "# LANGUAGE translation of PROJECT\n";
      $header .= "# Copyright (c) YEAR NAME <EMAIL@ADDRESS>\n";
      $header .= "#\n";
      $header .= "msgid \"\"\n";
      $header .= "msgstr \"\"\n";
      $header .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
      $header .= "\"POT-Creation-Date: " . date("Y-m-d H:iO") . "\\n\"\n";
      $header .= "\"PO-Revision-Date: YYYY-mm-DD HH:MM+ZZZZ\\n\"\n";
      $header .= "\"Last-Translator: NAME <EMAIL@ADDRESS>\\n\"\n";
      $header .= "\"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\n\"\n";
      $header .= "\"MIME-Version: 1.0\\n\"\n";
      $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
      $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
      $header .= "\"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n\"\n";
    }
  }

  $output = $header . "\n";

  foreach ($strings as $lid => $string) {
    // Only process non-children, children are output below their parent.
    if (!isset($string['child'])) {
      if ($string['comment']) {
        $output .= '#: ' . $string['comment'] . "\n";
      }
      if (!empty($string['context'])) {
        $output .= 'msgctxt ' . _locale_export_string($string['context']);
      }
      $output .= 'msgid ' . _locale_export_string($string['source']);
      if (!empty($string['plural'])) {
        $plural = $string['plural'];
        $output .= 'msgid_plural ' . _locale_export_string($strings[$plural]['source']);
        if (isset($language)) {
          $translation = $string['translation'];
          for ($i = 0; $i < $locale_plurals[$language->langcode]['plurals']; $i++) {
            $output .= 'msgstr[' . $i . '] ' . _locale_export_string($translation);
            if ($plural) {
              $translation = _locale_export_remove_plural($strings[$plural]['translation']);
              $plural = isset($strings[$plural]['plural']) ? $strings[$plural]['plural'] : 0;
            }
            else {
              $translation = '';
            }
          }
        }
        else {
          $output .= 'msgstr[0] ""' . "\n";
          $output .= 'msgstr[1] ""' . "\n";
        }
      }
      else {
        $output .= 'msgstr ' . _locale_export_string($string['translation']);
      }
      $output .= "\n";
    }
  }
  return $output;
}

/**
 * Writes a generated PO or POT file to the output.
 *
 * @param $language
 *   Language object to generate the output for, or NULL if generating
 *   translation template.
 * @param $output
 *   The PO(T) file to output as a string. See _locale_export_generate_po()
 *   on how it can be generated.
 */
function _locale_export_po($language = NULL, $output = NULL) {
  // Log the export event.
  if (isset($language)) {
    $filename = $language->langcode . '.po';
    watchdog('locale', 'Exported %locale translation file: %filename.', array('%locale' => $language->name, '%filename' => $filename));
  }
  else {
    $filename = 'drupal.pot';
    watchdog('locale', 'Exported translation file: %filename.', array('%filename' => $filename));
  }
  // Download the file for the client.
  header("Content-Disposition: attachment; filename=$filename");
  header("Content-Type: text/plain; charset=utf-8");
  print $output;
  drupal_exit();
}

/**
 * Prints a string on multiple lines.
 */
function _locale_export_string($str) {
  $stri = addcslashes($str, "\0..\37\\\"");
  $parts = array();

  // Cut text into several lines
  while ($stri != "") {
    $i = strpos($stri, "\\n");
    if ($i === FALSE) {
      $curstr = $stri;
      $stri = "";
    }
    else {
      $curstr = substr($stri, 0, $i + 2);
      $stri = substr($stri, $i + 2);
    }
    $curparts = explode("\n", _locale_export_wrap($curstr, 70));
    $parts = array_merge($parts, $curparts);
  }

  // Multiline string
  if (count($parts) > 1) {
    return "\"\"\n\"" . implode("\"\n\"", $parts) . "\"\n";
  }
  // Single line string
  elseif (count($parts) == 1) {
    return "\"$parts[0]\"\n";
  }
  // No translation
  else {
    return "\"\"\n";
  }
}

/**
 * Wraps text for Portable Object (Template) files.
 */
function _locale_export_wrap($str, $len) {
  $words = explode(' ', $str);
  $return = array();

  $cur = "";
  $nstr = 1;
  while (count($words)) {
    $word = array_shift($words);
    if ($nstr) {
      $cur = $word;
      $nstr = 0;
    }
    elseif (strlen("$cur $word") > $len) {
      $return[] = $cur . " ";
      $cur = $word;
    }
    else {
      $cur = "$cur $word";
    }
  }
  $return[] = $cur;

  return implode("\n", $return);
}

/**
 * Removes plural index information from a string.
 */
function _locale_export_remove_plural($entry) {
  return preg_replace('/(@count)\[[0-9]\]/', '\\1', $entry);
}

/**
 * @} End of "locale-api-import-export"
 */
