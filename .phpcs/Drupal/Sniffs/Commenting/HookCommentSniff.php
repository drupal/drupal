<?php
/**
 * Ensures hook comments on function are correct.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Ensures hook comments on function are correct.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Commenting_HookCommentSniff implements PHP_CodeSniffer_Sniff
{


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
            return;
        }

        if ($tokens[$commentEnd]['code'] === T_COMMENT) {
            return;
        }

        $commentStart = $tokens[$commentEnd]['comment_opener'];

        $empty = array(
                  T_DOC_COMMENT_WHITESPACE,
                  T_DOC_COMMENT_STAR,
                 );

        $short = $phpcsFile->findNext($empty, ($commentStart + 1), $commentEnd, true);
        if ($short === false) {
            // No content at all.
            return;
        }

        // Account for the fact that a short description might cover
        // multiple lines.
        $shortContent = $tokens[$short]['content'];
        $shortEnd     = $short;
        for ($i = ($short + 1); $i < $commentEnd; $i++) {
            if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
                if ($tokens[$i]['line'] === ($tokens[$shortEnd]['line'] + 1)) {
                    $shortContent .= $tokens[$i]['content'];
                    $shortEnd      = $i;
                } else {
                    break;
                }
            }
        }

        // Check if hook implementation doc is formated correctly.
        if (preg_match('/^[\s]*Implement[^\n]+?hook_[^\n]+/i', $shortContent, $matches)) {
            if (!strstr($matches[0], 'Implements ') || strstr($matches[0], 'Implements of')
                || !preg_match('/ (drush_)?hook_[a-zA-Z0-9_]+\(\)( for [a-z0-9_-]+(\(\)|\.tpl\.php|\.html.twig))?\.$/', $matches[0])
            ) {
                $phpcsFile->addWarning('Format should be "* Implements hook_foo().", "* Implements hook_foo_BAR_ID_bar() for xyz_bar().",, "* Implements hook_foo_BAR_ID_bar() for xyz-bar.html.twig.", or "* Implements hook_foo_BAR_ID_bar() for xyz-bar.tpl.php.".', $short);
            } else {
                // Check that a hook implementation does not duplicate @param and
                // @return documentation.
                foreach ($tokens[$commentStart]['comment_tags'] as $pos => $tag) {
                    if ($tokens[$tag]['content'] === '@param') {
                        $warn = 'Hook implementations should not duplicate @param documentation';
                        $phpcsFile->addWarning($warn, $tag, 'HookParamDoc');
                    }

                    if ($tokens[$tag]['content'] === '@return') {
                        $warn = 'Hook implementations should not duplicate @return documentation';
                        $phpcsFile->addWarning($warn, $tag, 'HookReturnDoc');
                    }
                }
            }//end if
        }//end if

    }//end process()


}//end class
