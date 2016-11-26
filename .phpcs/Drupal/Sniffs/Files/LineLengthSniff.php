<?php
/**
 * Drupal_Sniffs_Files_LineLengthSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks comment lines in the file, and throws warnings if they are over 80
 * characters in length.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Files_LineLengthSniff extends Generic_Sniffs_Files_LineLengthSniff
{

    /**
     * The limit that the length of a line should not exceed.
     *
     * @var int
     */
    public $lineLimit = 80;

    /**
     * The limit that the length of a line must not exceed.
     * But just check the line length of comments....
     *
     * Set to zero (0) to disable.
     *
     * @var int
     */
    public $absoluteLineLimit = 0;


    /**
     * Checks if a line is too long.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param array                $tokens    The token stack.
     * @param int                  $stackPtr  The first token on the next line.
     *
     * @return void
     */
    protected function checkLineLength(PHP_CodeSniffer_File $phpcsFile, $tokens, $stackPtr)
    {
        if (isset(PHP_CodeSniffer_Tokens::$commentTokens[$tokens[($stackPtr - 1)]['code']]) === true) {
            $doc_comment_tag = $phpcsFile->findFirstOnLine(T_DOC_COMMENT_TAG, ($stackPtr - 1));
            if ($doc_comment_tag !== false) {
                // Allow doc comment tags such as long @param tags to exceed the 80
                // character limit.
                return;
            }

            if ($tokens[($stackPtr - 1)]['code'] === T_COMMENT
                && (preg_match('/^[[:space:]]*\/\/ @.+/', $tokens[($stackPtr - 1)]['content']) === 1
                // Allow anything that does not contain spaces (like URLs) to be
                // longer.
                || strpos(trim($tokens[($stackPtr - 1)]['content'], "/ \n"), ' ') === false)
            ) {
                // Allow @link and @see documentation to exceed the 80 character
                // limit.
                return;
            }

            // Code examples between @code and @endcode are allowed to exceed 80
            // characters.
            if (isset($tokens[$stackPtr]) === true && $tokens[$stackPtr]['code'] === T_DOC_COMMENT_WHITESPACE) {
                $tag = $phpcsFile->findPrevious(array(T_DOC_COMMENT_TAG, T_DOC_COMMENT_OPEN_TAG), ($stackPtr - 1));
                if ($tokens[$tag]['content'] === '@code') {
                    return;
                }
            }

            // Drupal 8 annotations can have long translatable descriptions and we
            // allow them to exceed 80 characters.
            if ($tokens[($stackPtr - 2)]['code'] === T_DOC_COMMENT_STRING
                && (strpos($tokens[($stackPtr - 2)]['content'], '@Translation(') !== false
                // Also allow anything without whitespace (like URLs) to exceed 80
                // characters.
                || strpos($tokens[($stackPtr - 2)]['content'], ' ') === false
                // Allow long "Contains ..." comments in @file doc blocks.
                || preg_match('/^Contains [a-zA-Z_\\\\.]+$/', $tokens[($stackPtr - 2)]['content']) === 1
                // Allow long paths or namespaces in annotations such as
                // "list_builder" = "Drupal\rules\Entity\Controller\RulesReactionListBuilder",
                || preg_match('#= ("|\')\S+[\\\\/]\S+("|\'),*$#', $tokens[($stackPtr - 2)]['content']) === 1)
            ) {
                return;
            }

            parent::checkLineLength($phpcsFile, $tokens, $stackPtr);
        }//end if

    }//end checkLineLength()


    /**
     * Returns the length of a defined line.
     *
     * @return integer
     */
    public function getLineLength(PHP_CodeSniffer_File $phpcsFile, $currentLine)
    {
        $tokens = $phpcsFile->getTokens();

        $tokenCount         = 0;
        $currentLineContent = '';

        $trim = (strlen($phpcsFile->eolChar) * -1);
        for (; $tokenCount < $phpcsFile->numTokens; $tokenCount++) {
            if ($tokens[$tokenCount]['line'] === $currentLine) {
                $currentLineContent .= $tokens[$tokenCount]['content'];
            }
        }

        return strlen($currentLineContent);

    }//end getLineLength()


}//end class
