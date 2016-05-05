<?php
/**
 * Verifies that control statements conform to their coding standards.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that "elseif" is used instead of "else if".
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_ControlStructures_ElseIfSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_ELSE);

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

        $tokens = $phpcsFile->getTokens();

        $nextNonWhiteSpace = $phpcsFile->findNext(
            T_WHITESPACE,
            ($stackPtr + 1),
            null,
            true,
            null,
            true
        );

        if ($tokens[$nextNonWhiteSpace]['code'] === T_IF) {
            $fix = $phpcsFile->addFixableError('Use "elseif" in place of "else if"', $nextNonWhiteSpace);
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->replaceToken($stackPtr, 'elseif');
                for ($i = ($stackPtr + 1); $i < $nextNonWhiteSpace; $i++) {
                    if ($tokens[$i]['code'] === T_WHITESPACE) {
                        $phpcsFile->fixer->replaceToken($i, '');
                    }
                }

                $phpcsFile->fixer->replaceToken($nextNonWhiteSpace, '');
                $phpcsFile->fixer->endChangeset();
            }
        }

    }//end process()


}//end class
