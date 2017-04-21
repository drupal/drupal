<?php
/**
 * Class Declaration Test.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Class Declaration Test.
 *
 * Checks the declaration of the class is correct.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Classes_ClassDeclarationSniff extends PSR2_Sniffs_Classes_ClassDeclarationSniff
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
     * @param integer              $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens    = $phpcsFile->getTokens();
        $errorData = array(strtolower($tokens[$stackPtr]['content']));

        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            $error = 'Possible parse error: %s missing opening or closing brace';
            $phpcsFile->addWarning($error, $stackPtr, 'MissingBrace', $errorData);
            return;
        }

        $openingBrace = $tokens[$stackPtr]['scope_opener'];

        $next = $phpcsFile->findNext(T_WHITESPACE, ($openingBrace + 1), null, true);
        if ($tokens[$next]['line'] === $tokens[$openingBrace]['line'] && $tokens[$next]['code'] !== T_CLOSE_CURLY_BRACKET) {
            $error = 'Opening brace must be the last content on the line';
            $fix   = $phpcsFile->addFixableError($error, $openingBrace, 'ContentAfterBrace');
            if ($fix === true) {
                $phpcsFile->fixer->addNewline($openingBrace);
            }
        }

        $previous        = $phpcsFile->findPrevious(T_WHITESPACE, ($openingBrace - 1), null, true);
        $decalrationLine = $tokens[$previous]['line'];
        $braceLine       = $tokens[$openingBrace]['line'];

        $lineDifference = ($braceLine - $decalrationLine);

        if ($lineDifference > 0) {
            $error = 'Opening brace should be on the same line as the declaration';
            $fix   = $phpcsFile->addFixableError($error, $openingBrace, 'BraceOnNewLine');
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                for ($i = ($previous + 1); $i < $openingBrace; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->addContent($previous, ' ');
                $phpcsFile->fixer->endChangeset();
            }

            return;
        }

        $openingBrace = $tokens[$stackPtr]['scope_opener'];
        if ($tokens[($openingBrace - 1)]['code'] !== T_WHITESPACE) {
            $length = 0;
        } else if ($tokens[($openingBrace - 1)]['content'] === "\t") {
            $length = '\t';
        } else {
            $length = strlen($tokens[($openingBrace - 1)]['content']);
        }

        if ($length !== 1) {
            $error = 'Expected 1 space before opening brace; found %s';
            $data  = array($length);
            $fix   = $phpcsFile->addFixableError($error, $openingBrace, 'SpaceBeforeBrace', $data);
            if ($fix === true) {
                if ($length === 0) {
                    $phpcsFile->fixer->replaceToken(($openingBrace), ' {');
                } else {
                    $phpcsFile->fixer->replaceToken(($openingBrace - 1), ' ');
                }
            }
        }

        // Now call the open spacing method from PSR2.
        $this->processOpen($phpcsFile, $stackPtr);

        $this->processClose($phpcsFile, $stackPtr);

    }//end process()


    /**
     * Processes the closing section of a class declaration.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function processClose(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Just in case.
        if (isset($tokens[$stackPtr]['scope_closer']) === false) {
            return;
        }

        // Check that the closing brace comes right after the code body.
        $closeBrace  = $tokens[$stackPtr]['scope_closer'];
        $prevContent = $phpcsFile->findPrevious(T_WHITESPACE, ($closeBrace - 1), null, true);
        if ($prevContent !== $tokens[$stackPtr]['scope_opener']
            && $tokens[$prevContent]['line'] !== ($tokens[$closeBrace]['line'] - 2)
            // If the class only contains a comment no extra line is needed.
            && isset(PHP_CodeSniffer_Tokens::$commentTokens[$tokens[$prevContent]['code']]) === false
        ) {
            $error = 'The closing brace for the %s must have an empty line before it';
            $data  = array($tokens[$stackPtr]['content']);
            $fix   = $phpcsFile->addFixableError($error, $closeBrace, 'CloseBraceAfterBody', $data);

            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                for ($i = ($prevContent + 1); $i < $closeBrace; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->replaceToken($closeBrace, $phpcsFile->eolChar.$phpcsFile->eolChar.$tokens[$closeBrace]['content']);

                $phpcsFile->fixer->endChangeset();
            }
        }//end if

        // Check the closing brace is on it's own line, but allow
        // for comments like "//end class".
        $nextContent = $phpcsFile->findNext(T_COMMENT, ($closeBrace + 1), null, true);
        if ($tokens[$nextContent]['content'] !== $phpcsFile->eolChar
            && $tokens[$nextContent]['line'] === $tokens[$closeBrace]['line']
        ) {
            $type  = strtolower($tokens[$stackPtr]['content']);
            $error = 'Closing %s brace must be on a line by itself';
            $data  = array($tokens[$stackPtr]['content']);
            $phpcsFile->addError($error, $closeBrace, 'CloseBraceSameLine', $data);
        }

    }//end processClose()


}//end class
