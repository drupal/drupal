<?php
/**
 * Drupal_Sniffs_NamingConventions_ValidClassNameSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Drupal_Sniffs_NamingConventions_ValidClassNameSniff.
 *
 * Ensures class and interface names start with a capital letter
 * and do not use _ separators.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.2.0RC3
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_NamingConventions_ValidClassNameSniff implements PHP_CodeSniffer_Sniff
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
               );

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The current file being processed.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $className = $phpcsFile->findNext(T_STRING, $stackPtr);
        $name      = trim($tokens[$className]['content']);
        $errorData = array(ucfirst($tokens[$stackPtr]['content']));

        // Make sure the first letter is a capital.
        if (preg_match('|^[A-Z]|', $name) === 0) {
            $error = '%s name must begin with a capital letter';
            $phpcsFile->addError($error, $stackPtr, 'StartWithCaptial', $errorData);
        }

        // Search for underscores.
        if (strpos($name, '_') !== false) {
            $error = '%s name must use UpperCamel naming without underscores';
            $phpcsFile->addError($error, $stackPtr, 'NoUnderscores', $errorData);
        }

    }//end process()


}//end class
