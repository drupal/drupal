<?php
/**
 * Drupal_Sniffs_NamingConventions_ValidGlobalSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Ensures that global variables start with an underscore.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_NamingConventions_ValidGlobalSniff implements PHP_CodeSniffer_Sniff
{

    public $coreGlobals = array(
                           '$argc',
                           '$argv',
                           '$base_insecure_url',
                           '$base_path',
                           '$base_root',
                           '$base_secure_url',
                           '$base_theme_info',
                           '$base_url',
                           '$channel',
                           '$conf',
                           '$config_directories',
                           '$cookie_domain',
                           '$databases',
                           '$db_prefix',
                           '$db_type',
                           '$db_url',
                           '$drupal_hash_salt',
                           '$drupal_test_info',
                           '$element',
                           '$forum_topic_list_header',
                           '$image',
                           '$install_state',
                           '$installed_profile',
                           '$is_https',
                           '$is_https_mock',
                           '$item',
                           '$items',
                           '$language',
                           '$language_content',
                           '$language_url',
                           '$locks',
                           '$menu_admin',
                           '$multibyte',
                           '$pager_limits',
                           '$pager_page_array',
                           '$pager_total',
                           '$pager_total_items',
                           '$tag',
                           '$theme',
                           '$theme_engine',
                           '$theme_info',
                           '$theme_key',
                           '$theme_path',
                           '$timers',
                           '$update_free_access',
                           '$update_rewrite_settings',
                           '$user',
                          );


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_GLOBAL);

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

        $varToken = $stackPtr;
        // Find variable names until we hit a semicolon.
        $ignore   = PHP_CodeSniffer_Tokens::$emptyTokens;
        $ignore[] = T_SEMICOLON;
        while ($varToken = $phpcsFile->findNext($ignore, ($varToken + 1), null, true, null, true)) {
            if ($tokens[$varToken]['code'] === T_VARIABLE
                && in_array($tokens[$varToken]['content'], $this->coreGlobals) === false
                && $tokens[$varToken]['content']{1} !== '_'
            ) {
                $error = 'global variables should start with a single underscore followed by the module and another underscore';
                $phpcsFile->addError($error, $varToken, 'GlobalUnderScore');
            }
        }

    }//end process()


}//end class
