<?php
/**
 * Drupal_Sniffs_NamingConventions_ValidVariableNameSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Drupal_Sniffs_NamingConventions_ValidVariableNameSniff.
 *
 * Checks the naming of member variables.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_NamingConventions_ValidVariableNameSniff

    extends PHP_CodeSniffer_Standards_AbstractVariableSniff
{


    /**
     * Processes class member variables.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    protected function processMemberVar(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $memberProps = $phpcsFile->getMemberProperties($stackPtr);
        if (empty($memberProps) === true) {
            return;
        }

        $memberName = ltrim($tokens[$stackPtr]['content'], '$');

        if (strpos($memberName, '_') === false) {
            return;
        }

        // Check if the class extends another class and get the name of the class
        // that is extended.
        if (empty($tokens[$stackPtr]['conditions']) === false) {
            $classPtr = key($tokens[$stackPtr]['conditions']);
            $extendsPtr = $phpcsFile->findNext(T_EXTENDS, ($classPtr + 1), $tokens[$classPtr]['scope_opener']);
            if ($extendsPtr !== false) {
                $extendsNamePtr = $phpcsFile->findNext(T_STRING, ($extendsPtr + 1), $tokens[$classPtr]['scope_opener']);

                // Special case config entities: those are allowed to have
                // underscores in their class property names. If a class extends
                // something like ConfigEntityBase then we consider it a config
                // entity class and allow underscores.
                if ($extendsNamePtr !== false
                    && strpos($tokens[$extendsNamePtr]['content'], 'ConfigEntityBase') !== false
                ) {
                    return;
                }
            }
        }

        $error = 'Class property %s should use lowerCamel naming without underscores';
        $data  = array($tokens[$stackPtr]['content']);
        $phpcsFile->addError($error, $stackPtr, 'LowerCamelName', $data);

    }//end processMemberVar()


    /**
     * Processes normal variables.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where this token was found.
     * @param int                  $stackPtr  The position where the token was found.
     *
     * @return void
     */
    protected function processVariable(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $varName = ltrim($tokens[$stackPtr]['content'], '$');

        $phpReservedVars = array(
                            '_SERVER',
                            '_GET',
                            '_POST',
                            '_REQUEST',
                            '_SESSION',
                            '_ENV',
                            '_COOKIE',
                            '_FILES',
                            'GLOBALS',
                           );

        // If it's a php reserved var, then its ok.
        if (in_array($varName, $phpReservedVars) === true) {
            return;
        }

        // If it is a static public variable of a class, then its ok.
        if ($tokens[($stackPtr - 1)]['code'] === T_DOUBLE_COLON) {
            return;
        }

        if (preg_match('/^[A-Z]/', $varName) === 1) {
            $error = "Variable \"$varName\" starts with a capital letter, but only \$lowerCamelCase or \$snake_case is allowed";
            $phpcsFile->addError($error, $stackPtr, 'LowerStart');
        }

    }//end processVariable()


    /**
     * Processes variables in double quoted strings.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where this token was found.
     * @param int                  $stackPtr  The position where the token was found.
     *
     * @return void
     */
    protected function processVariableInString(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        // We don't care about variables in strings.

    }//end processVariableInString()


}//end class
