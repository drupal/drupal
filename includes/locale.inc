<?php

/**
 * @file
 * Admin-related functions for locale.module.
 */

// ---------------------------------------------------------------------------------
// Language addition functionality (administration only)

/**
 * Helper function to add a language
 */
function _locale_add_language($code, $name, $onlylanguage = TRUE) {
  db_query("INSERT INTO {locales_meta} (locale, name) VALUES ('%s','%s')", $code, $name);
  $result = db_query("SELECT lid FROM {locales_source}");
  while ($string = db_fetch_object($result)) {
    db_query("INSERT INTO {locales_target} (lid, locale) VALUES (%d,'%s')", $string->lid, $code);
  }

  // If only the language was added, and not a PO file import triggered
  // the language addition, we need to inform the user on how to start
  // a translation
  if ($onlylanguage) {
    $message = t('%locale language added. You can now import a translation. See the <a href="%locale-help">help screen</a> for more information.', array('%locale' => theme('placeholder', t($name)), '%locale-help' => url('admin/help/locale')));
  }
  else {
    $message = t('%locale language added.', array('%locale' => theme('placeholder', t($name))));
  }

  drupal_set_message($message);
  watchdog('locale', t('%language language (%locale) added.', array('%language' => theme('placeholder', $name), '%locale' => theme('placeholder', $code))));
}

/**
 * User interface for the language management screen
 */
function _locale_admin_manage_screen() {
  $edit = &$_POST['edit'];
  $languages = locale_supported_languages(TRUE, TRUE);

  $header = array(array('data' => t('Code')), array('data' => t('English name')), array('data' => t('Enabled')), array('data' => t('Default')), array('data' => t('Translated')), array('data' => t('Operations')));

  foreach ($languages['name'] as $key => $lang) {

    $status = db_fetch_object(db_query("SELECT isdefault, enabled FROM {locales_meta} WHERE locale = '%s'", $key));

    if ($key == 'en') {
      $rows[] = array('en', check_plain($lang), form_checkbox('', 'enabled][en', 1, $status->enabled), form_radio('', 'sitedefault', $key, $status->isdefault), message_na(), '');
    }
    else {
      $original = db_fetch_object(db_query("SELECT COUNT(*) AS strings FROM {locales_source}"));
      $translation = db_fetch_object(db_query("SELECT COUNT(*) AS translation FROM {locales_target} WHERE locale = '%s' AND translation != ''", $key));

      $ratio = ($original->strings > 0 && $translation->translation > 0) ? round(($translation->translation/$original->strings)*100., 2) : 0;

      $rows[] = array(check_plain($key), ($key != 'en' ? form_textfield('', 'name]['. $key, $lang, 15, 64) : $lang), form_checkbox('', 'enabled]['. $key, 1, $status->enabled), form_radio('', 'sitedefault', $key, $status->isdefault), "$translation->translation/$original->strings ($ratio%)", ($key != 'en' ? l(t('delete'), 'admin/locale/language/delete/'. urlencode($key)) : ''));
    }
  }

  return form(theme('table', $header, $rows) . form_submit(t('Save configuration')), 'POST', url('admin/locale'));
}

/**
 * User interface for the language addition screen
 */
function _locale_admin_manage_add_screen() {

  $isocodes = _locale_prepare_iso_list();

  $output = '<h2>'. t('From language list') .'</h2>';
  $form = form_select(t('Language name'), 'langcode', key($isocodes), $isocodes, t('Select your language here, or add it below, if you are unable to find it.'));
  $form .= form_submit(t('Add language'));
  $output .= form($form);

  $edit = &$_POST['edit'];
  $output .= '<h2>'. t('Custom language') .'</h2>';
  $form = form_textfield(t('Language code'), 'langcode', $edit['langcode'], 70, 12, t("Commonly this is an <a href=\"%iso-codes\">ISO 639 language code</a> with an optional country code for regional variants. Examples include 'en', 'en-US' and 'zh-cn'.", array('%iso-codes' => 'http://www.w3.org/WAI/ER/IG/ert/iso639.htm')));
  $form .= form_textfield(t('Language name in English'), 'langname', $edit['langname'], 70, 64, t('Name of the language. Will be available for translation in all languages.'));
  $form .= form_submit(t('Add language'));
  $output .= form($form);

  return $output;
}


/**
 * User interface for the translation import screen
 */
function _locale_admin_import_screen() {
  $languages = locale_supported_languages(FALSE, TRUE);
  $languages = array_map("t", $languages['name']);
  unset($languages['en']);

  if (!count($languages)) {
    $languages = _locale_prepare_iso_list();
  }
  else {
    $languages = array(
      t('Already added languages') => $languages,
      t('Languages not yet added') => _locale_prepare_iso_list()
    );
  }

  $form = form_file(t('Language file'), 'file', 50, t('A gettext Portable Object (.po) file.'));
  $form .= form_select(t('Import into'), 'langcode', '', $languages, t('Choose the language you want to add strings into. If you choose a language which is not yet set up, then it will be added.'));
  $form .= form_radios(t('Mode'), 'mode', 'overwrite', array('overwrite' => t('Strings in the uploaded file replace existing ones, new ones are added'), 'keep' => t('Existing strings are kept, only new strings are added')));
  $form .= form_submit(t('Import'));
  $output = form($form, 'POST', url('admin/locale/language/import'), array('enctype' => 'multipart/form-data'));
  return $output;
}

/**
 * Parses Gettext Portable Object file information and inserts into database
 *
 * @param $file Object contains properties of local file to be imported
 * @param $edit Language code
 * @param $mode should existing translations be replaced?
 */
function _locale_import_po($file, $lang, $mode) {
  // If not in 'safe mode', increase the maximum execution time:
  if (!ini_get('safe_mode')) {
    set_time_limit(240);
  }

  // Check if we have the language already in the database
  if (!db_fetch_object(db_query("SELECT locale FROM {locales_meta} WHERE locale = '%s'", $lang))) {
    drupal_set_message(t('Unsupported language selected for import.'), 'error');
    return FALSE;
  }

  // Check if we can get the strings from the file
  if (!($strings = _locale_import_read_po($file))) {
    drupal_set_message(t('Translation file %filename broken: Could not be read.', array('%filename' => theme('placeholder', $file->filename))), 'error');
    return FALSE;
  }

  // Strip out header from the string pairs
  $header = $strings[""]["msgstr"];
  unset($strings[""]);

  // Get information from the header into the database
  if ($header) {
    $hdr = _locale_import_parse_header($header);

    // Get the plural formula
    if ($hdr["Plural-Forms"] && $p = _locale_import_parse_plural_forms($hdr["Plural-Forms"], $file->filename)) {
      list($nplurals, $plural) = $p;
      db_query("UPDATE {locales_meta} SET plurals = '%d', formula = '%s' WHERE locale = '%s'", $nplurals, $plural, $lang);
    }
    else {
      db_query("UPDATE {locales_meta} SET plurals = '%d', formula = '%s' WHERE locale = '%s'", 0, '', $lang);
    }
  }
  else {
    drupal_set_message(t('Translation file %filename broken: No header.', array('%filename' => theme('placeholder', $file->filename))), 'error');
    return FALSE;
  }

  $additions = 0;
  $updates = 0;
  foreach ($strings as $value) {
    $comments = _locale_import_shorten_comments($value['#']);

    // Handle a translation for some plural string
    if (strpos($value['msgid'], "\0")) {
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
        $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s'", $english[$key]));
        if ($loc->lid) { // a string exists
          $lid = $loc->lid;
          // update location field
          db_query("UPDATE {locales_source} SET location = '%s' WHERE lid = %d", $comments, $lid);
          $trans2 = db_fetch_object(db_query("SELECT lid, translation, plid, plural FROM {locales_target} WHERE lid = %d AND locale = '%s'", $lid, $lang));
          if (!$trans2->lid) { // no translation in current language
            db_query("INSERT INTO {locales_target} (lid, locale, translation, plid, plural) VALUES (%d, '%s', '%s', %d, %d)", $lid, $lang, $trans, $plid, $key);
            $additions++;
          } // translation exists
          else if ($mode == 'overwrite' || $trans2->translation == '') {
            db_query("UPDATE {locales_target} SET translation = '%s', plid = %d, plural = %d WHERE locale = '%s' AND lid = %d", $trans, $plid, $key, $lang, $lid);
            if ($trans2->translation == '') {
              $additions++;
            }
            else {
              $updates++;
            }
          }
        }
        else { // no string
          db_query("INSERT INTO {locales_source} (location, source) VALUES ('%s', '%s')", $comments, $english[$key]);
          $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s'", $english[$key]));
          $lid = $loc->lid;
          db_query("INSERT INTO {locales_target} (lid, locale, translation, plid, plural) VALUES (%d, '%s', '%s', %d, %d)", $lid, $lang, $trans, $plid, $key);
          if ($trans != '') {
            $additions++;
          }
        }
        $plid = $lid;
      }
    }

    // A simple translation
    else {
      $english = $value['msgid'];
      $translation = $value['msgstr'];
      $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s'", $english));
      if ($loc->lid) { // a string exists
        $lid = $loc->lid;
        // update location field
        db_query("UPDATE {locales_source} SET location = '%s' WHERE source = '%s'", $comments, $english);
        $trans = db_fetch_object(db_query("SELECT lid, translation FROM {locales_target} WHERE lid = %d AND locale = '%s'", $lid, $lang));
        if (!$trans->lid) { // no translation in current language
          db_query("INSERT INTO {locales_target} (lid, locale, translation) VALUES (%d, '%s', '%s')", $lid, $lang, $translation);
          $additions++;
        } // translation exists
        else if ($mode == 'overwrite') { //overwrite in any case
          db_query("UPDATE {locales_target} SET translation = '%s' WHERE locale = '%s' AND lid = %d", $translation, $lang, $lid);
          if ($trans->translation == '') {
            $additions++;
          }
          else {
            $updates++;
          }
        } // overwrite if empty string
        else if ($trans->translation == '') {
          db_query("UPDATE {locales_target} SET translation = '%s' WHERE locale = '%s' AND lid = %d", $translation, $lang, $lid);
          $additions++;
        }
      }
      else { // no string
        db_query("INSERT INTO {locales_source} (location, source) VALUES ('%s', '%s')", $comments, $english);
        $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s'", $english));
        $lid = $loc->lid;
        db_query("INSERT INTO {locales_target} (lid, locale, translation) VALUES (%d, '%s', '%s')", $lid, $lang, $translation);
        if ($translation != '') {
          $additions++;
        }
      }
    }
  }

  // Successful import
  // rebuild locale cache
  cache_clear_all("locale:$lang");

  // rebuild the menu, strings may have changed
  menu_rebuild();

  drupal_set_message(t('Translation successfully imported. %number translated strings added to language, %update strings updated.', array('%number' => $additions, '%update' => $updates)));
  watchdog('locale', t('Imported %file into %locale: %number new strings added and %update updated.', array('%file' => theme('placeholder', $file->filename), '%locale' => theme('placeholder', $lang), '%number' => $additions, '%update' => $updates)));
  return TRUE;
}

/**
 * Parses Gettext Portable Object file into an array
 *
 * @param $file Object with properties of local file to parse
 * @author Jacobo Tarrio
 */
function _locale_import_read_po($file) {

  $message = theme('placeholder', $file->filename);
  $fd = fopen($file->filepath, "rb");
  if (!$fd) {
    drupal_set_message(t('Translation import failed: file %filename cannot be read.', array('%filename' => $message)), 'error');
    return FALSE;
  }
  $info = fstat($fd);
  $len = $info["size"];
  $po = fread($fd, $len);
  fclose($fd);

  $context = "COMMENT"; // Parser context: COMMENT, MSGID, MSGID_PLURAL, MSGSTR and MSGSTR_ARR
  $current = array();   // Current entry being read
  $strings = array();   // List of entries read
  $plural = 0;          // Current plural form

  $po = strtr($po, array("\\\n" => ""));
  $lines = split("\n", $po);
  $lineno = 0;

  foreach ($lines as $line) {
    $lineno++;
    $line = trim($line);

    if (!strncmp("#", $line, 1)) { // A comment
      if ($context == "COMMENT") { // Already in comment context: add
        $current["#"][] = substr($line, 1);
      }
      elseif (($context == "MSGSTR") || ($context == "MSGSTR_ARR")) { // End current entry, start a new one
        $strings[$current["msgid"]] = $current;
        $current = array();
        $current["#"][] = substr($line, 1);
        $context = "COMMENT";
      }
      else { // Parse error
        drupal_set_message(t("Translation file %filename broken: expected 'msgstr' in line %line.", array('%filename' => $message, '%line' => $lineno)), 'error');
        return FALSE;
      }
    }
    elseif (!strncmp("msgid_plural", $line, 12)) {
      if ($context != "MSGID") { // Must be plural form for current entry
        drupal_set_message(t("Translation file %filename broken: unexpected 'msgid_plural' in line %line.", array('%filename' => $message, '%line' => $lineno)), 'error');
        return FALSE;
      }
      $line = trim(substr($line, 12));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === false) {
        drupal_set_message(t('Translation file %filename broken: syntax error in line %line.', array('%filename' => $message, '%line' => $lineno)), 'error');
        return FALSE;
      }
      $current["msgid"] = $current["msgid"] ."\0". $quoted;
      $context = "MSGID_PLURAL";
    }
    elseif (!strncmp("msgid", $line, 5)) {
      if ($context == "MSGSTR") {   // End current entry, start a new one
        $strings[$current["msgid"]] = $current;
        $current = array();
      }
      elseif ($context == "MSGID") { // Already in this context? Parse error
        drupal_set_message(t("Translation file %filename broken: unexpected 'msgid' in line %line.", array('%filename' => $message, '%line' => $lineno)), 'error');
        return FALSE;
      }
      $line = trim(substr($line, 5));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === false) {
        drupal_set_message(t('Translation file %filename broken: syntax error in line %line.', array('%filename' => $message, '%line' => $lineno)), 'error');
        return FALSE;
      }
      $current["msgid"] = $quoted;
      $context = "MSGID";
    }
    elseif (!strncmp("msgstr[", $line, 7)) {
      if (($context != "MSGID") && ($context != "MSGID_PLURAL") && ($context != "MSGSTR_ARR")) { // Must come after msgid, msgid_plural, or msgstr[]
        drupal_set_message(t("Translation file %filename broken: unexpected 'msgstr[]' in line %line.", array('%filename' => $message, '%line' => $lineno)), 'error');
        return FALSE;
      }
      if (strpos($line, "]") === false) {
        drupal_set_message(t('Translation file %filename broken: syntax error in line %line.', array('%filename' => $message, '%line' => $lineno)), 'error');
        return FALSE;
      }
      $frombracket = strstr($line, "[");
      $plural = substr($frombracket, 1, strpos($frombracket, "]") - 1);
      $line = trim(strstr($line, " "));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === false) {
        drupal_set_message(t('Translation file %filename broken: syntax error in line %line.', array('%filename' => $message, '%line' => $lineno)), 'error');
        return FALSE;
      }
      $current["msgstr"][$plural] = $quoted;
      $context = "MSGSTR_ARR";
    }
    elseif (!strncmp("msgstr", $line, 6)) {
      if ($context != "MSGID") {   // Should come just after a msgid block
        drupal_set_message(t("Translation file %filename broken: unexpected 'msgstr' in line %line.", array('%filename' => $message, '%line' => $lineno)), 'error');
        return FALSE;
      }
      $line = trim(substr($line, 6));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === false) {
        drupal_set_message(t('Translation file %filename broken: syntax error in line %line.', array('%filename' => $message, '%line' => $lineno)), 'error');
        return FALSE;
      }
      $current["msgstr"] = $quoted;
      $context = "MSGSTR";
    }
    elseif ($line != "") {
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === false) {
        drupal_set_message(t('Translation file %filename broken: syntax error in line %line.', array('%filename' => $message, '%line' => $lineno)), 'error');
        return FALSE;
      }
      if (($context == "MSGID") || ($context == "MSGID_PLURAL")) {
        $current["msgid"] .= $quoted;
      }
      elseif ($context == "MSGSTR") {
        $current["msgstr"] .= $quoted;
      }
      elseif ($context == "MSGSTR_ARR") {
        $current["msgstr"][$plural] .= $quoted;
      }
      else {
        drupal_set_message(t('Translation file %filename broken: unexpected string in line %line.', array('%filename' => $message, '%line' => $lineno)), 'error');
        return FALSE;
      }
    }
  }

  // End of PO file, flush last entry
  if (($context == "MSGSTR") || ($context == "MSGSTR_ARR")) {
    $strings[$current["msgid"]] = $current;
  }
  elseif ($context != "COMMENT") {
    drupal_set_message(t('Translation file %filename broken: unexpected end of file at line %line.', array('%filename' => $message, '%line' => $lineno)), 'error');
    return FALSE;
  }

  return $strings;
}

/**
 * Parses a Gettext Portable Object file header
 *
 * @param $header A string containing the complete header
 * @return An associative array of key-value pairs
 * @author Jacobo Tarrio
 */
function _locale_import_parse_header($header) {
  $hdr = array();

  $lines = explode("\n", $header);
  foreach ($lines as $line) {
    $line = trim($line);
    if ($line) {
      list($tag, $contents) = explode(":", $line, 2);
      $hdr[trim($tag)] = trim($contents);
    }
  }

  return $hdr;
}

/**
 * Parses a Plural-Forms entry from a Gettext Portable Object file header
 *
 * @param $pluralforms A string containing the Plural-Forms entry
 * @param $filename A string containing the filename
 * @return An array containing the number of plurals and a
 *         formula in PHP for computing the plural form
 * @author Jacobo Tarrio
 */
function _locale_import_parse_plural_forms($pluralforms, $filename) {
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

  if ($plural) {
    return array($nplurals, $plural);
  }
  else {
    drupal_set_message(t("Translation file %filename broken: plural formula couldn't get parsed.", array('%filename' => theme('placeholder', $filename))), 'error');
    return FALSE;
  }
}

/**
 * Parses and sanitizes an arithmetic formula into a PHP expression
 *
 * While parsing, we ensure, that the operators have the right
 * precedence and associativity.
 *
 * @param $string A string containing the arithmetic formula
 * @return The PHP version of the formula
 * @author Jacobo Tarrio
 */
function _locale_import_parse_arithmetic($string) {
  // Operator precedence table
  $prec = array("(" => -1, ")" => -1, "?" => 1, ":" => 1, "||" => 3, "&&" => 4, "==" => 5, "!=" => 5, "<" => 6, ">" => 6, "<=" => 6, ">=" => 6, "+" => 7, "-" => 7, "*" => 8, "/" => 8, "%" => 8);
  // Right associativity
  $rasc = array("?" => 1, ":" => 1);

  $tokens = _locale_import_tokenize_formula($string);

  // Parse by converting into infix notation then back into postfix
  $opstk = array();
  $elstk = array();

  foreach ($tokens as $token) {
    $ctok = $token;

    // Numbers and the $n variable are simply pushed into $elarr
    if (is_numeric($token)) {
      $elstk[] = $ctok;
    }
    elseif ($ctok == "n") {
      $elstk[] = '$n';
    }
    elseif ($ctok == "(") {
      $opstk[] = $ctok;
    }
    elseif ($ctok == ")") {
      $topop = array_pop($opstk);
      while (($topop != NULL) && ($topop != "(")) {
        $elstk[] = $topop;
        $topop = array_pop($opstk);
      }
    }
    elseif ($prec[$ctok]) {
      // If it's an operator, then pop from $oparr into $elarr until the
      // precedence in $oparr is less than current, then push into $oparr
      $topop = array_pop($opstk);
      while (($topop != NULL) && ($prec[$topop] >= $prec[$ctok]) && !(($prec[$topop] == $prec[$ctok]) && $rasc[$topop] && $rasc[$ctok])) {
        $elstk[] = $topop;
        $topop = array_pop($opstk);
      }
      if ($topop) {
        $opstk[] = $topop;   // Return element to top
      }
      $opstk[] = $ctok;      // Parentheses are not needed
    }
    else {
      return false;
    }
  }

  // Flush operator stack
  $topop = array_pop($opstk);
  while ($topop != NULL) {
    $elstk[] = $topop;
    $topop = array_pop($opstk);
  }

  // Now extract formula from stack
  $prevsize = count($elstk) + 1;
  while (count($elstk) < $prevsize) {
    $prevsize = count($elstk);
    for ($i = 2; $i < count($elstk); $i++) {
      $op = $elstk[$i];
      if ($prec[$op]) {
        $f = "";
        if ($op == ":") {
          $f = $elstk[$i - 2] ."):". $elstk[$i - 1] .")";
        }
        elseif ($op == "?") {
          $f = "(". $elstk[$i - 2] ."?(". $elstk[$i - 1];
        }
        else {
          $f = "(". $elstk[$i - 2] . $op . $elstk[$i - 1] .")";
        }
        array_splice($elstk, $i - 2, 3, $f);
        break;
      }
    }
  }

  // If only one element is left, the number of operators is appropriate
  if (count($elstk) == 1) {
    return $elstk[0];
  }
  else {
    return FALSE;
  }
}

/**
 * Backward compatible implementation of token_get_all() for formula parsing
 *
 * @param $string A string containing the arithmetic formula
 * @return The PHP version of the formula
 * @author Gerhard Killesreiter
 */
function _locale_import_tokenize_formula($formula) {
  $formula = str_replace(" ", "", $formula);
  $tokens = array();
  for ($i = 0; $i < strlen($formula); $i++) {
    if (is_numeric($formula{$i})) {
      $num = $formula{$i};
      $j = $i + 1;
      while($j < strlen($formula) && is_numeric($formula{$j})) {
        $num .= $formula{$j};
        $j++;
      }
      $i = $j - 1;
      $tokens[] = $num;
    }
    elseif ($pos = strpos(" =<>!&|", $formula{$i})) { // We won't have a space
      $next = $formula{($i+1)};
      switch ($pos) {
        case 1:
        case 2:
        case 3:
        case 4:
          if ($next == '=') {
            $tokens[] = $formula{$i} .'=';
            $i++;
          }
          else {
            $tokens[] = $formula{$i};
          }
          break;
        case 5:
          if ($next == '&') {
            $tokens[] = '&&';
            $i++;
          }
          else {
            $tokens[] = $formula{$i};
          }
          break;
        case 6:
          if ($next == '|') {
            $tokens[] = '||';
            $i++;
          }
          else {
            $tokens[] = $formula{$i};
          }
          break;
      }
    }
    else {
      $tokens[] = $formula{$i};
    }
  }
  return $tokens;
}

/**
 * Modify a string to contain proper count indices
 *
 * This is a callback function used via array_map()
 *
 * @param $entry An array element
 * @param $key Index of the array element
 */
function _locale_import_append_plural($entry, $key) {
  // No modifications for 0, 1
  if ($key == 0 || $key == 1) {
    return $entry;
  }

  // First remove any possibly false indices, then add new ones
  $entry = preg_replace('/(%count)\[[0-9]\]/', '\\1', $entry);
  return preg_replace('/(%count)/', "\\1[$key]", $entry);
}

/**
 * Generate a short, one string version of the passed comment array
 *
 * @param $comment An array of strings containing a comment
 * @return Short one string version of the comment
 */
function _locale_import_shorten_comments($comment) {
  $comm = '';
  while (count($comment)) {
    $test = $comm . substr(array_shift($comment), 1) .', ';
    if (strlen($comm) < 130) {
      $comm = $test;
    }
    else {
      break;
    }
  }
  return substr($comm, 0, -2);
}

/**
 * Parses a string in quotes
 *
 * @param $string A string specified with enclosing quotes
 * @return The string parsed from inside the quotes
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
 * User interface for the translation export screen
 */
function _locale_admin_export_screen() {
  $languages = locale_supported_languages(FALSE, TRUE);
  $languages = array_map("t", $languages['name']);
  unset($languages['en']);
  $output = '';

  // Offer language specific export if any language is set up
  if (count($languages)) {
    $output .= '<h2>'. t('Export translation') .'</h2>';
    $form = form_select(t('Language name'), 'langcode', '', $languages, t('Select the language you would like to export in gettext Portable Object (.po) format.'));
    $form .= form_submit(t('Export'));
    $output .= form($form);
  }

  // Complete template export of the strings
  $output .= '<h2>'. t('Export template') .'</h2>';
  $form = t('<p>Generate a gettext Portable Object Template (.pot) file with all the interface strings from the Drupal locale database.</p>');
  $form .= form_submit(t('Export'));
  $output .= form($form);

  return $output;
}

/**
 * Exports a Portable Object (Template) file for a language
 *
 * @param $language Selects a language to generate the output for
 */
function _locale_export_po($language) {
  global $user;

  // Get language specific strings, or all strings
  if ($language) {
    $meta = db_fetch_object(db_query("SELECT * FROM {locales_meta} WHERE locale = '%s'", $language));
    $result = db_query("SELECT s.lid, s.source, s.location, t.translation, t.plid, t.plural FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid WHERE t.locale = '%s' ORDER BY t.plid, t.plural", $language);
  }
  else {
    $result = db_query("SELECT s.lid, s.source, s.location, t.plid, t.plural FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid ORDER BY t.plid, t.plural");
  }

  // Build array out of the database results
  $parent = array();
  while ($child = db_fetch_object($result)) {
    if ($child->source != '') {
      $parent[$child->lid]['comment'] = $child->location;
      $parent[$child->lid]['msgid'] = $child->source;
      if ($child->plid) {
        $parent[$child->lid][$child->plid]['plural'] = $child->lid;
        $parent[$child->lid][$child->plid]['translation'] = $child->translation;
        $parent[$child->lid][$child->plid]['msgid'] = $child->source;
      }
      else {
        $parent[$child->lid]['translation'] = $child->translation;
      }
    }
  }

  // Generating Portable Object file for a language
  if ($language) {
    $filename = $language .'.po';
    $header .= "# $meta->name translation of ". variable_get('site_name', 'Drupal') ."\n";
    $header .= '# Copyright (c) '. date('Y') .' '. $user->name .' <'. $user->mail .">\n";
    $header .= "#\n";
    $header .= "msgid \"\"\n";
    $header .= "msgstr \"\"\n";
    $header .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
    $header .= "\"POT-Creation-Date: ". date("Y-m-d H:iO") ."\\n\"\n";
    $header .= "\"PO-Revision-Date: ". date("Y-m-d H:iO") ."\\n\"\n";
    $header .= "\"Last-Translator: ". $user->name .' <'. $user->mail .">\\n\"\n";
    $header .= "\"Language-Team: ". $meta->name .' <'. $user->mail .">\\n\"\n";
    $header .= "\"MIME-Version: 1.0\\n\"\n";
    $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
    $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
    if ($meta->formula && $meta->plurals) {
      $header .= "\"Plural-Forms: nplurals=". $meta->plurals ."; plural=". strtr($meta->formula, '$', '') .";\\n\"\n";
    }
    $header .= "\n";
    watchdog('locale', t('Exported %locale translation file: %filename.', array('%locale' => theme('placeholder', $meta->name), '%filename' => theme('placeholder', $filename))));
  }

  // Generating Portable Object Template
  else {
    $filename = variable_get('site_name', 'drupal') .'.pot';
    $header .= "# LANGUAGE translation of PROJECT\n";
    $header .= "# Copyright (c) YEAR NAME <EMAIL@ADDRESS>\n";
    $header .= "#\n";
    $header .= "msgid \"\"\n";
    $header .= "msgstr \"\"\n";
    $header .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
    $header .= "\"POT-Creation-Date: ". date("Y-m-d H:iO") ."\\n\"\n";
    $header .= "\"PO-Revision-Date: YYYY-mm-DD HH:MM+ZZZZ\\n\"\n";
    $header .= "\"Last-Translator: NAME <EMAIL@ADDRESS>\\n\"\n";
    $header .= "\"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\n\"\n";
    $header .= "\"MIME-Version: 1.0\\n\"\n";
    $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
    $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
    $header .= "\"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n\"\n";
    $header .= "\n";
    watchdog('locale', t('Exported translation file: %filename.', array('%filename' => theme('placeholder', $filename))));
  }

  // Start download process
  header("Content-Disposition: attachment; filename=$filename");
  header("Content-Type: text/plain; charset=utf-8");

  print $header;

  foreach ($parent as $lid => $message) {
    if (!isset($done[$lid])) {
      if ($message['comment']) {
        print '#: '. $message['comment'] ."\n";
      }
      print 'msgid '. _locale_export_print($message['msgid']);
      if (isset($message[1]['plural'])) {
        print 'msgid_plural '. _locale_export_print($message[1]['msgid']);
        if ($language) {
          for ($i = 0; $i < $meta->plurals; $i++) {
            print 'msgstr['. $i .'] '. _locale_export_print(_locale_export_remove_plural($message[${i}]['translation']));
            $done[$message[${i}]['plural']] = 1;
          }
        }
        else {
          print 'msgstr[0] ""'. "\n";
          print 'msgstr[1] ""'. "\n";
          $done[$message[0]['plural']] = 1;
          $done[$message[1]['plural']] = 1;
        }
      }
      else {
        if ($language) {
          print 'msgstr '. _locale_export_print($message['translation']);
        }
        else {
          print 'msgstr ""'. "\n";
        }
      }
      print "\n";
    }
  }
  die();
}

/**
 * Print out a string on multiple lines
 */
function _locale_export_print($str) {
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

  if (count($parts) > 1) {
    return "\"\"\n\"". implode("\"\n\"", $parts) ."\"\n";
  }
  else {
    return "\"$parts[0]\"\n";
  }
}

/**
 * Custom word wrapping for Portable Object (Template) files.
 *
 * @author Jacobo Tarrio
 */
function _locale_export_wrap($str, $len) {
  $words = split(" ", $str);
  $ret = array();

  $cur = "";
  $nstr = 1;
  while (count($words)) {
    $word = array_shift($words);
    if ($nstr) {
      $cur = $word;
      $nstr = 0;
    }
    elseif (strlen("$cur $word") > $len) {
      $ret[] = $cur . " ";
      $cur = $word;
    }
    else {
      $cur = "$cur $word";
    }
  }
  $ret[] = $cur;

  return implode("\n", $ret);
}

/**
 * Removes plural index information from a string
 */
function _locale_export_remove_plural($entry) {
  return preg_replace('/(%count)\[[0-9]\]/', '\\1', $entry);
}

function _locale_string_delete($lid) {
  db_query('DELETE FROM {locales_source} WHERE lid = %d', $lid);
  db_query('DELETE FROM {locales_target} WHERE lid = %d', $lid);
  locale_refresh_cache();
  drupal_set_message(t('Deleted string'));
}

/**
 * Action handler for string editing
 *
 * Saves all translations of one string submitted from a form
 */
function _locale_string_save($lid) {

  $edit =& $_POST['edit'];
  foreach ($edit as $key => $value) {
    $trans = db_fetch_object(db_query("SELECT translation FROM {locales_target} WHERE lid = %d AND locale = '%s'", $lid, $key));
    if (isset($trans->translation)) {
      db_query("UPDATE {locales_target} SET translation = '%s' WHERE lid = %d AND locale = '%s'", $value, $lid, $key);
    }
    else {
      db_query("INSERT INTO {locales_target}  (lid, translation, locale) VALUES (%d, '%s', '%s')", $lid, $value, $key);
    }
  }
  // refresh the locale cache
  locale_refresh_cache();
  // rebuild the menu, strings may have changed
  menu_rebuild();
  // delete form data so it will remember where it came from
  $edit = '';

  drupal_set_message(t('Saved string'));
}

/**
 * User interface for string editing
 */
function _locale_string_edit($lid) {
  $languages = locale_supported_languages(FALSE, TRUE);
  unset($languages['name']['en']);

  $result = db_query('SELECT DISTINCT s.source, t.translation, t.locale FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid WHERE s.lid = %d', $lid);
  $form = '';
  while ($translation = db_fetch_object($result)) {
    $orig = $translation->source;
    $form .= (strlen($orig) > 40) ? form_textarea($languages['name'][$translation->locale], $translation->locale, $translation->translation, 70, 15) : form_textfield($languages['name'][$translation->locale], $translation->locale, $translation->translation, 50, 128);
    unset($languages['name'][$translation->locale]);
  }
  foreach ($languages['name'] as $key => $lang) {
    $form .= (strlen($orig) > 40) ? form_textarea($lang, $key, '', 70, 15) : form_textfield($lang, $key, '', 50, 128);
  }
  $form = form_item(t('Original text'), wordwrap(check_plain($orig, 0))) . $form;

  $form .= form_submit(t('Save translations'));

  return form($form);
}

/**
 * List languages in search result table
 */
function _locale_string_language_list($translation) {
  $languages = locale_supported_languages(FALSE, TRUE);
  unset($languages['name']['en']);
  $output = '';
  foreach ($languages['name'] as $key => $value) {
    if (isset($translation[$key])) {
      $output .= ($translation[$key] != '') ? $key .' ' : "<strike>$key</strike> ";
    }
  }

  return $output;
}

/**
 * Build object out of search criteria specified in request variables
 */
function _locale_string_seek_query() {
  static $query = NULL;

  if (is_null($query)) {
    $fields = array('string', 'language', 'searchin');
    $query = new StdClass;
    if (is_array($_REQUEST['edit'])) {
      foreach ($_REQUEST['edit'] as $key => $value) {
        if (!empty($value) && in_array($key, $fields)) {
          $query->$key = $value;
        }
      }
    }
    else {
      foreach ($_REQUEST as $key => $value) {
        if (!empty($value) && in_array($key, $fields)) {
          $query->$key = strpos(',', $value) ? explode(',', $value) : $value;
        }
      }
    }
  }
  return $query;
}

/**
 * Perform a string search and display results in a table
 */
function _locale_string_seek() {
  // We have at least one criterion to match
  if ($query = _locale_string_seek_query()) {
    $join = "SELECT s.source, s.location, s.lid, t.translation, t.locale FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid ";

    // Compute LIKE section
    switch ($query->searchin) {
      case 'translated':
        $where = "WHERE (t.translation LIKE '%". db_escape_string($query->string) ."%' AND t.translation != '')";
        $orderby = "ORDER BY t.translation";
        break;
      case 'untranslated':
        $where = "WHERE (s.source LIKE '%". db_escape_string($query->string) ."%' AND t.translation = '')";
        $orderby = "ORDER BY s.source";
        break;
      case 'all' :
      default:
        $where = "WHERE (s.source LIKE '%". db_escape_string($query->string) ."%' OR t.translation LIKE '%". db_escape_string($query->string) ."%')";
        $orderby = '';
        break;
    }

    switch ($query->language) {
      // Force search in source strings
      case "en":
        $sql = $join ." WHERE s.source LIKE '%". db_escape_string($query->string) ."%' ORDER BY s.source";
        break;
      // Search in all languages
      case "all":
        $sql = "$join $where $orderby";
        break;
      // Some different language
      default:
        $sql = "$join $where AND t.locale = '". db_escape_string($query->language) ."' $orderby";
    }

    $result = pager_query($sql, 50);

    $header = array(t('String'), t('Locales'), array('data' => t('Operations'), 'colspan' => '2'));
    $arr = array();
    while ($locale = db_fetch_object($result)) {
      $arr[$locale->lid]['locales'][$locale->locale] = $locale->translation;
      $arr[$locale->lid]['location'] = $locale->location;
      $arr[$locale->lid]['source'] = $locale->source;
    }
    foreach ($arr as $lid => $value) {
      $source = htmlspecialchars($value['source']);
      $rows[] = array(array('data' => (strlen($source) > 150 ? substr($source, 0, 150) .'...' : $source) .'<br /><small>'. $value['location'] .'</small>'), array('data' => _locale_string_language_list($value['locales']), 'align' => 'center'), array('data' => l(t('edit'), "admin/locale/string/edit/$lid"), 'nowrap' => 'nowrap'), array('data' => l(t('delete'), "admin/locale/string/delete/$lid"), 'nowrap' => 'nowrap'));
    }

    $request = array();
    if (count($query)) {
      foreach ($query as $key => $value) {
        $request[$key] = (is_array($value)) ? implode(',', $value) : $value;
      }
    }

    if ($pager = theme('pager', NULL, 50, 0, $request)) {
      $rows[] = array(array('data' => "$pager", 'colspan' => '5'));
    }

    $output .= theme('table', $header, $rows);

  }

  return $output;
}

/**
 * User interface for the string search screen
 */
function _locale_string_seek_form() {

  // Get *all* languages set up
  $languages = locale_supported_languages(FALSE, TRUE);
  asort($languages['name']); unset($languages['name']['en']);
  $languages['name'] = array_map('check_plain', $languages['name']);

  // Present edit form preserving previous user settings
  $query = _locale_string_seek_query();
  $form .= form_textfield(t('Strings to search for'), 'string', $query->string, 30, 30, t('Leave blank to show all strings. The search is case sensitive.'));
  $form .= form_radios(t('Language'), 'language', ($query->language ? $query->language : 'all'), array_merge(array('all' => t('All languages'), 'en' => t('English (provided by Drupal)')), $languages['name']));
  $form .= form_radios(t('Search in'), 'searchin', ($query->searchin ? $query->searchin : 'all'), array('all' => t('All strings in that language'), 'translated' => t('Only translated strings'), 'untranslated' => t('Only untranslated strings')));

  $form .= form_submit(t('Search'));
  $output = form(form_group(t('Search strings'), $form), 'POST', url('admin/locale/string/search'));

  return $output;
}

// ---------------------------------------------------------------------------------
// List of some of the most common languages (administration only)

/**
 * Prepares the language code list for a select form item with only the unsupported ones
 */
function _locale_prepare_iso_list() {
  $languages = locale_supported_languages(FALSE, TRUE);
  $isocodes = _locale_get_iso639_list();
  foreach ($isocodes as $key => $value) {
    if (isset($languages['name'][$key])) {
      unset($isocodes[$key]);
      continue;
    }
    if (count($value) == 2) {
      $tname = t($value[0]);
      $isocodes[$key] = ($tname == $value[1]) ? $tname : "$tname ($value[1])";
    }
    else {
      $isocodes[$key] = t($value[0]);
    }
  }
  asort($isocodes);
  return $isocodes;
}

/**
 * Some of the common languages with their English and native names
 *
 * Based on ISO 639 and http://people.w3.org/rishida/names/languages.html
 */
function _locale_get_iso639_list() {
  return array(
    "aa" => array("Afar"),
    "ab" => array("Abkhazian", "аҧсуа бызшәа"),
    "ae" => array("Avestan"),
    "af" => array("Afrikaans"),
    "ak" => array("Akan"),
    "am" => array("Amharic", "አማርኛ"),
    "ar" => array("Arabic", "العربية"),
    "as" => array("Assamese"),
    "av" => array("Avar"),
    "ay" => array("Aymara"),
    "az" => array("Azerbaijani", "azərbaycan"),
    "ba" => array("Bashkir"),
    "be" => array("Belarusian", "Беларуская"),
    "bg" => array("Bulgarian", "Български"),
    "bh" => array("Bihari"),
    "bi" => array("Bislama"),
    "bm" => array("Bambara", "Bamanankan"),
    "bn" => array("Bengali"),
    "bo" => array("Tibetan"),
    "br" => array("Breton"),
    "bs" => array("Bosnian", "Bosanski"),
    "ca" => array("Catalan", "Català"),
    "ce" => array("Chechen"),
    "ch" => array("Chamorro"),
    "co" => array("Corsican"),
    "cr" => array("Cree"),
    "cs" => array("Czech", "Čeština"),
    "cu" => array("Old Slavonic"),
    "cv" => array("Welsh", "Cymraeg"),
    "cy" => array("Welch"),
    "da" => array("Danish"),
    "de" => array("German", "Deutsch"),
    "dv" => array("Maldivian"),
    "dz" => array("Bhutani"),
    "ee" => array("Ewe", "Ɛʋɛ"),
    "el" => array("Greek", "Ελληνικά"),
    "en" => array("English"),
    "eo" => array("Esperanto"),
    "es" => array("Spanish", "Español"),
    "et" => array("Estonian", "Eesti"),
    "eu" => array("Basque", "Euskera"),
    "fa" => array("Persian", "فارسی"),
    "ff" => array("Fulah", "Fulfulde"),
    "fi" => array("Finnish", "Suomi"),
    "fj" => array("Fiji"),
    "fo" => array("Faeroese"),
    "fr" => array("French", "Français"),
    "fy" => array("Frisian", "Frysk"),
    "ga" => array("Irish", "Gaeilge"),
    "gd" => array("Scots Gaelic"),
    "gl" => array("Galician", "Galego"),
    "gn" => array("Guarani"),
    "gu" => array("Gujarati"),
    "gv" => array("Manx"),
    "ha" => array("Hausa"),
    "he" => array("Hebrew", "עברית"),
    "hi" => array("Hindi", "हिन्दी"),
    "ho" => array("Hiri Motu"),
    "hr" => array("Croatian", "Hrvatski"),
    "hu" => array("Hungarian", "Magyar"),
    "hy" => array("Armenian", "Հայերեն"),
    "hz" => array("Herero"),
    "ia" => array("Interlingua"),
    "id" => array("Indonesian", "Bahasa Indonesia"),
    "ie" => array("Interlingue"),
    "ig" => array("Igbo"),
    "ik" => array("Inupiak"),
    "is" => array("Icelandic", "Íslenska"),
    "it" => array("Italian", "Italiano"),
    "iu" => array("Inuktitut"),
    "ja" => array("Japanese", "日本語"),
    "jv" => array("Javanese"),
    "ka" => array("Georgian"),
    "kg" => array("Kongo"),
    "ki" => array("Kikuyu"),
    "kj" => array("Kwanyama"),
    "kk" => array("Kazakh", "Қазақ"),
    "kl" => array("Greenlandic"),
    "km" => array("Cambodian"),
    "kn" => array("Kannada", "ಕನ್ನಡ"),
    "ko" => array("Korean", "한국어"),
    "kr" => array("Kanuri"),
    "ks" => array("Kashmiri"),
    "ku" => array("Kurdish", "Kurdî"),
    "kv" => array("Komi"),
    "kw" => array("Cornish"),
    "ky" => array("Kirghiz", "Кыргыз"),
    "la" => array("Latin", "Latina"),
    "lb" => array("Luxembourgish"),
    "lg" => array("Luganda"),
    "ln" => array("Lingala"),
    "lo" => array("Laothian"),
    "lt" => array("Lithuanian", "Lietuviškai"),
    "lv" => array("Latvian", "Latviešu"),
    "mg" => array("Malagasy"),
    "mh" => array("Marshallese"),
    "mi" => array("Maori"),
    "mk" => array("Macedonian", "Македонски"),
    "ml" => array("Malayalam", "മലയാളം"),
    "mn" => array("Mongolian"),
    "mo" => array("Moldavian"),
    "mr" => array("Marathi"),
    "ms" => array("Malay", "Bahasa Melayu"),
    "mt" => array("Maltese", "Malti"),
    "my" => array("Burmese"),
    "na" => array("Nauru"),
    "nd" => array("North Ndebele"),
    "ne" => array("Nepali"),
    "ng" => array("Ndonga"),
    "nl" => array("Dutch", "Nederlands"),
    "no" => array("Norwegian", "Norsk"),
    "nr" => array("South Ndebele"),
    "nv" => array("Navajo"),
    "ny" => array("Chichewa"),
    "oc" => array("Occitan"),
    "om" => array("Oromo"),
    "or" => array("Oriya"),
    "os" => array("Ossetian"),
    "pa" => array("Punjabi"),
    "pi" => array("Pali"),
    "pl" => array("Polish", "Polski"),
    "ps" => array("Pashto", "پښتو"),
    "pt" => array("Portuguese", "Português"),
    "qu" => array("Quechua"),
    "rm" => array("Rhaeto-Romance"),
    "rn" => array("Kirundi"),
    "ro" => array("Romanian", "Română"),
    "ru" => array("Russian", "Русский"),
    "rw" => array("Kinyarwanda"),
    "sa" => array("Sanskrit"),
    "sc" => array("Sardinian"),
    "sd" => array("Sindhi"),
    "se" => array("Northern Sami"),
    "sg" => array("Sango"),
    "sh" => array("Serbo-Croatian"),
    "si" => array("Singhalese"),
    "sk" => array("Slovak", "Slovenčina"),
    "sl" => array("Slovenian", "Slovenščina"),
    "sm" => array("Samoan"),
    "sn" => array("Shona"),
    "so" => array("Somali"),
    "sq" => array("Albanian", "Shqip"),
    "sr" => array("Serbian", "Српски"),
    "ss" => array("Siswati"),
    "st" => array("Sesotho"),
    "su" => array("Sudanese"),
    "sv" => array("Swedish", "Svenska"),
    "sw" => array("Swahili", "Kiswahili"),
    "ta" => array("Tamil", "தமிழ்"),
    "te" => array("Telugu", "తెలుగు"),
    "tg" => array("Tajik"),
    "th" => array("Thai", "ภาษาไทย"),
    "ti" => array("Tigrinya"),
    "tk" => array("Turkmen"),
    "tl" => array("Tagalog"),
    "tn" => array("Setswana"),
    "to" => array("Tonga"),
    "tr" => array("Turkish", "Türkçe"),
    "ts" => array("Tsonga"),
    "tt" => array("Tatar", "Tatarça"),
    "tw" => array("Twi"),
    "ty" => array("Tahitian"),
    "ug" => array("Uighur"),
    "uk" => array("Ukrainian", "Українська"),
    "ur" => array("Urdu", "اردو"),
    "uz" => array("Uzbek", "o'zbek"),
    "ve" => array("Venda"),
    "vi" => array("Vietnamese", "Tiếng Việt"),
    "vo" => array("Volapük"),
    "wo" => array("Wolof"),
    "xh" => array("Xhosa", "isiXhosa"),
    "yi" => array("Yiddish"),
    "yo" => array("Yoruba", "Yorùbá"),
    "za" => array("Zhuang"),
    "zh-hans" => array("Chinese, Simplified", "简体中文"),
    "zh-hant" => array("Chinese, Traditional", "繁體中文"),
    "zu" => array("Zulu", "isiZulu"),
  );
}

?>
