<?php
/**
 * Drupal_Sniffs_Classes_InterfaceNameSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that interface names end with "Interface".
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Classes_InterfaceNameSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_INTERFACE);

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
        $tokens  = $phpcsFile->getTokens();
        $namePtr = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        $name    = $tokens[$namePtr]['content'];
        if (substr($name, -9) !== 'Interface') {
            $warn = 'Interface names should always have the suffix "Interface"';
            $phpcsFile->addWarning($warn, $namePtr, 'InterfaceSuffix');
        }

    }//end process()


}//end class
