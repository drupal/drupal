<?php
/**
 * Drupal_Sniffs_CSS_ClassDefinitionNameSpacingSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Ensure there are no blank lines between the names of classes/IDs. Copied from
 * Squiz_Sniffs_CSS_ClassDefinitionNameSpacingSniff because we also check for comma
 * separated selectors on their own line.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_CSS_ClassDefinitionNameSpacingSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array('CSS');


    /**
     * Returns the token types that this sniff is interested in.
     *
     * @return int[]
     */
    public function register()
    {
        return array(T_OPEN_CURLY_BRACKET);

    }//end register()


    /**
     * Processes the tokens that this sniff is interested in.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where the token was found.
     * @param int                  $stackPtr  The position in the stack where
     *                                        the token was found.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Do not check nested style definitions as, for example, in @media style rules.
        $nested = $phpcsFile->findNext(T_OPEN_CURLY_BRACKET, ($stackPtr + 1), $tokens[$stackPtr]['bracket_closer']);
        if ($nested !== false) {
            return;
        }

        // Find the first blank line before this opening brace, unless we get
        // to another style definition, comment or the start of the file.
        $endTokens  = array(
                       T_OPEN_CURLY_BRACKET  => T_OPEN_CURLY_BRACKET,
                       T_CLOSE_CURLY_BRACKET => T_CLOSE_CURLY_BRACKET,
                       T_OPEN_TAG            => T_OPEN_TAG,
                      );
        $endTokens += PHP_CodeSniffer_Tokens::$commentTokens;

        $foundContent = false;
        $currentLine  = $tokens[$stackPtr]['line'];
        for ($i = ($stackPtr - 1); $i >= 0; $i--) {
            if (isset($endTokens[$tokens[$i]['code']]) === true) {
                break;
            }

            // A comma must be followed by a new line character.
            if ($tokens[$i]['code'] === T_COMMA
                && strpos($tokens[($i + 1)]['content'], $phpcsFile->eolChar) === false
            ) {
                $error = 'Multiple selectors should each be on a single line';
                $fix   = $phpcsFile->addFixableError($error, ($i + 1), 'MultipleSelectors');
                if ($fix === true) {
                    $phpcsFile->fixer->addNewline($i);
                }
            }

            // Selectors must be on the same line.
            if ($tokens[$i]['code'] === T_WHITESPACE
                && strpos($tokens[$i]['content'], $phpcsFile->eolChar) !== false
                && isset($endTokens[$tokens[($i - 1)]['code']]) === false
                && in_array($tokens[($i - 1)]['code'], array(T_WHITESPACE, T_COMMA)) === false
            ) {
                $error = 'Selectors must be on a single line';
                $fix   = $phpcsFile->addFixableError($error, $i, 'SeletorSingleLine');
                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken($i, str_replace($phpcsFile->eolChar, ' ', $tokens[$i]['content']));
                }
            }

            if ($tokens[$i]['line'] === $currentLine) {
                if ($tokens[$i]['code'] !== T_WHITESPACE) {
                    $foundContent = true;
                }

                continue;
            }

            // We changed lines.
            if ($foundContent === false) {
                // Before we throw an error, make sure we are not looking
                // at a gap before the style definition.
                $prev = $phpcsFile->findPrevious(T_WHITESPACE, $i, null, true);
                if ($prev !== false
                    && isset($endTokens[$tokens[$prev]['code']]) === false
                ) {
                    $error = 'Blank lines are not allowed between class names';
                    $fix   = $phpcsFile->addFixableError($error, ($i + 1), 'BlankLinesFound');
                    if ($fix === true) {
                        $phpcsFile->fixer->replaceToken(($i + 1), '');
                    }
                }

                break;
            }

            $foundContent = false;
            $currentLine  = $tokens[$i]['line'];
        }//end for

    }//end process()


}//end class
