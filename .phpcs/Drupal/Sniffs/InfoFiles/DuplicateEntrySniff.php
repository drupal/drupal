<?php
/**
 * Drupal_Sniffs_InfoFiles_DuplicateEntrySniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Make sure that entries in info files are specified only once.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_InfoFiles_DuplicateEntrySniff implements PHP_CodeSniffer_Sniff
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

        $contents   = file_get_contents($phpcsFile->getFilename());
        $duplicates = $this->findDuplicateInfoFileEntries($contents);
        if (!empty($duplicates)) {
            foreach ($duplicates as $duplicate) {
                $error = 'Duplicate entry for "%s" in info file';
                $phpcsFile->addError($error, $stackPtr, 'DuplicateEntry', array($duplicate));
            }
        }

        return $end;

    }//end process()


    /**
     * Parses a Drupal info file and checsk if a key apperas more than once.
     *
     * @param string $data The contents of the info file to parse
     *
     * @return array A list of configuration keys that appear more than once.
     */
    protected function findDuplicateInfoFileEntries($data)
    {
        $info       = array();
        $duplicates = array();
        $constants  = get_defined_constants();

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

                if (array_key_exists($last, $parent)) {
                    $duplicates[] = $last;
                }

                $parent[$last] = $value;
            }//end foreach
        }//end if

        return $duplicates;

    }//end findDuplicateInfoFileEntries()


}//end class
