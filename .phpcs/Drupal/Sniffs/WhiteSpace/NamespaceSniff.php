<?php
/**
 * Drupal_Sniffs_WhiteSpace_NamespaceSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that there is exactly one space after the namespace keyword.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_WhiteSpace_NamespaceSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_NAMESPACE);

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
            $error = 'There must be exactly one space after the namepace keyword';
            $fix   = $phpcsFile->addFixableError($error, ($stackPtr + 1), 'OneSpace');
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken(($stackPtr + 1), ' ');
            }
        }

    }//end process()


}//end class
