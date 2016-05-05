<?php
/**
 * Drupal_Sniffs_Formatting_SpaceInlineIfSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that there is no space between "?" and ":" inline if/else statements.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Formatting_SpaceInlineIfSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_INLINE_ELSE);

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

        // Handle the short ternary operator (?:) introduced in PHP 5.3.
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($tokens[$previous]['code'] === T_INLINE_THEN) {
            if ($previous !== ($stackPtr - 1)) {
                $error = 'There must be no space between ? and :';
                $phpcsFile->addError($error, $stackPtr, 'SpaceInlineElse');
            }
        }//end if

    }//end process()


}//end class
