<?php
/**
 * Drupal_Sniffs_Array_ArraySniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Drupal_Sniffs_Array_ArraySniff.
 *
 * Checks if the array's are styled in the Drupal way.
 * - Comma after the last array element
 * - Indentation is 2 spaces for multi line array definitions
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Array_ArraySniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
                T_ARRAY,
                T_OPEN_SHORT_ARRAY,
               );

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Support long and short syntax.
        $parenthesis_opener = 'parenthesis_opener';
        $parenthesis_closer = 'parenthesis_closer';
        if ($tokens[$stackPtr]['code'] === T_OPEN_SHORT_ARRAY) {
            $parenthesis_opener = 'bracket_opener';
            $parenthesis_closer = 'bracket_closer';
        }

        $lastItem = $phpcsFile->findPrevious(
            PHP_CodeSniffer_Tokens::$emptyTokens,
            ($tokens[$stackPtr][$parenthesis_closer] - 1),
            $stackPtr,
            true
        );

        // Empty array.
        if ($lastItem === $tokens[$stackPtr][$parenthesis_opener]) {
            return;
        }

        // Inline array.
        $isInlineArray = $tokens[$tokens[$stackPtr][$parenthesis_opener]]['line'] === $tokens[$tokens[$stackPtr][$parenthesis_closer]]['line'];

        // Check if the last item in a multiline array has a "closing" comma.
        if ($tokens[$lastItem]['code'] !== T_COMMA && $isInlineArray === false
            && $tokens[($lastItem + 1)]['code'] !== T_CLOSE_PARENTHESIS
            && $tokens[($lastItem + 1)]['code'] !== T_CLOSE_SHORT_ARRAY
        ) {
            $data = array($tokens[$lastItem]['content']);
            $fix  = $phpcsFile->addFixableWarning('A comma should follow the last multiline array item. Found: %s', $lastItem, 'CommaLastItem', $data);
            if ($fix === true) {
                $phpcsFile->fixer->addContent($lastItem, ',');
            }

            return;
        }

        if ($isInlineArray === true) {
            // Check if this array contains at least 3 elements and exceeds the 80
            // character line length.
            if ($tokens[$tokens[$stackPtr][$parenthesis_closer]]['column'] > 80) {
                $comma1 = $phpcsFile->findNext(T_COMMA, ($stackPtr + 1), $tokens[$stackPtr][$parenthesis_closer]);
                if ($comma1 !== false) {
                    $comma2 = $phpcsFile->findNext(T_COMMA, ($comma1 + 1), $tokens[$stackPtr][$parenthesis_closer]);
                    if ($comma2 !== false) {
                        $error = 'If the line declaring an array spans longer than 80 characters, each element should be broken into its own line';
                        $phpcsFile->addError($error, $stackPtr, 'LongLineDeclaration');
                    }
                }
            }

            // Only continue for multi line arrays.
            return;
        }

        // Find the first token on this line.
        $firstLineColumn = $tokens[$stackPtr]['column'];
        for ($i = $stackPtr; $i >= 0; $i--) {
            // If there is a PHP open tag then this must be a template file where we
            // don't check indentation.
            if ($tokens[$i]['code'] === T_OPEN_TAG) {
                return;
            }

            // Record the first code token on the line.
            if ($tokens[$i]['code'] !== T_WHITESPACE) {
                $firstLineColumn = $tokens[$i]['column'];
                // This could be a multi line string or comment beginning with white
                // spaces.
                $trimmed = ltrim($tokens[$i]['content']);
                if ($trimmed !== $tokens[$i]['content']) {
                    $firstLineColumn = ($firstLineColumn + strpos($tokens[$i]['content'], $trimmed));
                }
            }

            // It's the start of the line, so we've found our first php token.
            if ($tokens[$i]['column'] === 1) {
                break;
            }
        }//end for

        $lineStart = $stackPtr;
        // Iterate over all lines of this array.
        while ($lineStart < $tokens[$stackPtr][$parenthesis_closer]) {
            // Find next line start.
            $newLineStart = $lineStart;
            $current_line = $tokens[$newLineStart]['line'];
            while ($current_line >= $tokens[$newLineStart]['line']) {
                $newLineStart = $phpcsFile->findNext(
                    PHP_CodeSniffer_Tokens::$emptyTokens,
                    ($newLineStart + 1),
                    ($tokens[$stackPtr][$parenthesis_closer] + 1),
                    true
                );

                if ($newLineStart === false) {
                    break 2;
                }

                // Long array syntax: Skip nested arrays, they are checked in a next
                // run.
                if ($tokens[$newLineStart]['code'] === T_ARRAY) {
                    $newLineStart = $tokens[$newLineStart]['parenthesis_closer'];
                    $current_line = $tokens[$newLineStart]['line'];
                }

                // Short array syntax: Skip nested arrays, they are checked in a next
                // run.
                if ($tokens[$newLineStart]['code'] === T_OPEN_SHORT_ARRAY) {
                    $newLineStart = $tokens[$newLineStart]['bracket_closer'];
                    $current_line = $tokens[$newLineStart]['line'];
                }

                // Nested structures such as closures: skip those, they are checked
                // in other sniffs. If the conditions of a token are different it
                // means that it is in a different nesting level.
                if ($tokens[$newLineStart]['conditions'] !== $tokens[$stackPtr]['conditions']) {
                    $current_line++;
                }
            }//end while

            if ($newLineStart === $tokens[$stackPtr][$parenthesis_closer]) {
                // End of the array reached.
                if ($tokens[$newLineStart]['column'] !== $firstLineColumn) {
                    $error = 'Array closing indentation error, expected %s spaces but found %s';
                    $data  = array(
                              $firstLineColumn - 1,
                              $tokens[$newLineStart]['column'] - 1,
                             );
                    $fix   = $phpcsFile->addFixableError($error, $newLineStart, 'ArrayClosingIndentation', $data);
                    if ($fix === true) {
                        if ($tokens[$newLineStart]['column'] === 1) {
                            $phpcsFile->fixer->addContentBefore($newLineStart, str_repeat(' ', ($firstLineColumn - 1)));
                        } else {
                            $phpcsFile->fixer->replaceToken(($newLineStart - 1), str_repeat(' ', ($firstLineColumn - 1)));
                        }
                    }
                }

                break;
            }

            $expectedColumn = ($firstLineColumn + 2);
            // If the line starts with "->" then we assume an additional level of
            // indentation.
            if ($tokens[$newLineStart]['code'] === T_OBJECT_OPERATOR) {
                $expectedColumn += 2;
            }

            if ($tokens[$newLineStart]['column'] !== $expectedColumn) {
                // Skip lines in nested structures such as a function call within an
                // array, no defined coding standard for those.
                $innerNesting = empty($tokens[$newLineStart]['nested_parenthesis']) === false
                    && end($tokens[$newLineStart]['nested_parenthesis']) < $tokens[$stackPtr][$parenthesis_closer];
                // Skip lines that are part of a multi-line string.
                $isMultiLineString = $tokens[($newLineStart - 1)]['code'] === T_CONSTANT_ENCAPSED_STRING
                    && substr($tokens[($newLineStart - 1)]['content'], -1) === $phpcsFile->eolChar;
                if ($innerNesting === false && $isMultiLineString === false) {
                    $error = 'Array indentation error, expected %s spaces but found %s';
                    $data  = array(
                              $expectedColumn - 1,
                              $tokens[$newLineStart]['column'] - 1,
                             );
                    $fix   = $phpcsFile->addFixableError($error, $newLineStart, 'ArrayIndentation', $data);
                    if ($fix === true) {
                        if ($tokens[$newLineStart]['column'] === 1) {
                            $phpcsFile->fixer->addContentBefore($newLineStart, str_repeat(' ', ($expectedColumn - 1)));
                        } else {
                            $phpcsFile->fixer->replaceToken(($newLineStart - 1), str_repeat(' ', ($expectedColumn - 1)));
                        }
                    }
                }
            }//end if

            $lineStart = $newLineStart;
        }//end while

    }//end process()


}//end class
