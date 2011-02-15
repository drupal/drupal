<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * @file
 * Base class for all handler
 *
 * @category  Litecommerce connector
 * @package   Litecommerce connector
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2010 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 * @version   SVN: $Id: Abstract.php 4912 2011-01-18 09:10:05Z svowl $
 * @link      http://www.litecommerce.com/
 * @see       ____file_see____
 * @since     1.0.0
 */

/**
 * Abstract 
 * 
 * @package XLite
 * @see     ____class_see____
 * @since   1.0.0
 */
abstract class LCConnector_Abstract
{
    /**
     * Data from the module .info file
     * 
     * @var    array
     * @access protected
     * @see    ____var_see____
     * @since  3.0.0
     */
    protected static $moduleInfo;

    /**
     * Flag; if LC "top.inc.php" is included
     *
     * @var    boolean
     * @access protected
     * @see    ____var_see____
     * @since  1.0.0
     */
    protected static $isLCConnected;


    // ------------------------------ LC-related methods -

    /**
     * Prepare file path
     *
     * @param string $dir Dir to prepare
     *
     * @return string
     * @access protected
     * @see    ____func_see____
     * @since  1.0.0
     */
    protected static function getCanonicalDir($dir)
    {
        return rtrim(realpath($dir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Return path to LC installation (from settings or default)
     *
     * @return string
     * @access public
     * @see    ____func_see____
     * @since  1.0.0
     */
    public static function getLCDir()
    {
        return variable_get('lc_dir', self::getModuleInfo('lc_dir_default'));
    }

    /**
     * Return full path to the LC top inc file
     * 
     * @return string
     * @access protected
     * @see    ____func_see____
     * @since  1.0.0
     */
    protected static function getLCTopIncFile()
    {
        return self::getLCCanonicalDir() . 'top.inc.php';
    }

    /**
     * Return absolute path to LC installation
     * 
     * @return string
     * @access public
     * @see    ____func_see____
     * @since  1.0.0
     */
    public static function getLCCanonicalDir()
    {
        return self::getCanonicalDir(self::getLCDir());
    }

    /**
     * Return data from the module .info file
     * 
     * @param string $field Name of the field to retrieve
     *  
     * @return array|string
     * @access protected
     * @see    ____func_see____
     * @since  3.0.0
     */
    protected static function getModuleInfo($field = null)
    {
        if (!isset(self::$moduleInfo)) {
            self::$moduleInfo = (array) drupal_parse_info_file(
                self::getCanonicalDir(dirname(dirname(__FILE__))) . 'lc_connector.info'
            );
        }

        return isset($field) ? @self::$moduleInfo[$field] : self::$moduleInfo;
    }

    /**
     * Check if we can connect to LiteCommerce
     * 
     * @return boolean
     * @access public
     * @see    ____func_see____
     * @since  1.0.0
     */
    public static function isLCExists()
    {
        return file_exists(self::getLCTopIncFile());
    }

    /**
     * Check if we already connected to LiteCommerce
     * 
     * @return boolean
     * @access protected
     * @see    ____func_see____
     * @since  1.0.0
     */
    protected static function isLCConnected()
    {
        if (!isset(self::$isLCConnected) && (self::$isLCConnected = self::isLCExists())) {
            require_once (self::getLCTopIncFile());
        }

        return self::$isLCConnected;
    }


    // ------------------------------ Call wrappers -

    /**
     * Get instance of LC singleton
     * 
     * @param string $class Base part of a sigleton class name
     *  
     * @return \XLite\Module\CDev\DrupalConnector\Drupal\ADrupal
     * @access protected
     * @see    ____func_see____
     * @since  3.0.0
     */
    protected static function getLCClassInstance($class)
    {
        return call_user_func(array('\XLite\Module\CDev\DrupalConnector\Drupal\\' . $class, 'getInstance'));
    }

    /**
     * Wrapper to directly call LC-dependend methods
     *
     * @param string  $class  Handler class name
     * @param string  $method Method to call
     * @param array   $args   Call arguments
     *
     * @return mixed
     * @access public
     * @see    ____func_see____
     * @since  3.0.0
     */
    public static function callDirectly($class, $method, array $args = array())
    {
        return call_user_func_array(array(self::getLCClassInstance($class), $method), $args);
    }

    /**
     * Wrapper to safely call LC-dependend methods
     *
     * @param string  $class  Handler class name
     * @param string  $method Method to call
     * @param array   $args   Call arguments
     *
     * @return mixed
     * @access public
     * @see    ____func_see____
     * @since  3.0.0
     */
    public static function callSafely($class, $method, array $args = array())
    {
        return self::isLCConnected() ? self::callDirectly($class, $method, $args) : null;
    }
}
