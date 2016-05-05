<?php
/**
 * Drupal_Sniffs_Semantics_FunctionDefinition.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Helper class to sniff for function definitions.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
abstract class Drupal_Sniffs_Semantics_FunctionDefinition implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_STRING);

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
        // Check if this is a function definition.
        $functionPtr = $phpcsFile->findPrevious(
            PHP_CodeSniffer_Tokens::$emptyTokens,
            ($stackPtr - 1),
            null,
            true
        );
        if ($tokens[$functionPtr]['code'] === T_FUNCTION) {
            $this->processFunction($phpcsFile, $stackPtr, $functionPtr);
        }

    }//end process()


    /**
     * Process this function definition.
     *
     * @param PHP_CodeSniffer_File $phpcsFile   The file being scanned.
     * @param int                  $stackPtr    The position of the function name in the stack.
     *                                           name in the stack.
     * @param int                  $functionPtr The position of the function keyword in the stack.
     *                                           keyword in the stack.
     *
     * @return void
     */
    public abstract function processFunction(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $functionPtr);


}//end class
