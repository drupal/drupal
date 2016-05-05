<?php
/**
 * Drupal_Sniffs_Semantics_FunctionTSniff
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Check the usage of the t() function to not escape translateable strings with back
 * slashes. Also checks that the first argument does not use string concatenation.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Semantics_FunctionTSniff extends Drupal_Sniffs_Semantics_FunctionCall
{

    /**
     * We also want to catch $this->t() calls in Drupal 8.
     *
     * @var bool
     */
    protected $includeMethodCalls = true;

    /**
     * Returns an array of function names this test wants to listen for.
     *
     * @return array
     */
    public function registerFunctionNames()
    {
        return array('t');

    }//end registerFunctionNames()


    /**
     * Processes this function call.
     *
     * @param PHP_CodeSniffer_File $phpcsFile
     *   The file being scanned.
     * @param int                  $stackPtr
     *   The position of the function call in the stack.
     * @param int                  $openBracket
     *   The position of the opening parenthesis in the stack.
     * @param int                  $closeBracket
     *   The position of the closing parenthesis in the stack.
     *
     * @return void
     */
    public function processFunctionCall(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr,
        $openBracket,
        $closeBracket
    ) {
        $tokens   = $phpcsFile->getTokens();
        $argument = $this->getArgument(1);

        if ($argument === false) {
            $error = 'Empty calls to t() are not allowed';
            $phpcsFile->addError($error, $stackPtr, 'EmptyT');
            return;
        }

        if ($tokens[$argument['start']]['code'] !== T_CONSTANT_ENCAPSED_STRING) {
            // Not a translatable string literal.
            $warning = 'Only string literals should be passed to t() where possible';
            $phpcsFile->addWarning($warning, $argument['start'], 'NotLiteralString');
            return;
        }

        $string = $tokens[$argument['start']]['content'];
        if ($string === '""' || $string === "''") {
            $warning = 'Do not pass empty strings to t()';
            $phpcsFile->addWarning($warning, $argument['start'], 'EmptyString');
            return;
        }

        $concatAfter = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($closeBracket + 1), null, true, null, true);
        if ($concatAfter !== false && $tokens[$concatAfter]['code'] === T_STRING_CONCAT) {
            $stringAfter = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($concatAfter + 1), null, true, null, true);
            if ($stringAfter !== false
                && $tokens[$stringAfter]['code'] === T_CONSTANT_ENCAPSED_STRING
                && strpos($tokens[$stringAfter]['content'], '<') === false
            ) {
                $warning = 'Do not concatenate strings to translatable strings, they should be part of the t() argument and you should use placeholders';
                $phpcsFile->addWarning($warning, $stringAfter, 'ConcatString');
            }
        }

        $lastChar = substr($string, -1);
        if ($lastChar === '"' || $lastChar === "'") {
            $message = substr($string, 1, -1);
            if ($message !== trim($message)) {
                $warning = 'Translatable strings must not begin or end with white spaces, use placeholders with t() for variables';
                $phpcsFile->addWarning($warning, $argument['start'], 'WhiteSpace');
            }
        }

        $concatFound = $phpcsFile->findNext(T_STRING_CONCAT, $argument['start'], $argument['end']);
        if ($concatFound !== false) {
            $error = 'Concatenating translatable strings is not allowed, use placeholders instead and only one string literal';
            $phpcsFile->addError($error, $concatFound, 'Concat');
        }

        // Check if there is a backslash escaped single quote in the string and
        // if the string makes use of double quotes.
        if ($string{0} === "'" && strpos($string, "\'") !== false
            && strpos($string, '"') === false
        ) {
            $warn = 'Avoid backslash escaping in translatable strings when possible, use "" quotes instead';
            $phpcsFile->addWarning($warn, $argument['start'], 'BackslashSingleQuote');
            return;
        }

        if ($string{0} === '"' && strpos($string, '\"') !== false
            && strpos($string, "'") === false
        ) {
            $warn = "Avoid backslash escaping in translatable strings when possible, use '' quotes instead";
            $phpcsFile->addWarning($warn, $argument['start'], 'BackslashDoubleQuote');
        }

    }//end processFunctionCall()


}//end class
