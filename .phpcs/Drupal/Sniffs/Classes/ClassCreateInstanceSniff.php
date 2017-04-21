<?php
/**
 * Class create instance Test.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Class create instance Test.
 *
 * Checks the declaration of the class is correct.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Classes_ClassCreateInstanceSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_NEW);

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

        // Search for an opening parenthesis in the current statement untill the
        // next semicolon.
        $nextParenthesis = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr, null, false, null, true);
        // If there is a parenthesis owner then this is not a constructor call,
        // but rather some array or somehting else.
        if ($nextParenthesis === false || isset($tokens[$nextParenthesis]['parenthesis_owner']) === true) {
            $error       = 'Calling class constructors must always include parentheses';
            $constructor = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($stackPtr + 1), null, true, null, true);
            // We can only invoke the fixer if we know this is a static constructor
            // function call.
            if ($tokens[$constructor]['code'] === T_STRING || $tokens[$constructor]['code'] === T_NS_SEPARATOR) {
                // Scan to the end of possible string\namespace parts.
                $nextConstructorPart = $constructor;
                while (true) {
                    $nextConstructorPart = $phpcsFile->findNext(
                        PHP_CodeSniffer_Tokens::$emptyTokens,
                        ($nextConstructorPart + 1),
                        null,
                        true,
                        null,
                        true
                    );
                    if ($nextConstructorPart === false
                        || ($tokens[$nextConstructorPart]['code'] !== T_STRING
                        && $tokens[$nextConstructorPart]['code'] !== T_NS_SEPARATOR)
                    ) {
                        break;
                    }

                    $constructor = $nextConstructorPart;
                }

                $fix = $phpcsFile->addFixableError($error, $constructor, 'ParenthesisMissing');
                if ($fix === true) {
                    $phpcsFile->fixer->addContent($constructor, '()');
                }
            } else {
                $phpcsFile->addError($error, $stackPtr, 'ParenthesisMissing');
            }//end if
        }//end if

    }//end process()


}//end class
