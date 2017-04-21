<?php
/**
 * Drupal_Sniffs_Functions_FunctionDeclarationSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Ensure that there is only one space after the function keyword and no space
 * before the opening parenthesis.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Functions_FunctionDeclarationSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_FUNCTION);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[($stackPtr + 1)]['content'] !== ' ') {
            $error = 'Expected exactly one space after the function keyword';
            $phpcsFile->addError($error, ($stackPtr + 1), 'SpaceAfter');
        }

        if (isset($tokens[($stackPtr + 3)]) === true
            && $tokens[($stackPtr + 3)]['code'] === T_WHITESPACE
        ) {
            $error = 'Space before opening parenthesis of function definition prohibited';
            $phpcsFile->addError($error, ($stackPtr + 3), 'SpaceBeforeParenthesis');
        }

    }//end process()


}//end class
