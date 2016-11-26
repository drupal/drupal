<?php
/**
 * Drupal_Sniffs_ControlStructures_InlineControlStructureSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Drupal_Sniffs_ControlStructures_InlineControlStructureSniff.
 *
 * Verifies that inline control statements are not present. This Sniff overides
 * the generic sniff because Drupal template files may use the alternative
 * syntax for control structures. See
 * http://www.php.net/manual/en/control-structures.alternative-syntax.php
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_ControlStructures_InlineControlStructureSniff
extends Generic_Sniffs_ControlStructures_InlineControlStructureSniff
{


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

        // Check for the alternate syntax for control structures with colons (:).
        if (isset($tokens[$stackPtr]['parenthesis_closer'])) {
            $start = $tokens[$stackPtr]['parenthesis_closer'];
        } else {
            $start = $stackPtr;
        }

        $scopeOpener = $phpcsFile->findNext(T_WHITESPACE, ($start + 1), null, true);
        if ($tokens[$scopeOpener]['code'] === T_COLON) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);

    }//end process()


}//end class
