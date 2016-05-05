<?php
/**
 * Drupal_Sniffs_InfoFiles_ClassFilesSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks files[] entries in info files. Only files containing classes/interfaces
 * should be listed.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_InfoFiles_ClassFilesSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_INLINE_HTML);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        // Only run this sniff once per info file.
        $end = (count($phpcsFile->getTokens()) + 1);

        $fileExtension = strtolower(substr($phpcsFile->getFilename(), -4));
        if ($fileExtension !== 'info') {
            return $end;
        }

        $contents = file_get_contents($phpcsFile->getFilename());
        $info     = self::drupalParseInfoFormat($contents);
        if (isset($info['files']) === true && is_array($info['files']) === true) {
            foreach ($info['files'] as $file) {
                $fileName = dirname($phpcsFile->getFilename()).'/'.$file;
                if (file_exists($fileName) === false) {
                    // We need to find the position of the offending line in the
                    // info file.
                    $ptr   = self::getPtr('files[]', $file, $phpcsFile);
                    $error = 'Declared file was not found';
                    $phpcsFile->addError($error, $ptr, 'DeclaredFileNotFound');
                    continue;
                }

                // Read the file, parse its tokens and check if it actually contains
                // a class or interface definition.
                $searchTokens = token_get_all(file_get_contents($fileName));
                foreach ($searchTokens as $token) {
                    if (is_array($token) === true
                        && in_array($token[0], array(T_CLASS, T_INTERFACE, T_TRAIT)) === true
                    ) {
                        continue 2;
                    }
                }

                $ptr   = self::getPtr('files[]', $file, $phpcsFile);
                $error = "It's only necessary to declare files[] if they declare a class or interface.";
                $phpcsFile->addError($error, $ptr, 'UnecessaryFileDeclaration');
            }//end foreach
        }//end if

        return $end;

    }//end process()


    /**
     * Helper function that returns the position of the key in the info file.
     *
     * @param string               $key      Key name to search for.
     * @param string               $value    Corresponding value to search for.
     * @param PHP_CodeSniffer_File $infoFile Info file to search in.
     *
     * @return int|false Returns the stack position if the file name is found, false
     *                                      otherwise.
     */
    public static function getPtr($key, $value, PHP_CodeSniffer_File $infoFile)
    {
        foreach ($infoFile->getTokens() as $ptr => $tokenInfo) {
            if (preg_match('@^[\s]*'.preg_quote($key).'[\s]*=[\s]*["\']?'.preg_quote($value).'["\']?@', $tokenInfo['content']) === 1) {
                return $ptr;
            }
        }

        return false;

    }//end getPtr()


    /**
     * Parses a Drupal info file. Copied from Drupal core drupal_parse_info_format().
     *
     * @param string $data The contents of the info file to parse
     *
     * @return array The info array.
     */
    public static function drupalParseInfoFormat($data)
    {
        $info      = array();
        $constants = get_defined_constants();

        if (preg_match_all(
            '
          @^\s*                           # Start at the beginning of a line, ignoring leading whitespace
          ((?:
            [^=;\[\]]|                    # Key names cannot contain equal signs, semi-colons or square brackets,
            \[[^\[\]]*\]                  # unless they are balanced and not nested
          )+?)
          \s*=\s*                         # Key/value pairs are separated by equal signs (ignoring white-space)
          (?:
            ("(?:[^"]|(?<=\\\\)")*")|     # Double-quoted string, which may contain slash-escaped quotes/slashes
            (\'(?:[^\']|(?<=\\\\)\')*\')| # Single-quoted string, which may contain slash-escaped quotes/slashes
            ([^\r\n]*?)                   # Non-quoted string
          )\s*$                           # Stop at the next end of a line, ignoring trailing whitespace
          @msx',
            $data,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                // Fetch the key and value string.
                $i = 0;
                foreach (array('key', 'value1', 'value2', 'value3') as $var) {
                    $$var = isset($match[++$i]) ? $match[$i] : '';
                }

                $value = stripslashes(substr($value1, 1, -1)).stripslashes(substr($value2, 1, -1)).$value3;

                // Parse array syntax.
                $keys   = preg_split('/\]?\[/', rtrim($key, ']'));
                $last   = array_pop($keys);
                $parent = &$info;

                // Create nested arrays.
                foreach ($keys as $key) {
                    if ($key == '') {
                        $key = count($parent);
                    }

                    if (!isset($parent[$key]) || !is_array($parent[$key])) {
                        $parent[$key] = array();
                    }

                    $parent = &$parent[$key];
                }

                // Handle PHP constants.
                if (isset($constants[$value])) {
                    $value = $constants[$value];
                }

                // Insert actual value.
                if ($last == '') {
                    $last = count($parent);
                }

                $parent[$last] = $value;
            }//end foreach
        }//end if

        return $info;

    }//end drupalParseInfoFormat()


}//end class
