<?php

/**
 * @file
 * Unifies formats of transliteration data from various sources.
 *
 * A few notes about this script:
 * - The functions in this file are NOT SECURE, because they use PHP functions
 *   like eval(). Absolutely do not run this script unless you trust the data
 *   files used for input.
 * - You will need to change the name of this file to remove the .txt extension
 *   before running it (it has been given this name so that you cannot run it
 *   by mistake). When you do that, move it out of your web root as well so
 *   that it cannot be run via a URL, and run the script via the PHP command
 *   at a command prompt.
 * - This script, depending on which portions of it you run, depends on having
 *   input data from various sources in sub-directories below where this file
 *   is located. The data inputs are as follows:
 *   - Existing Drupal Core transliteration data: Sub-directory 'data'; comes
 *     from core/lib/Drupal/Component/Transliteration/data
 *   - Midgardmvc data: Sub-directory 'utf8_to_ascii_db'; download from
 *     https://github.com/bergie/midgardmvc_helper_urlize/downloads
 *   - CPAN Text-Unidecode data: Sub-directory 'Unidecode'; download from
 *     http://search.cpan.org/~sburke/Text-Unidecode-0.04/lib/Text/Unidecode.pm
 *   - Node.js project: Sub-directory 'unidecoder_data'; download from
 *     https://github.com/bitwalker/stringex/downloads
 *   - JUnidecode project: Sub-directory 'junidecode'; download source from
 *     http://www.ippatsuman.com/projects/junidecode/index.html
 * - You will also need to make directory 'outdata' to hold output.
 * - If you plan to use the 'intl' data, you will also need to have the PECL
 *   packages 'yaml' and 'intl' installed.  See
 *   http://php.net/manual/install.pecl.downloads.php for generic PECL
 *   package installation instructions. The following commands on Ubuntu Linux
 *   will install yaml and intl packages:
 *   @code
 *   sudo apt-get install libyaml-dev
 *   sudo pecl install yaml
 *   sudo apt-get install php5-intl
 *   sudo apt-get install libicu-dev
 *   sudo pecl install intl
 *   @endcode
 *   After running these commands, you will need to make sure
 *   'extension=intl.so' and 'extension=yaml.so' are added to the php.ini file
 *   that is in use for the PHP command-line command.
 * - When you have collected all of the data and installed the required
 *   packages, you will need to find the specific commands below that you want
 *   to use and un-comment them. The preferred data source for Drupal Core is
 *   the PECL 'intl' package, and the line that needs to be un-commented in
 *   order to make a Drupal Core patch is:
 *   @code
 *   patch_drupal('outdata');
 *   @endcode
 * - The functions are documented in more detail in their headers where they
 *   are defined. Many have parameters that you can use to change the output.
 */

// Commands to read various data sources:
// $data = read_drupal_data();
// $data = read_midgard_data();
// $data = read_cpan_data();
// $data = read_nodejs_data();
// $data = read_intl_data();
// $data = read_junidecode_data();

// After running a read_*_data() function, you can print out the data
// (it will make a LOT of output):
// print_r($data);

// Command to read in all of data sources and output in CSV format, explaining
// the differences:
// read_all_to_csv();

// Command to patch Drupal Core data, using the intl data set, and put the
// resulting changed data files in the 'outdata' directory:
patch_drupal('outdata');

/**
 * Reads in all transliteration data and outputs differences in CSV format.
 *
 * Each data set is compared to the Drupal Core reference data set, and the
 * differences are noted. The data must be in the locations noted in the
 * file header above. The CSV output has several columns. The first one is the
 * Unicode character code. The next columns contain the transliteration of
 * that character in each of the data sets. The last column, tells what the
 * differences are between the Drupal Core reference set and the other data
 * sets:
 * - missing: The target set is missing data that the Drupal set has.
 * - provided: The target set has provided data that Drupal does not have.
 * - case: The target and Drupal set output differ only in upper/lower case.
 * - different: The target and Drupal set output differ in more than just case.
 *
 * @param bool $print_all
 *   TRUE to print all data; FALSE (default) to print just data where there
 *   are differences between the Drupal set and other data sources.
 * @param bool $print_missing
 *   TRUE to print cases where one of the non-Drupal sets is missing information
 *   and that is the only difference; FALSE (default) to include these rows.
 */
function read_all_to_csv($print_all = FALSE, $print_missing = FALSE) {
  $data = array();
  $types = array('drupal', 'midgard', 'cpan', 'nodejs', 'junidecode', 'intl');

  // Alternatively, if you just want to compare a couple of data sets, you can
  // uncomment and edit the following line:
  // $types = array('drupal', 'intl');

  // Read in all the data.
  foreach ($types as $type) {
    $data[$type] = call_user_func('read_' . $type . '_data');
  }

  // Print CSV header row.
  print "character,";
  print implode(',', $types);
  print ",why\n";

  // Go through all the banks of character data.
  for ($bank = 0; $bank < 256; $bank++) {

    // Go through characters in bank; skip pure ASCII characters.
    $start = ($bank == 0) ? 0x80 : 0;
    for ($chr = $start; $chr < 256; $chr++) {

      // Gather the data together for this character.
      $row = array();
      foreach ($types as $type) {
        $row[$type] = (isset($data[$type][$bank][$chr]) && is_string($data[$type][$bank][$chr])) ? $data[$type][$bank][$chr] : '';
      }

      // Only print if there are differences or we are printing all data.
      $print = $print_all;
      $ref = $row['drupal'];
      $why = array();
      foreach ($types as $type) {
        // Try to characterize what the differences are.
        if ($row[$type] != $ref) {
          if ($row[$type] == '') {
            $why['missing'] = 'missing';
            if ($print_missing) {
              $print = TRUE;
            }
          }
          elseif ($ref == '') {
            $why['provided'] = 'provided';
            $print = TRUE;
          }
          elseif ($row[$type] == strtolower($ref) || $row[$type] == strtoupper($ref)) {
            $why['case'] = 'case';
            $print = TRUE;
          }
          else {
            $why['different'] = 'different';
            $print = TRUE;
          }
        }
      }

      // Print the data line.
      if ($print) {
        print '0x' . sprintf('%04x', 256 * $bank + $chr) . ',';
        foreach ($row as $out) {
          print '"' . addcslashes($out, '"') . '", ';
        }
        print implode(':', $why);
        print "\n";
      }
    }
  }
}

/**
 * Reads in 'intl' transliteration data and writes out changed Drupal files.
 *
 * Writes out the Drupal data files that would have to change to make our data
 * match the intl data set.
 *
 * @param string $outdir
 *   Directory to put the patched data files in (under where the script is
 *   being run).
 */
function patch_drupal($outdir) {
  $data = array();

  // Note that this is hard-wired below. Changing this line will have no
  // effect except to break this function.
  $types = array('drupal', 'intl');

  // Read in all the data.
  foreach ($types as $type) {
    $data[$type] = call_user_func('read_' . $type . '_data');
  }

  // Go through all the banks of character data.
  for ($bank = 0; $bank < 256; $bank++) {
    $print_bank = FALSE;

    // Go through characters in bank; skip pure ASCII characters.
    $start = ($bank == 0) ? 0x80 : 0;
    $newdata = array();
    for ($chr = 0; $chr < 256; $chr++) {
      // Fill up the start of the ASCII range.
      if ($chr < $start) {
        $newdata[$chr] = chr($chr);
        continue;
      }

      // Figure out what characters we actually have.
      $drupal = isset($data['drupal'][$bank][$chr]) ? $data['drupal'][$bank][$chr] : NULL;
      // Note that for intl, we only want to keep the transliteration if it
      // has something other than '' in it.
      $intl = isset($data['intl'][$bank][$chr]) && $data['intl'][$bank][$chr] != '' ? $data['intl'][$bank][$chr] : NULL;
      // Make sure we have something in the Drupal data set, in case we need
      // to print.
      $newdata[$chr] = $drupal;

      if (!isset($intl)) {
        continue;
      }
      if (!isset($drupal) || $drupal != $intl) {
        $print_bank = TRUE;
        $newdata[$chr] = $intl;
      }
    }

    // If we found a difference, output a data file.
    if ($print_bank) {
      write_data_file($newdata, $bank, $outdir);
    }
  }
}

/**
 * Reads in the Drupal Core generic transliteration data set.
 *
 * The data is expected to be in files xNN.php in directory 'data' under
 * this file's directory.
 *
 * @return array
 *   Nested array of transliteration data. Outer keys are the first two
 *   bytes of Unicode characters (or 0 for base ASCII characters). The next
 *   level is the other two bytes, and the values are the transliterations.
 *
 * @see PhpTransliteration::readGenericData()
 */
function read_drupal_data() {
  $dir = __DIR__ . '/data';
  $out = array();

  // Read data files.
  for ($bank = 0; $bank < 256; $bank++) {
    $base = array();
    $file = $dir . '/x' . sprintf('%02x', $bank) . '.php';
    if (is_file($file)) {
      include($file);
    }
    $out[$bank] = $base;
  }

  return $out;
}

/**
 * Reads in the MidgardMVC transliteration data.
 *
 * The data is expected to be in files xNN.php in directory utf8_to_ascii_db
 * under the directory where this file resides. It can be downloaded from
 * https://github.com/bergie/midgardmvc_helper_urlize/downloads.
 *
 * @return array
 *   Nested array of transliteration data. Outer keys are the first two
 *   bytes of Unicode characters (or 0 for base ASCII characters). The next
 *   level is the other two bytes, and the values are the transliterations.
 */
function read_midgard_data() {
  $dir = __DIR__ . '/utf8_to_ascii_db';
  $out = array();

  // Read data files.
  for ($bank = 0; $bank < 256; $bank++) {
    $UTF8_TO_ASCII = array($bank => array());
    $file = $dir . '/x' . sprintf('%02x', $bank) . '.php';
    if (is_file($file)) {
      include($file);
    }
    $base = $UTF8_TO_ASCII[$bank];

    // For unknown characters, these files have '[?]' in them. Replace with
    // NULL for compatibility with our data.
    $base = array_map('_replace_question_with_null', $base);
    $out[$bank] = $base;
  }

  return $out;
}

/**
 * Reads in the CPAN Text::Unidecode data set.
 *
 * The data is expected to be in files xNN.pm in directory 'Unidecode' under
 * this file's directory. It can be downloaded from
 * http://search.cpan.org/~sburke/Text-Unidecode-0.04/lib/Text/Unidecode.pm.
 *
 * @return array
 *   Nested array of transliteration data. Outer keys are the first two
 *   bytes of Unicode characters (or 0 for base ASCII characters). The next
 *   level is the other two bytes, and the values are the transliterations.
 */
function read_cpan_data() {
  $dir = __DIR__ . '/Unidecode';
  $out = array();

  // Read data files.
  for ($bank = 0; $bank < 256; $bank++) {
    $base = array();
    $file = $dir . '/x' . sprintf('%02x', $bank) . '.pm';
    if (is_file($file)) {
      $base = _cpan_read_file($file);
    }
    $out[$bank] = $base;
  }

  return $out;
}

/**
 * Reads in the data in a single file from the Text::Unidecode CPAN project.
 *
 * @param string $file
 *   File to read from.
 *
 * @return array
 *   Data read from the file.
 *
 * @see read_cpan_data()
 */
function _cpan_read_file($file) {

  $contents = file($file);
  $save = '';
  foreach ($contents as $line) {
    // Discard lines starting with # or $. The first line seems to have a
    // comment starting with #, the second has a Perl line like
    // $Text::Unidecode::Char[0x04] = [, -- and we do not want either.
    if (preg_match('|^\s*[#\$]|', $line)) {
      continue;
    }

    // Discard lines ending with semi-colons, which we also don't want
    // (there seem to be two of these lines at the end of the files).
    if (preg_match('|;\s*$|', $line)) {
      continue;
    }

    // Replace '[?]' with nothing (that means "don't know how to
    // transliterate"). In some files, this is encoded as qq{[?]} or
    // qq{[?] } instead.
    $line = str_replace('qq{[?]}', 'NULL', $line);
    $line = str_replace('qq{[?] }', 'NULL', $line);
    $line = str_replace("'[?]'", 'NULL', $line);

    // Replace qq{} with either "" or '' or nothing, depending on what is
    // inside it.
    $line = str_replace('qq{\{}', "'{'", $line);
    $line = str_replace('qq{\}}', "'}'", $line);
    $line = str_replace('qq{\} }', "'} '", $line);
    $line = str_replace("qq{\\\\}", '"\\\\"', $line);
    $line = str_replace("qq{\\", "qq{'", $line);
    $line = str_replace("qq{\"'}", "\"\\\"'\"", $line);
    $line = preg_replace('|qq\{([^\'\}]+)\}|', "'$1'", $line);
    $line = preg_replace('|qq\{([^\}]+)\}|', '"$1"', $line);

    $save .= $line;
  }

  // Now we should have a string that looks like:
  // 'a', 'b', ...
  // Evaluate as an array.
  $save = 'return array(' . $save . ');';

  $data = @eval($save);
  if (isset($data) && is_array($data)) {
    $data = array_map('_replace_hex_with_character', $data);
  }
  else {
    // There was a problem, so throw an error and exit.
    print "Problem in evaluating $file\n";
    print $save;
    eval($save);
    exit();
  }

  // For unknown characters, these files may still have '[?]' in them. Replace
  // with NULL for compatibility with our data.
  $data = array_map('_replace_question_with_null', $data);

  return $data;
}

/**
 * Reads in the Node.js transliteration data.
 *
 * The data is expected to be in files xNN.yml in directory unidecoder_data
 * under the directory where this file resides. It can be downloaded from
 * https://github.com/bitwalker/stringex/downloads. You also need the PECL
 * 'yaml' extension installed for this function to work.
 *
 * @return array
 *   Nested array of transliteration data. Outer keys are the first two
 *   bytes of Unicode characters (or 0 for base ASCII characters). The next
 *   level is the other two bytes, and the values are the transliterations.
 */
function read_nodejs_data() {
  $dir = __DIR__ . '/unidecoder_data';
  $out = array();

  // Read data files.
  for ($bank = 0; $bank < 256; $bank++) {
    $base = array();
    $file = $dir . '/x' . sprintf('%02x', $bank) . '.yml';
    if (is_file($file)) {
      $base = yaml_parse_file($file);
      // For unknown characters, these files have '[?]' in them. Replace with
      // NULL for compatibility with our data.
      $base = array_map('_replace_question_with_null', $base);
    }
    $out[$bank] = $base;
  }

  return $out;
}

/**
 * Loads the PECL 'intl' Transliterator class's transliteration data.
 *
 * You need to have the PECL 'intl' package installed for this to work.
 *
 * @return array
 *   Nested array of transliteration data. Outer keys are the first two
 *   bytes of Unicode characters (or 0 for base ASCII characters). The next
 *   level is the other two bytes, and the values are the transliterations.
 */
function read_intl_data() {
  // In order to transliterate, you first have to create a transliterator
  // object. This needs a list of transliteration operations. You can get a
  // list of available operations with:
  //   print_r(Transliterator::listIDs()); exit();
  // And a few of these are documented on
  // http://userguide.icu-project.org/transforms/general and
  // http://www.unicode.org/reports/tr15/ (for normalizations).
  // There are also maps to the Unicode characters at:
  //  http://www.unicode.org/roadmaps/bmp/
  //  http://www.unicode.org/charts/nameslist/
  $ops = '';

  // The first step in any transform: separate out accents and remove them.
  $ops .= 'NFD; [:Nonspacing Mark:] Remove; NFC;';

  // Then you need to do a bunch of language-specific or script-specific
  // transliterations. Here is hopefully a representative set. There are
  // quite a few scripts that don't appear to have rules currently, such
  // as Ethiopian.
  $ops .= 'Greek-Latin; ';
  $ops .= 'Cyrillic-Latin; ';
  $ops .= 'Armenian-Latin; ';
  $ops .= 'Hebrew-Latin; ';
  $ops .= 'Arabic-Latin; ';
  $ops .= 'Syriac-Latin; ';
  $ops .= 'Thaana-Latin; ';
  $ops .= 'Devanagari-Latin; ';
  $ops .= 'Bengali-Latin; ';
  $ops .= 'Gurmukhi-Latin; ';
  $ops .= 'Gujarati-Latin; ';
  $ops .= 'Oriya-Latin; ';
  $ops .= 'Tamil-Latin; ';
  $ops .= 'Telugu-Latin; ';
  $ops .= 'Kannada-Latin; ';
  $ops .= 'Malayalam-Latin; ';
  $ops .= 'Thai-Latin; ';
  $ops .= 'Georgian-Latin; ';
  $ops .= 'Hangul-Latin; ';
  $ops .= 'Mongolian-Latin/BGN; ';
  $ops .= 'Jamo-Latin; ';
  $ops .= 'Katakana-Latin; ';
  $ops .= 'Any-Latin; ';

  // Finally, after transforming to Latin, transform to ASCII.
  $ops .= 'Latin-ASCII; ';

  // Remove any remaining accents and recompose.
  $ops .= 'NFD; [:Nonspacing Mark:] Remove; NFC;';

  $trans = Transliterator::create($ops);
  $out = array();

  // Transliterate all possible characters.
  for ($bank = 0; $bank < 256; $bank++) {
    $data = array();
    for ($chr = 0; $chr < 256; $chr++) {
      // Skip the UTF-16 and "private use" ranges completely.
      $OK = ($bank <= 0xd8 || $bank > 0xf8);

      $result = $OK ? $trans->transliterate(mb_convert_encoding(pack('n', 256 * $bank + $chr), 'UTF-8', 'UTF-16BE')) : '';

      // See if we have managed to transliterate this to ASCII or not. If not,
      // return NULL instead of this character.
      $max = chr(127);
      foreach (preg_split('//u', $result, 0, PREG_SPLIT_NO_EMPTY) as $character) {
        if ($character > $max) {
          $OK = $OK && FALSE;
          break;
        }
      }
      $data[$chr] = ($OK) ? $result : NULL;
    }
    $out[$bank] = $data;
  }

  return $out;
}

/**
 * Reads in the JUnidecode data set.
 *
 * The data is expected to be in files XNN.java in directory 'junidecode' under
 * this file's directory. It can be downloaded from
 * http://www.ippatsuman.com/projects/junidecode/index.html
 *
 * @return array
 *   Nested array of transliteration data. Outer keys are the first two
 *   bytes of Unicode characters (or 0 for base ASCII characters). The next
 *   level is the other two bytes, and the values are the transliterations.
 */
function read_junidecode_data() {
  $dir = __DIR__ . '/junidecode';
  $out = array();

  // Read data files.
  for ($bank = 0; $bank < 256; $bank++) {
    $base = array();
    $file = $dir . '/X' . sprintf('%02x', $bank) . '.java';
    if (is_file($file)) {
      $base = _junidecode_read_file($file);
    }
    $out[$bank] = $base;
  }

  return $out;
}

/**
 * Reads in the data in a single file from the JUnidecode project.
 *
 * @param string $file
 *   File to read from.
 *
 * @return array
 *   Data read from the file.
 *
 * @see read_junidecode_data()
 */
function _junidecode_read_file($file) {
  $contents = file($file);
  $save = '';
  foreach ($contents as $line) {
    // Discard lines starting with * or / or package or class or public or },
    // to get rid of comments and Java code.
    if (preg_match('|^\s*[\*/\}]|', $line)) {
      continue;
    }
    if (preg_match('/^\s*package|public|class/', $line)) {
      continue;
    }

    // Some of the lines look like this:
    //      new String("" + (char) 0x00), // 0x00
    // Transform to be '0x00,'
    $line = preg_replace('|^\s*new\s+String\s*\(\s*""\s*\+\s*\(char\)\s+0x([0-9]+).*$|', '0x$1,', $line);

    // Strings are in double quotes, yet many have \' in them.
    $line = str_replace("\'", "'", $line);

    // Everything else should probably be OK -- the lines are like:
    //  "Ie", // 0x00
    $save .= $line;
  }

  // Evaluate as an array.
  $save = 'return array(' . $save . ');';

  $data = @eval($save);
  if (isset($data) && is_array($data)) {
    $data = array_map('_replace_hex_with_character', $data);
    $data = array_map('_replace_question_with_null', $data);
  }
  else {
    // There was a problem, so throw an error and exit.
    print "Problem in evaluating $file\n";
    print $save;
    eval($save);
    exit();
  }

  return $data;
}

/**
 * Callback for array_map(): Returns $data, with '[?]' replaced with NULL.
 */
function _replace_question_with_null($data) {
  return ($data == '[?]' || $data == '[?] ') ? NULL : $data;
}

/**
 * Callback for array_map(): Replaces '\xNN' with the actual character.
 */
function _replace_hex_with_character($item) {
  if (strpos($item, '\x') === 0) {
    $item = eval($item);
  }
  return $item;
}

/**
 * Writes a data file out in the standard Drupal Core data format.
 *
 * @param array $data
 *   Array of data to write out.
 * @param string $bank
 *   Bank of characters it belongs to.
 * @param string $dir
 *   Output directory.
 */
function write_data_file($data, $bank, $outdir) {
  $dir = __DIR__ . '/' . $outdir;
  $file = $dir . '/x' . sprintf('%02x', $bank) . '.php';

  $out = '';
  $out .= "<?php\n\n/**\n * @file\n * Generic transliteration data for the PhpTransliteration class.\n */\n\n\$base = array(\n";

  // The 00 file skips the ASCII range
  $start = 0;
  if ($bank == 0) {
    $start = 0x80;
    $out .= "  // Note: to save memory plain ASCII mappings have been left out.\n";
  }

  for ($line = $start; $line <= 0xf0; $line += 0x10) {
    $out .= '  0x' . sprintf('%02X', $line) . ' =>';
    $elems = array_values(array_slice($data, $line, 16));
    for ($i = 0; $i < 16; $i++ ) {
      if (isset($elems[$i])) {
        $out .= " '" . addcslashes($elems[$i], "'\\") . "',";
      }
      else {
        $out .= ' NULL,';
      }
    }
    $out .= "\n";
  }

  $out .= ");\n";

  file_put_contents($file, $out);
}
