<?php
/**
 * Drupal_Sniffs_Files_TxtFileLineLengthSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @author   Klaus Purer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Drupal_Sniffs_Files_TxtFileLineLengthSniff.
 *
 * Checks all lines in a *.txt or *.md file and throws warnings if they are over 80
 * characters in length.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @author   Klaus Purer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Files_TxtFileLineLengthSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_INLINE_HTML);

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
        $fileExtension = strtolower(substr($phpcsFile->getFilename(), -3));
        if ($fileExtension === 'txt' || $fileExtension === '.md') {
            $tokens = $phpcsFile->getTokens();

            $content    = rtrim($tokens[$stackPtr]['content']);
            $lineLength = mb_strlen($content, 'UTF-8');
            if ($lineLength > 80) {
                $data    = array(
                            80,
                            $lineLength,
                           );
                $warning = 'Line exceeds %s characters; contains %s characters';
                $phpcsFile->addWarning($warning, $stackPtr, 'TooLong', $data);
            }
        }

    }//end process()


}//end class
