<?php
/**
 * Drupal_Sniffs_Semantics_InstallHooksSniff
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that hook_disable(), hook_enable(), hook_install(), hook_uninstall(),
 * hook_requirements() and hook_schema() are not defined in the module file.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Semantics_InstallHooksSniff extends Drupal_Sniffs_Semantics_FunctionDefinition
{


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
    public function processFunction(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $functionPtr)
    {
        $fileExtension = strtolower(substr($phpcsFile->getFilename(), -6));
        // Only check in *.module files.
        if ($fileExtension !== 'module') {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $fileName = substr(basename($phpcsFile->getFilename()), 0, -7);
        if ($tokens[$stackPtr]['content'] === ($fileName.'_install')
            || $tokens[$stackPtr]['content'] === ($fileName.'_uninstall')
            || $tokens[$stackPtr]['content'] === ($fileName.'_requirements')
            || $tokens[$stackPtr]['content'] === ($fileName.'_schema')
            || $tokens[$stackPtr]['content'] === ($fileName.'_enable')
            || $tokens[$stackPtr]['content'] === ($fileName.'_disable')
        ) {
            $error = '%s() is an installation hook and must be declared in an install file';
            $data  = array($tokens[$stackPtr]['content']);
            $phpcsFile->addError($error, $stackPtr, 'InstallHook', $data);
        }

    }//end processFunction()


}//end class
