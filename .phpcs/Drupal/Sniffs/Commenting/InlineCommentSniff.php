<?php
/**
 * PHP_CodeSniffer_Sniffs_Drupal_Commenting_InlineCommentSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * PHP_CodeSniffer_Sniffs_Drupal_Commenting_InlineCommentSniff.
 *
 * Checks that no perl-style comments are used. Checks that inline comments ("//")
 * have a space after //, start capitalized and end with proper punctuation.
 * Largely copied from Squiz_Sniffs_Commenting_InlineCommentSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Commenting_InlineCommentSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array(
                                   'PHP',
                                   'JS',
                                  );


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
                T_COMMENT,
                T_DOC_COMMENT_OPEN_TAG,
               );

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

        // If this is a function/class/interface doc block comment, skip it.
        // We are only interested in inline doc block comments, which are
        // not allowed.
        if ($tokens[$stackPtr]['code'] === T_DOC_COMMENT_OPEN_TAG) {
            $nextToken = $phpcsFile->findNext(
                PHP_CodeSniffer_Tokens::$emptyTokens,
                ($stackPtr + 1),
                null,
                true
            );

            $ignore = array(
                       T_CLASS,
                       T_INTERFACE,
                       T_TRAIT,
                       T_FUNCTION,
                       T_CLOSURE,
                       T_PUBLIC,
                       T_PRIVATE,
                       T_PROTECTED,
                       T_FINAL,
                       T_STATIC,
                       T_ABSTRACT,
                       T_CONST,
                       T_PROPERTY,
                      );

            // Also ignore all doc blocks defined in the outer scope (no scope
            // conditions are set).
            if (in_array($tokens[$nextToken]['code'], $ignore) === true
                || empty($tokens[$stackPtr]['conditions']) === true
            ) {
                return;
            }

            if ($phpcsFile->tokenizerType === 'JS') {
                // We allow block comments if a function or object
                // is being assigned to a variable.
                $ignore    = PHP_CodeSniffer_Tokens::$emptyTokens;
                $ignore[]  = T_EQUAL;
                $ignore[]  = T_STRING;
                $ignore[]  = T_OBJECT_OPERATOR;
                $nextToken = $phpcsFile->findNext($ignore, ($nextToken + 1), null, true);
                if ($tokens[$nextToken]['code'] === T_FUNCTION
                    || $tokens[$nextToken]['code'] === T_CLOSURE
                    || $tokens[$nextToken]['code'] === T_OBJECT
                    || $tokens[$nextToken]['code'] === T_PROTOTYPE
                ) {
                    return;
                }
            }

            $prevToken = $phpcsFile->findPrevious(
                PHP_CodeSniffer_Tokens::$emptyTokens,
                ($stackPtr - 1),
                null,
                true
            );

            if ($tokens[$prevToken]['code'] === T_OPEN_TAG) {
                return;
            }

            // Inline doc blocks are allowed in JSDoc.
            if ($tokens[$stackPtr]['content'] === '/**' && $phpcsFile->tokenizerType !== 'JS') {
                // The only exception to inline doc blocks is the /** @var */
                // declaration.
                $content = $phpcsFile->getTokensAsString($stackPtr, ($tokens[$stackPtr]['comment_closer'] - $stackPtr + 1));
                if (preg_match('#^/\*\* @var [a-zA-Z0-9_\\\\\[\]|]+ \$[a-zA-Z0-9_]+ \*/$#', $content) !== 1) {
                    $error = 'Inline doc block comments are not allowed; use "/* Comment */" or "// Comment" instead';
                    $phpcsFile->addError($error, $stackPtr, 'DocBlock');
                }
            }
        }//end if

        if ($tokens[$stackPtr]['content']{0} === '#') {
            $error = 'Perl-style comments are not allowed; use "// Comment" instead';
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'WrongStyle');
            if ($fix === true) {
                $comment = ltrim($tokens[$stackPtr]['content'], "# \t");
                $phpcsFile->fixer->replaceToken($stackPtr, "// $comment");
            }
        }

        // We don't want end of block comments. If the last comment is a closing
        // curly brace.
        $previousContent = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($tokens[$previousContent]['line'] === $tokens[$stackPtr]['line']) {
            if ($tokens[$previousContent]['code'] === T_CLOSE_CURLY_BRACKET) {
                return;
            }

            // Special case for JS files.
            if ($tokens[$previousContent]['code'] === T_COMMA
                || $tokens[$previousContent]['code'] === T_SEMICOLON
            ) {
                $lastContent = $phpcsFile->findPrevious(T_WHITESPACE, ($previousContent - 1), null, true);
                if ($tokens[$lastContent]['code'] === T_CLOSE_CURLY_BRACKET) {
                    return;
                }
            }
        }

        $comment = rtrim($tokens[$stackPtr]['content']);

        // Only want inline comments.
        if (substr($comment, 0, 2) !== '//') {
            return;
        }

        // Ignore code example lines.
        if ($this->isInCodeExample($phpcsFile, $stackPtr) === true) {
            return;
        }

        // Verify the indentation of this comment line.
        $this->processIndentation($phpcsFile, $stackPtr);

        // If the current line starts with a tag such as "@see" then the trailing dot
        // rules and upper case start rules don't apply.
        if (strpos(trim(substr($tokens[$stackPtr]['content'], 2)), '@') === 0) {
            return;
        }

        // The below section determines if a comment block is correctly capitalised,
        // and ends in a full-stop. It will find the last comment in a block, and
        // work its way up.
        $nextComment = $phpcsFile->findNext(array(T_COMMENT), ($stackPtr + 1), null, false);
        if (($nextComment !== false)
            && (($tokens[$nextComment]['line']) === ($tokens[$stackPtr]['line'] + 1))
            // A tag such as @todo means a separate comment block.
            && strpos(trim(substr($tokens[$nextComment]['content'], 2)), '@') !== 0
        ) {
            return;
        }

        $topComment  = $stackPtr;
        $lastComment = $stackPtr;
        while (($topComment = $phpcsFile->findPrevious(array(T_COMMENT), ($lastComment - 1), null, false)) !== false) {
            if ($tokens[$topComment]['line'] !== ($tokens[$lastComment]['line'] - 1)) {
                break;
            }

            $lastComment = $topComment;
        }

        $topComment  = $lastComment;
        $commentText = '';

        for ($i = $topComment; $i <= $stackPtr; $i++) {
            if ($tokens[$i]['code'] === T_COMMENT) {
                $commentText .= trim(substr($tokens[$i]['content'], 2));
            }
        }

        if ($commentText === '') {
            $error = 'Blank comments are not allowed';
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'Empty');
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken($stackPtr, '');
            }

            return;
        }

        $words = preg_split('/\s+/', $commentText);
        if (preg_match('|\p{Lu}|u', $commentText[0]) === 0 && $commentText[0] !== '@') {
            // Allow special lower cased words that contain non-alpha characters
            // (function references, machine names with underscores etc.).
            $matches = array();
            preg_match('/[a-z]+/', $words[0], $matches);
            if (isset($matches[0]) && $matches[0] === $words[0]) {
                $error = 'Inline comments must start with a capital letter';
                $fix   = $phpcsFile->addFixableError($error, $topComment, 'NotCapital');
                if ($fix === true) {
                    $newComment = preg_replace("/$words[0]/", ucfirst($words[0]), $tokens[$topComment]['content'], 1);
                    $phpcsFile->fixer->replaceToken($topComment, $newComment);
                }
            }
        }

        $commentCloser   = $commentText[(strlen($commentText) - 1)];
        $acceptedClosers = array(
                            'full-stops'        => '.',
                            'exclamation marks' => '!',
                            'colons'            => ':',
                            'question marks'    => '?',
                            'or closing parentheses' => ')',
                           );

        // Allow @tag style comments without punctuation.
        if (in_array($commentCloser, $acceptedClosers) === false && $commentText[0] !== '@') {
            // Allow special last words like URLs or function references
            // without punctuation.
            $lastWord = $words[(count($words) - 1)];
            $matches  = array();
            preg_match('/https?:\/\/.+/', $lastWord, $matches);
            $isUrl = isset($matches[0]) === true;
            preg_match('/[$a-zA-Z_]+\([$a-zA-Z_]*\)/', $lastWord, $matches);
            $isFunction = isset($matches[0]) === true;
            if (!$isUrl && !$isFunction) {
                $error = 'Inline comments must end in %s';
                $ender = '';
                foreach ($acceptedClosers as $closerName => $symbol) {
                    $ender .= ' '.$closerName.',';
                }

                $ender = trim($ender, ' ,');
                $data  = array($ender);
                $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'InvalidEndChar', $data);
                if ($fix === true) {
                    $newContent = preg_replace('/(\s+)$/', '.$1', $tokens[$stackPtr]['content']);
                    $phpcsFile->fixer->replaceToken($stackPtr, $newContent);
                }
            }
        }//end if

        // Finally, the line below the last comment cannot be empty if this inline
        // comment is on a line by itself.
        if ($tokens[$previousContent]['line'] < $tokens[$stackPtr]['line'] && ($stackPtr + 1) < $phpcsFile->numTokens) {
            for ($i = ($stackPtr + 1); $i < $phpcsFile->numTokens; $i++) {
                if ($tokens[$i]['line'] === ($tokens[$stackPtr]['line'] + 1)) {
                    if ($tokens[$i]['code'] !== T_WHITESPACE || $i === ($phpcsFile->numTokens - 1)) {
                        return;
                    }
                } else if ($tokens[$i]['line'] > ($tokens[$stackPtr]['line'] + 1)) {
                    break;
                }
            }

            $warning = 'There must be no blank line following an inline comment';
            $fix     = $phpcsFile->addFixableWarning($warning, $stackPtr, 'SpacingAfter');
            if ($fix === true) {
                $next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
                if ($next === ($phpcsFile->numTokens - 1)) {
                    return;
                }

                $phpcsFile->fixer->beginChangeset();
                for ($i = ($stackPtr + 1); $i < $next; $i++) {
                    if ($tokens[$i]['line'] === $tokens[$next]['line']) {
                        break;
                    }

                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->endChangeset();
            }
        }//end if

    }//end process()


    /**
     * Determines if a comment line is part of an @code/@endcode example.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return boolean Returns true if the comment line is within a @code block,
     *                 false otherwise.
     */
    protected function isInCodeExample(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens      = $phpcsFile->getTokens();
        $prevComment = $stackPtr;
        $lastComment = $stackPtr;
        while (($prevComment = $phpcsFile->findPrevious(array(T_COMMENT), ($lastComment - 1), null, false)) !== false) {
            if ($tokens[$prevComment]['line'] !== ($tokens[$lastComment]['line'] - 1)) {
                return false;
            }

            if ($tokens[$prevComment]['content'] === '// @code'.$phpcsFile->eolChar) {
                return true;
            }

            if ($tokens[$prevComment]['content'] === '// @endcode'.$phpcsFile->eolChar) {
                return false;
            }

            $lastComment = $prevComment;
        }

        return false;

    }//end isInCodeExample()


    /**
     * Checks the indentation level of the comment contents.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    protected function processIndentation(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens     = $phpcsFile->getTokens();
        $comment    = rtrim($tokens[$stackPtr]['content']);
        $spaceCount = 0;
        $tabFound   = false;

        $commentLength = strlen($comment);
        for ($i = 2; $i < $commentLength; $i++) {
            if ($comment[$i] === "\t") {
                $tabFound = true;
                break;
            }

            if ($comment[$i] !== ' ') {
                break;
            }

            $spaceCount++;
        }

        $fix = false;
        if ($tabFound === true) {
            $error = 'Tab found before comment text; expected "// %s" but found "%s"';
            $data  = array(
                      ltrim(substr($comment, 2)),
                      $comment,
                     );
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'TabBefore', $data);
        } else if ($spaceCount === 0 && strlen($comment) > 2) {
            $error = 'No space found before comment text; expected "// %s" but found "%s"';
            $data  = array(
                      substr($comment, 2),
                      $comment,
                     );
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'NoSpaceBefore', $data);
        }//end if

        if ($fix === true) {
            $newComment = '// '.ltrim($tokens[$stackPtr]['content'], "/\t ");
            $phpcsFile->fixer->replaceToken($stackPtr, $newComment);
        }

        if ($spaceCount > 1) {
            // Check if there is a comment on the previous line that justifies the
            // indentation.
            $prevComment = $phpcsFile->findPrevious(array(T_COMMENT), ($stackPtr - 1), null, false);
            if (($prevComment !== false) && (($tokens[$prevComment]['line']) === ($tokens[$stackPtr]['line'] - 1))) {
                $prevCommentText = rtrim($tokens[$prevComment]['content']);
                $prevSpaceCount  = 0;
                for ($i = 2; $i < strlen($prevCommentText); $i++) {
                    if ($prevCommentText[$i] !== ' ') {
                        break;
                    }

                    $prevSpaceCount++;
                }

                if ($spaceCount > $prevSpaceCount && $prevSpaceCount > 0) {
                    // A previous comment could be a list item or @todo.
                    $indentationStarters = array(
                                            '-',
                                            '@todo',
                                           );
                    $words = preg_split('/\s+/', $prevCommentText);
                    if (in_array($words[1], $indentationStarters) === true) {
                        if ($spaceCount !== ($prevSpaceCount + 2)) {
                            $error = 'Comment indentation error after %s element, expected %s spaces';
                            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'SpacingBefore', array($words[1], $prevSpaceCount + 2));
                            if ($fix === true) {
                                $newComment = '//'.str_repeat(' ', ($prevSpaceCount + 2)).ltrim($tokens[$stackPtr]['content'], "/\t ");
                                $phpcsFile->fixer->replaceToken($stackPtr, $newComment);
                            }
                        }
                    } else {
                        $error = 'Comment indentation error, expected only %s spaces';
                        $phpcsFile->addError($error, $stackPtr, 'SpacingBefore', array($prevSpaceCount));
                    }
                }//end if
            } else {
                $error = '%s spaces found before inline comment; expected "// %s" but found "%s"';
                $data  = array(
                          $spaceCount,
                          substr($comment, (2 + $spaceCount)),
                          $comment,
                         );
                $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'SpacingBefore', $data);
                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken($stackPtr, '// '.substr($comment, (2 + $spaceCount)).$phpcsFile->eolChar);
                }
            }//end if
        }//end if

    }//end processIndentation()


}//end class
