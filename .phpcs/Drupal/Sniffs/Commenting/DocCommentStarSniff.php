<?php
/**
 * Drupal_Sniffs_Commenting_DocCommentStarSniff
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that a doc comment block has a doc comment star on every line.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Commenting_DocCommentStarSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_DOC_COMMENT_OPEN_TAG);

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

        $lastLineChecked = $tokens[$stackPtr]['line'];
        for ($i = ($stackPtr + 1); $i < ($tokens[$stackPtr]['comment_closer'] - 1); $i++) {
            // We are only interested in the beginning of the line.
            if ($tokens[$i]['line'] === $lastLineChecked) {
                continue;
            }

            // The first token on the line must be a whitespace followed by a star.
            if ($tokens[$i]['code'] === T_DOC_COMMENT_WHITESPACE) {
                if ($tokens[($i + 1)]['code'] !== T_DOC_COMMENT_STAR) {
                    $error = 'Doc comment star missing';
                    $fix   = $phpcsFile->addFixableError($error, $i, 'StarMissing');
                    if ($fix === true) {
                        $phpcsFile->fixer->replaceToken($i, str_repeat(' ', $tokens[$stackPtr]['column']).'* ');
                    }
                }
            } else if ($tokens[$i]['code'] !== T_DOC_COMMENT_STAR) {
                $error = 'Doc comment star missing';
                $fix   = $phpcsFile->addFixableError($error, $i, 'StarMissing');
                if ($fix === true) {
                    $phpcsFile->fixer->addContentBefore($i, str_repeat(' ', $tokens[$stackPtr]['column']).'* ');
                }
            }

            $lastLineChecked = $tokens[$i]['line'];
        }//end for

    }//end process()


}//end class
