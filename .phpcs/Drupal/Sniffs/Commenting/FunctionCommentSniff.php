<?php
/**
 * Parses and verifies the doc comments for functions.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Parses and verifies the doc comments for functions. Largely copied from
 * Squiz_Sniffs_Commenting_FunctionCommentSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Commenting_FunctionCommentSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * A map of invalid data types to valid ones for param and return documentation.
     *
     * @var array
     */
    protected $invalidTypes = array(
                               'Array'    => 'array',
                               'array()'  => 'array',
                               'boolean'  => 'bool',
                               'Boolean'  => 'bool',
                               'integer'  => 'int',
                               'str'      => 'string',
                               'stdClass' => 'object',
                               'number'   => 'int',
                               'String'   => 'string',
                               'type'     => 'string or int or object...',
                              );

    /**
     * An array of variable types for param/var we will check.
     *
     * @var array(string)
     */
    public $allowedTypes = array(
                            'array',
                            'bool',
                            'float',
                            'int',
                            'mixed',
                            'object',
                            'string',
                            'resource',
                            'callable',
                           );


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_FUNCTION);

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

        $commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);
        if ($tokens[$commentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG
            && $tokens[$commentEnd]['code'] !== T_COMMENT
        ) {
            $fix = $phpcsFile->addFixableError('Missing function doc comment', $stackPtr, 'Missing');
            if ($fix === true) {
                $before = $phpcsFile->findNext(T_WHITESPACE, ($commentEnd + 1), ($stackPtr + 1), true);
                $phpcsFile->fixer->addContentBefore($before, "/**\n *\n */\n");
            }

            return;
        }

        // If the comment is the first comment in the file then this is a file
        // comment, not a function comment.
        $fileComment = $phpcsFile->findNext(T_WHITESPACE, 1, null, true);
        if ($fileComment === $commentEnd
            || ($tokens[$commentEnd]['code'] === T_DOC_COMMENT_CLOSE_TAG
            && $tokens[$commentEnd]['comment_opener'] === $fileComment)
        ) {
            $fix = $phpcsFile->addFixableError('Missing function doc comment, only found file comment', $stackPtr, 'MissingFile');
            if ($fix === true) {
                $before = $phpcsFile->findNext(T_WHITESPACE, ($commentEnd + 1), ($stackPtr + 1), true);
                $phpcsFile->fixer->addContentBefore($before, "/**\n *\n */\n");
            }

            return;
        }

        if ($tokens[$commentEnd]['code'] === T_COMMENT) {
            $fix = $phpcsFile->addFixableError('You must use "/**" style comments for a function comment', $stackPtr, 'WrongStyle');
            if ($fix === true) {
                // Convert the comment into a doc comment.
                $phpcsFile->fixer->beginChangeset();
                $comment = '';
                for ($i = $commentEnd; $tokens[$i]['code'] === T_COMMENT; $i--) {
                    $comment = ' *'.ltrim($tokens[$i]['content'], '/* ').$comment;
                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->replaceToken($commentEnd, "/**\n".rtrim($comment, "*/\n")."\n */\n");
                $phpcsFile->fixer->endChangeset();
            }

            return;
        }

        if ($tokens[$commentEnd]['line'] !== ($tokens[$stackPtr]['line'] - 1)) {
            $error = 'There must be no blank lines after the function comment';
            $fix   = $phpcsFile->addFixableError($error, $commentEnd, 'SpacingAfter');
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken(($commentEnd + 1), '');
            }
        }

        $commentStart = $tokens[$commentEnd]['comment_opener'];
        foreach ($tokens[$commentStart]['comment_tags'] as $tag) {
            if ($tokens[$tag]['content'] === '@see') {
                // Make sure the tag isn't empty.
                $string = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $tag, $commentEnd);
                if ($string === false || $tokens[$string]['line'] !== $tokens[$tag]['line']) {
                    $error = 'Content missing for @see tag in function comment';
                    $phpcsFile->addError($error, $tag, 'EmptySees');
                }
            }
        }

        $this->processReturn($phpcsFile, $stackPtr, $commentStart);
        $this->processThrows($phpcsFile, $stackPtr, $commentStart);
        $this->processParams($phpcsFile, $stackPtr, $commentStart);
        $this->processSees($phpcsFile, $stackPtr, $commentStart);

    }//end process()


    /**
     * Process the return comment of this function comment.
     *
     * @param PHP_CodeSniffer_File $phpcsFile    The file being scanned.
     * @param int                  $stackPtr     The position of the current token
     *                                           in the stack passed in $tokens.
     * @param int                  $commentStart The position in the stack where the comment started.
     *
     * @return void
     */
    protected function processReturn(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $commentStart)
    {
        $tokens = $phpcsFile->getTokens();

        // Skip constructor and destructor.
        $className = '';
        foreach ($tokens[$stackPtr]['conditions'] as $condPtr => $condition) {
            if ($condition === T_CLASS || $condition === T_INTERFACE) {
                $className = $phpcsFile->getDeclarationName($condPtr);
                $className = strtolower(ltrim($className, '_'));
            }
        }

        $methodName      = $phpcsFile->getDeclarationName($stackPtr);
        $isSpecialMethod = ($methodName === '__construct' || $methodName === '__destruct');
        $methodName      = strtolower(ltrim($methodName, '_'));

        $return = null;
        foreach ($tokens[$commentStart]['comment_tags'] as $pos => $tag) {
            if ($tokens[$tag]['content'] === '@return') {
                if ($return !== null) {
                    $error = 'Only 1 @return tag is allowed in a function comment';
                    $phpcsFile->addError($error, $tag, 'DuplicateReturn');
                    return;
                }

                $return = $tag;
                // Any strings until the next tag belong to this comment.
                if (isset($tokens[$commentStart]['comment_tags'][($pos + 1)]) === true) {
                    $end = $tokens[$commentStart]['comment_tags'][($pos + 1)];
                } else {
                    $end = $tokens[$commentStart]['comment_closer'];
                }
            }
        }

        $type = null;
        if ($isSpecialMethod === false && $methodName !== $className) {
            if ($return !== null) {
                $type = $tokens[($return + 2)]['content'];
                if (empty($type) === true || $tokens[($return + 2)]['code'] !== T_DOC_COMMENT_STRING) {
                    $error = 'Return type missing for @return tag in function comment';
                    $phpcsFile->addError($error, $return, 'MissingReturnType');
                } else {
                    // Check return type (can be multiple, separated by '|').
                    $typeNames      = explode('|', $type);
                    $suggestedNames = array();
                    foreach ($typeNames as $i => $typeName) {
                        $suggestedName = $this->suggestType($typeName);
                        if (in_array($suggestedName, $suggestedNames) === false) {
                            $suggestedNames[] = $suggestedName;
                        }
                    }

                    $suggestedType = implode('|', $suggestedNames);
                    if ($type !== $suggestedType) {
                        $error = 'Function return type "%s" is invalid';
                        $error = 'Expected "%s" but found "%s" for function return type';
                        $data  = array(
                                  $suggestedType,
                                  $type,
                                 );
                        $phpcsFile->addError($error, $return, 'InvalidReturn', $data);
                    }

                    if ($type[0] === '$' && $type !== '$this') {
                        $error = '@return data type must not contain "$"';
                        $phpcsFile->addError($error, $return, '$InReturnType');
                    }

                    if ($type === 'void') {
                        $error = 'If there is no return value for a function, there must not be a @return tag.';
                        $phpcsFile->addError($error, $return, 'VoidReturn');
                    } else if ($type !== 'mixed') {
                        // If return type is not void, there needs to be a return statement
                        // somewhere in the function that returns something.
                        if (isset($tokens[$stackPtr]['scope_closer']) === true) {
                            $endToken    = $tokens[$stackPtr]['scope_closer'];
                            $returnToken = $phpcsFile->findNext(T_RETURN, $stackPtr, $endToken);
                            if ($returnToken === false) {
                                $error = '@return doc comment specified, but function has no return statement';
                                $phpcsFile->addError($error, $return, 'InvalidNoReturn');
                            } else {
                                $semicolon = $phpcsFile->findNext(T_WHITESPACE, ($returnToken + 1), null, true);
                                if ($tokens[$semicolon]['code'] === T_SEMICOLON) {
                                    $error = 'Function return type is not void, but function is returning void here';
                                    $phpcsFile->addError($error, $returnToken, 'InvalidReturnNotVoid');
                                }
                            }
                        }
                    }//end if
                }//end if

                $comment = '';
                for ($i = ($return + 3); $i < $end; $i++) {
                    if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
                        $indent = 0;
                        if ($tokens[($i - 1)]['code'] === T_DOC_COMMENT_WHITESPACE) {
                            $indent = strlen($tokens[($i - 1)]['content']);
                        }

                        $comment       .= ' '.$tokens[$i]['content'];
                        $commentLines[] = array(
                                           'comment' => $tokens[$i]['content'],
                                           'token'   => $i,
                                           'indent'  => $indent,
                                          );
                        if ($indent < 3) {
                            $error = 'Return comment indentation must be 3 spaces, found %s spaces';
                            $phpcsFile->addError($error, $i, 'ReturnCommentIndentation', array($indent));
                        }
                    }
                }

                if ($comment === '' && $type !== '$this' && $type !== 'static') {
                    if (strpos($type, ' ') !== false) {
                        $error = 'Description for the @return value must be on the next line';
                    } else {
                        $error = 'Description for the @return value is missing';
                    }

                    $phpcsFile->addError($error, $return, 'MissingReturnComment');
                }//end if
            }//end if
        } else {
            // No return tag for constructor and destructor.
            if ($return !== null) {
                $error = '@return tag is not required for constructor and destructor';
                $phpcsFile->addError($error, $return, 'ReturnNotRequired');
            }
        }//end if

    }//end processReturn()


    /**
     * Process any throw tags that this function comment has.
     *
     * @param PHP_CodeSniffer_File $phpcsFile    The file being scanned.
     * @param int                  $stackPtr     The position of the current token
     *                                           in the stack passed in $tokens.
     * @param int                  $commentStart The position in the stack where the comment started.
     *
     * @return void
     */
    protected function processThrows(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $commentStart)
    {
        $tokens = $phpcsFile->getTokens();

        foreach ($tokens[$commentStart]['comment_tags'] as $pos => $tag) {
            if ($tokens[$tag]['content'] !== '@throws') {
                continue;
            }

            if ($tokens[($tag + 2)]['code'] !== T_DOC_COMMENT_STRING) {
                $error = 'Exception type missing for @throws tag in function comment';
                $phpcsFile->addError($error, $tag, 'InvalidThrows');
            } else {
                // Any strings until the next tag belong to this comment.
                if (isset($tokens[$commentStart]['comment_tags'][($pos + 1)]) === true) {
                    $end = $tokens[$commentStart]['comment_tags'][($pos + 1)];
                } else {
                    $end = $tokens[$commentStart]['comment_closer'];
                }

                $comment    = '';
                $throwStart = null;
                for ($i = ($tag + 3); $i < $end; $i++) {
                    if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
                        if ($throwStart === null) {
                            $throwStart = $i;
                        }

                        $indent = 0;
                        if ($tokens[($i - 1)]['code'] === T_DOC_COMMENT_WHITESPACE) {
                            $indent = strlen($tokens[($i - 1)]['content']);
                        }

                        $comment .= ' '.$tokens[$i]['content'];
                        if ($indent < 3) {
                            $error = 'Throws comment indentation must be 3 spaces, found %s spaces';
                            $phpcsFile->addError($error, $i, 'TrhowsCommentIndentation', array($indent));
                        }
                    }
                }

                $comment = trim($comment);

                if ($comment === '') {
                    if (str_word_count($tokens[($tag + 2)]['content'], 0, '\\') > 1) {
                        $error = '@throws comment must be on the next line';
                        $phpcsFile->addError($error, $tag, 'ThrowsComment');
                    }

                    return;
                }

                // Starts with a capital letter and ends with a fullstop.
                $firstChar = $comment{0};
                if (strtoupper($firstChar) !== $firstChar) {
                    $error = '@throws tag comment must start with a capital letter';
                    $phpcsFile->addError($error, $throwStart, 'ThrowsNotCapital');
                }

                $lastChar = substr($comment, -1);
                if (in_array($lastChar, array('.', '!', '?')) === false) {
                    $error = '@throws tag comment must end with a full stop';
                    $phpcsFile->addError($error, $throwStart, 'ThrowsNoFullStop');
                }
            }//end if
        }//end foreach

    }//end processThrows()


    /**
     * Process the function parameter comments.
     *
     * @param PHP_CodeSniffer_File $phpcsFile    The file being scanned.
     * @param int                  $stackPtr     The position of the current token
     *                                           in the stack passed in $tokens.
     * @param int                  $commentStart The position in the stack where the comment started.
     *
     * @return void
     */
    protected function processParams(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $commentStart)
    {
        $tokens = $phpcsFile->getTokens();

        $params  = array();
        $maxType = 0;
        $maxVar  = 0;
        foreach ($tokens[$commentStart]['comment_tags'] as $pos => $tag) {
            if ($tokens[$tag]['content'] !== '@param') {
                continue;
            }

            $type         = '';
            $typeSpace    = 0;
            $var          = '';
            $varSpace     = 0;
            $comment      = '';
            $commentLines = array();
            if ($tokens[($tag + 2)]['code'] === T_DOC_COMMENT_STRING) {
                $matches = array();
                preg_match('/([^$&]+)(?:((?:\$|&)[^\s]+)(?:(\s+)(.*))?)?/', $tokens[($tag + 2)]['content'], $matches);

                $typeLen   = strlen($matches[1]);
                $type      = trim($matches[1]);
                $typeSpace = ($typeLen - strlen($type));
                $typeLen   = strlen($type);
                if ($typeLen > $maxType) {
                    $maxType = $typeLen;
                }

                if (isset($matches[4]) === true) {
                    $comment = $matches[4];
                    $error   = 'Parameter comment must be on the next line';
                    $fix     = $phpcsFile->addFixableError($error, ($tag + 2), 'ParamCommentNewLine');
                    if ($fix === true) {
                        $parts = $matches;
                        unset($parts[0]);
                        $parts[3] = "\n *   ";
                        $phpcsFile->fixer->replaceToken(($tag + 2), implode('', $parts));
                    }
                }

                $var    = isset($matches[2]) ? $matches[2] : '';
                $varLen = strlen($var);
                if ($varLen > $maxVar) {
                    $maxVar = $varLen;
                }

                // Any strings until the next tag belong to this comment.
                if (isset($tokens[$commentStart]['comment_tags'][($pos + 1)]) === true) {
                    $end = $tokens[$commentStart]['comment_tags'][($pos + 1)];
                } else {
                    $end = $tokens[$commentStart]['comment_closer'];
                }

                for ($i = ($tag + 3); $i < $end; $i++) {
                    if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
                        $indent = 0;
                        if ($tokens[($i - 1)]['code'] === T_DOC_COMMENT_WHITESPACE) {
                            $indent = strlen($tokens[($i - 1)]['content']);
                        }

                        $comment       .= ' '.$tokens[$i]['content'];
                        $commentLines[] = array(
                                           'comment' => $tokens[$i]['content'],
                                           'token'   => $i,
                                           'indent'  => $indent,
                                          );
                        if ($indent < 3) {
                            $error = 'Parameter comment indentation must be 3 spaces, found %s spaces';
                            $fix   = $phpcsFile->addFixableError($error, $i, 'ParamCommentIndentation', array($indent));
                            if ($fix === true) {
                                $phpcsFile->fixer->replaceToken(($i - 1), '   ');
                            }
                        }
                    }
                }//end for

                if ($comment == '') {
                    $error = 'Missing parameter comment';
                    $phpcsFile->addError($error, $tag, 'MissingParamComment');
                    $commentLines[] = array('comment' => '');
                }//end if
                // Allow the "..." @param doc for a variable number of parameters.
                if (isset($matches[2]) === false && $tokens[($tag + 2)]['content'] !== '...') {
                    if ($tokens[($tag + 2)]['content'][0] === '$' || $tokens[($tag + 2)]['content'][0] === '&') {
                        $error = 'Missing parameter type';
                        $phpcsFile->addError($error, $tag, 'MissingParamType');
                    } else {
                        $error = 'Missing parameter name';
                        $phpcsFile->addError($error, $tag, 'MissingParamName');
                    }
                }//end if
            } else {
                $error = 'Missing parameter type';
                $phpcsFile->addError($error, $tag, 'MissingParamType');
            }//end if

            $params[] = array(
                         'tag'          => $tag,
                         'type'         => $type,
                         'var'          => $var,
                         'comment'      => $comment,
                         'commentLines' => $commentLines,
                         'type_space'   => $typeSpace,
                         'var_space'    => $varSpace,
                        );
        }//end foreach

        $realParams  = $phpcsFile->getMethodParameters($stackPtr);
        $foundParams = array();

        $checkPos = 0;
        foreach ($params as $pos => $param) {
            // If the type is empty, the whole line is empty.
            if ($param['type'] === '') {
                continue;
            }

            if ($param['var'] === '') {
                continue;
            }

            // Make sure the param name is correct.
            $matched = false;
            // Parameter documentation can be ommitted for some parameters, so
            // we have to search the rest for a match.
            $realName = '<undefined>';
            while (isset($realParams[($checkPos)]) === true) {
                $realName = $realParams[$checkPos]['name'];

                if ($realName === $param['var'] || ($realParams[$checkPos]['pass_by_reference'] === true
                    && ('&'.$realName) === $param['var'])
                ) {
                    $matched = true;
                    break;
                }

                $checkPos++;
            }

            // Check the param type value.
            $typeNames = explode('|', $param['type']);
            foreach ($typeNames as $typeName) {
                $suggestedName = $this->suggestType($typeName);
                if ($typeName !== $suggestedName) {
                    $error = 'Expected "%s" but found "%s" for parameter type';
                    $data  = array(
                              $suggestedName,
                              $typeName,
                             );

                    $fix = $phpcsFile->addFixableError($error, $param['tag'], 'IncorrectParamVarName', $data);
                    if ($fix === true && $phpcsFile->fixer->enabled === true) {
                        $content  = $suggestedName;
                        $content .= str_repeat(' ', $param['type_space']);
                        $content .= $param['var'];
                        $phpcsFile->fixer->replaceToken(($param['tag'] + 2), $content);
                    }
                } else if (count($typeNames) === 1) {
                    // Check type hint for array and custom type.
                    $suggestedTypeHint = '';
                    if (strpos($suggestedName, 'array') !== false) {
                        $suggestedTypeHint = 'array';
                    } else if (strpos($suggestedName, 'callable') !== false) {
                        $suggestedTypeHint = 'callable';
                    } else if (substr($suggestedName, -2) === '[]') {
                        $suggestedTypeHint = 'array';
                    } else if ($suggestedName === 'object') {
                        $suggestedTypeHint = '';
                    } else if (in_array($typeName, $this->allowedTypes) === false) {
                        $suggestedTypeHint = $suggestedName;
                    }

                    if ($suggestedTypeHint !== '' && isset($realParams[$checkPos]) === true) {
                        $typeHint = $realParams[$checkPos]['type_hint'];
                        // Array type hints are allowed to be omitted.
                        if ($typeHint === '' && $suggestedTypeHint !== 'array') {
                            $error = 'Type hint "%s" missing for %s';
                            $data  = array(
                                      $suggestedTypeHint,
                                      $param['var'],
                                     );
                            $phpcsFile->addError($error, $stackPtr, 'TypeHintMissing', $data);
                        } else if ($typeHint !== $suggestedTypeHint && $typeHint !== '') {
                            // The type hint could be fully namespaced, so we check
                            // for the part after the last "\".
                            $name_parts = explode('\\', $suggestedTypeHint);
                            $last_part  = end($name_parts);
                            if ($last_part !== $typeHint && $this->isAliasedType($typeHint, $suggestedTypeHint, $phpcsFile) === false) {
                                $error = 'Expected type hint "%s"; found "%s" for %s';
                                $data  = array(
                                          $last_part,
                                          $typeHint,
                                          $param['var'],
                                         );
                                $phpcsFile->addError($error, $stackPtr, 'IncorrectTypeHint', $data);
                            }
                        }//end if
                    } else if ($suggestedTypeHint === '' && isset($realParams[$checkPos]) === true) {
                        $typeHint = $realParams[$checkPos]['type_hint'];
                        if ($typeHint !== '' && $typeHint !== 'stdClass') {
                            $error = 'Unknown type hint "%s" found for %s';
                            $data  = array(
                                      $typeHint,
                                      $param['var'],
                                     );
                            $phpcsFile->addError($error, $stackPtr, 'InvalidTypeHint', $data);
                        }
                    }//end if
                }//end if
            }//end foreach

            $foundParams[] = $param['var'];

            // Check number of spaces after the type.
            $spaces = 1;
            if ($param['type_space'] !== $spaces) {
                $error = 'Expected %s spaces after parameter type; %s found';
                $data  = array(
                          $spaces,
                          $param['type_space'],
                         );

                $fix = $phpcsFile->addFixableError($error, $param['tag'], 'SpacingAfterParamType', $data);
                if ($fix === true && $phpcsFile->fixer->enabled === true) {
                    $phpcsFile->fixer->beginChangeset();

                    $content  = $param['type'];
                    $content .= str_repeat(' ', $spaces);
                    $content .= $param['var'];
                    $content .= str_repeat(' ', $param['var_space']);
                    $content .= $param['commentLines'][0]['comment'];
                    $phpcsFile->fixer->replaceToken(($param['tag'] + 2), $content);

                    // Fix up the indent of additional comment lines.
                    foreach ($param['commentLines'] as $lineNum => $line) {
                        if ($lineNum === 0
                            || $param['commentLines'][$lineNum]['indent'] === 0
                        ) {
                            continue;
                        }

                        $newIndent = ($param['commentLines'][$lineNum]['indent'] + $spaces - $param['type_space']);
                        $phpcsFile->fixer->replaceToken(
                            ($param['commentLines'][$lineNum]['token'] - 1),
                            str_repeat(' ', $newIndent)
                        );
                    }

                    $phpcsFile->fixer->endChangeset();
                }//end if
            }//end if

            if ($matched === false) {
                if ($checkPos >= $pos) {
                    $code = 'ParamNameNoMatch';
                    $data = array(
                             $param['var'],
                             $realName,
                            );

                    $error = 'Doc comment for parameter %s does not match ';
                    if (strtolower($param['var']) === strtolower($realName)) {
                        $error .= 'case of ';
                        $code   = 'ParamNameNoCaseMatch';
                    }

                    $error .= 'actual variable name %s';

                    $phpcsFile->addError($error, $param['tag'], $code, $data);
                    // Reset the parameter position to check for following
                    // parameters.
                    $checkPos = ($pos - 1);
                } else if (substr($param['var'], -4) !== ',...') {
                    // We must have an extra parameter comment.
                    $error = 'Superfluous parameter comment';
                    $phpcsFile->addError($error, $param['tag'], 'ExtraParamComment');
                }//end if
            }//end if

            $checkPos++;

            if ($param['comment'] === '') {
                continue;
            }

            // Param comments must start with a capital letter and end with the full stop.
            $firstChar = isset($param['commentLines'][0]['comment']) ? $param['commentLines'][0]['comment'] : $param['comment'];
            if (preg_match('|\p{Lu}|u', $firstChar) === 0) {
                $error        = 'Parameter comment must start with a capital letter';
                $commentToken = isset($param['commentLines'][0]['token']) ? $param['commentLines'][0]['token'] : $param['tag'];
                $phpcsFile->addError($error, $commentToken, 'ParamCommentNotCapital');
            }

            $lastChar = substr($param['comment'], -1);
            if (in_array($lastChar, array('.', '!', '?')) === false) {
                $error = 'Parameter comment must end with a full stop';
                if (empty($param['commentLines'])) {
                    $commentToken = $param['tag'];
                } else {
                    $lastLine     = end($param['commentLines']);
                    $commentToken = $lastLine['token'];
                }

                $phpcsFile->addError($error, $commentToken, 'ParamCommentFullStop');
            }
        }//end foreach

    }//end processParams()


    /**
     * Process the function "see" comments.
     *
     * @param PHP_CodeSniffer_File $phpcsFile    The file being scanned.
     * @param int                  $stackPtr     The position of the current token
     *                                           in the stack passed in $tokens.
     * @param int                  $commentStart The position in the stack where the comment started.
     *
     * @return void
     */
    protected function processSees(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $commentStart)
    {
        $tokens = $phpcsFile->getTokens();
        foreach ($tokens[$commentStart]['comment_tags'] as $tag) {
            if ($tokens[$tag]['content'] !== '@see') {
                continue;
            }

            if ($tokens[($tag + 2)]['code'] === T_DOC_COMMENT_STRING) {
                $comment = $tokens[($tag + 2)]['content'];
                if (strpos($comment, ' ') !== false) {
                    $error = 'The @see reference should not contain any additional text';
                    $phpcsFile->addError($error, $tag, 'SeeAdditionalText');
                    continue;
                }

                if (preg_match('/[\.!\?]$/', $comment) === 1) {
                    $error = 'Trailing punctuation for @see references is not allowed.';
                    $phpcsFile->addError($error, $tag, 'SeePunctuation');
                }
            }
        }

    }//end processSees()


    /**
     * Returns a valid variable type for param/var tag.
     *
     * @param string $type The variable type to process.
     *
     * @return string
     */
    protected function suggestType($type)
    {
        if (isset($this->invalidTypes[$type])) {
            return $this->invalidTypes[$type];
        }

        return $type;

    }//end suggestType()


    /**
     * Checks if a used type hint is an alias defined by a "use" statement.
     *
     * @param string               $typeHint          The type hint used.
     * @param string               $suggestedTypeHint The fully qualified type to
     *                                                check against.
     * @param PHP_CodeSniffer_File $phpcsFile         The file being checked.
     *
     * @return boolean
     */
    protected function isAliasedType($typeHint, $suggestedTypeHint, PHP_CodeSniffer_File $phpcsFile)
    {
        $tokens = $phpcsFile->getTokens();

        // Iterate over all "use" statements in the file.
        $usePtr = 0;
        while ($usePtr !== false) {
            $usePtr = $phpcsFile->findNext(T_USE, ($usePtr + 1));
            if ($usePtr === false) {
                return false;
            }

            // Only check use statements in the global scope.
            if (empty($tokens[$usePtr]['conditions']) === false) {
                continue;
            }

            // Now comes the original class name, possibly with namespace
            // backslashes.
            $originalClass = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($usePtr + 1), null, true);
            if ($originalClass === false || ($tokens[$originalClass]['code'] !== T_STRING
                && $tokens[$originalClass]['code'] !== T_NS_SEPARATOR)
            ) {
                continue;
            }

            $originalClassName = '';
            while (in_array($tokens[$originalClass]['code'], array(T_STRING, T_NS_SEPARATOR)) === true) {
                $originalClassName .= $tokens[$originalClass]['content'];
                $originalClass++;
            }

            if (ltrim($originalClassName, '\\') !== ltrim($suggestedTypeHint, '\\')) {
                continue;
            }

            // Now comes the "as" keyword signaling an alias name for the class.
            $asPtr = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($originalClass + 1), null, true);
            if ($asPtr === false || $tokens[$asPtr]['code'] !== T_AS) {
                continue;
            }

            // Now comes the name the class is aliased to.
            $aliasPtr = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($asPtr + 1), null, true);
            if ($aliasPtr === false || $tokens[$aliasPtr]['code'] !== T_STRING
                || $tokens[$aliasPtr]['content'] !== $typeHint
            ) {
                continue;
            }

            // We found a use statement that aliases the used type hint!
            return true;
        }//end while

        return false;

    }//end isAliasedType()


}//end class
