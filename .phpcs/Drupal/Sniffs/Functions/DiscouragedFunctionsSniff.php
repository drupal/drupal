<?php
/**
 * Drupal_Sniffs_Functions_DiscouragedFunctionsSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Discourage the use of debug functions.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Functions_DiscouragedFunctionsSniff extends Generic_Sniffs_PHP_ForbiddenFunctionsSniff
{

    /**
     * A list of forbidden functions with their alternatives.
     *
     * The value is NULL if no alternative exists, i.e., the function should
     * just not be used.
     *
     * @var array(string => string|null)
     */
    public $forbiddenFunctions = array(
                                     // Devel module debugging functions.
                                  'dargs'               => null,
                                  'dcp'                 => null,
                                  'dd'                  => null,
                                  'dfb'                 => null,
                                  'dfbt'                => null,
                                  'dpm'                 => null,
                                  'dpq'                 => null,
                                  'dpr'                 => null,
                                  'dprint_r'            => null,
                                  'drupal_debug'        => null,
                                  'dsm'                 => null,
                                  'dvm'                 => null,
                                  'dvr'                 => null,
                                  'kdevel_print_object' => null,
                                  'kpr'                 => null,
                                  'kprint_r'            => null,
                                  'sdpm'                => null,
                                  // Functions which are not available on all
                                  // PHP builds.
                                  'fnmatch'             => null,
                                 );

    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var bool
     */
    public $error = false;

}//end class
