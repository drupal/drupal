<?php
/**
 * Parses and verifies the class doc comment.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that comment doc blocks exist on classes, interfaces and traits. Largely
 * copied from Squiz_Sniffs_Commenting_ClassCommentSniff.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Commenting_ClassCommentSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
                T_CLASS,
                T_INTERFACE,
                T_TRAIT,
               );

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
        $find   = PHP_CodeSniffer_Tokens::$methodPrefixes;
        $find[] = T_WHITESPACE;
        $name   = $tokens[$stackPtr]['content'];

        $commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);
        if ($tokens[$commentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG
            && $tokens[$commentEnd]['code'] !== T_COMMENT
        ) {
            $fix = $phpcsFile->addFixableError('Missing %s doc comment', $stackPtr, 'Missing', array($name));
            if ($fix === true) {
                $phpcsFile->fixer->addContent($commentEnd, "\n/**\n *\n */");
            }

            return;
        }

        // Try and determine if this is a file comment instead of a class comment.
        // We assume that if this is the first comment after the open PHP tag, then
        // it is most likely a file comment instead of a class comment.
        if ($tokens[$commentEnd]['code'] === T_DOC_COMMENT_CLOSE_TAG) {
            $start = ($tokens[$commentEnd]['comment_opener'] - 1);
        } else {
            $start = ($commentEnd - 1);
        }

        $prev = $phpcsFile->findPrevious(T_WHITESPACE, $start, null, true);
        if ($tokens[$prev]['code'] === T_OPEN_TAG) {
            $prevOpen = $phpcsFile->findPrevious(T_OPEN_TAG, ($prev - 1));
            if ($prevOpen === false) {
                // This is a comment directly after the first open tag,
                // so probably a file comment.
                $fix = $phpcsFile->addFixableError('Missing %s doc comment', $stackPtr, 'Missing', array($name));
                if ($fix === true) {
                    $phpcsFile->fixer->addContent($commentEnd, "\n/**\n *\n */");
                }

                return;
            }
        }

        if ($tokens[$commentEnd]['code'] === T_COMMENT) {
            $fix = $phpcsFile->addFixableError('You must use "/**" style comments for a %s comment', $stackPtr, 'WrongStyle', array($name));
            if ($fix === true) {
                // Convert the comment into a doc comment.
                $phpcsFile->fixer->beginChangeset();
                $comment = '';
                for ($i = $commentEnd; $tokens[$i]['code'] === T_COMMENT; $i--) {
                    $comment = ' *'.ltrim($tokens[$i]['content'], '/* ').$comment;
                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->replaceToken($commentEnd, "/**\n".rtrim($comment, "*/\n")."\n */");
                $phpcsFile->fixer->endChangeset();
            }

            return;
        }

        if ($tokens[$commentEnd]['line'] !== ($tokens[$stackPtr]['line'] - 1)) {
            $error = 'There must be exactly one newline after the %s comment';
            $fix   = $phpcsFile->addFixableError($error, $commentEnd, 'SpacingAfter', array($name));
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                for ($i = ($commentEnd + 1); $tokens[$i]['code'] === T_WHITESPACE && $i < $stackPtr; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->addContent($commentEnd, "\n");
                $phpcsFile->fixer->endChangeset();
            }
        }

    }//end process()


}//end class
