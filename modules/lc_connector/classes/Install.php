<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * @file
 * Installation process handler
 *
 * @category  Litecommerce connector
 * @package   Litecommerce connector
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2010 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 * @version   SVN: $Id: Install.php 4959 2011-01-23 18:56:36Z vvs $
 * @link      http://www.litecommerce.com/
 * @see       ____file_see____
 * @since     1.0.0
 */

/**
 * Install 
 * 
 * @package XLite
 * @see     ____class_see____
 * @since   1.0.0
 */
abstract class LCConnector_Install extends LCConnector_Abstract
{
    /**
     * Get module tables schema 
     * 
     * @return array
     * @access public
     * @see    ____func_see____
     * @since  1.0.0
     */
    public static function getSchema()
    {
        return array(

            'block_lc_widget_settings' => array(
                'description' => t('List of LC widget settings'),
                'fields'      => array(
                    'bid' => array(
                        'description' => t('Block id'),
                        'type'        => 'int',
                        'not null'    => true,
                        'default'     => 0,
                    ),
                    'name' => array(
                        'description' => t('Setting code'),
                        'type'        => 'char',
                        'length'      => 32,
                        'not null'    => true,
                        'default'     => '',
                    ),
                    'value' => array(
                        'description' => t('Setting value'),
                        'type'        => 'varchar',
                        'length'      => 255,
                    ),
                ),
                'indexes' => array(
                    'bid' => array('bid'),
                ),
                'unique keys' => array(
                    'bid_name' => array('bid', 'name'),
                ),
                'foreign keys' => array(
                    'settings' => array(
                        'table'   => 'block',
                        'columns' => array('bid' => 'bid'),
                    ),
                ),
            ),
        );
    }

    /**
     * Perform install 
     * 
     * @return void
     * @access public
     * @see    ____func_see____
     * @since  1.0.0
     */
    public static function performInstall()
    {
        $description = array('description' => t('LC class'), 'type' => 'varchar', 'length' => 255);
        db_add_field('block_custom', 'lc_class', $description);
    }

    /**
     * Perform uninstall 
     * 
     * @return void
     * @access public
     * @see    ____func_see____
     * @since  1.0.0
     */
    public static function performUninstall()
    {
        db_drop_field('block_custom', 'lc_class');
    }


    // --------------- FIXME: all of the code below must be revised - 

    /**
     * Check requirements
     * 
     * @return array
     * @access public
     * @see    ____func_see____
     * @since  1.0.0
     */
    public static function checkRequirements($phase)
    {
        $errorMsg = null;
        $requirements = array();

        // Trying to include LiteCommerce installation scripts
        $errorMsg = static::includeLCFiles();

        if (is_null($errorMsg)) {

            if ('install' == $phase) {
                $requirements = static::checkRequirementsInstall();
          
            } else {
                $requirements = static::checkRequirementsUpdate();
            }
        
        } else {

            // LiteCommerce is not found at all: requirements failed
            $requirements['lc_not_found'] = array(
                'description' => $errorMsg,
                'severity' => REQUIREMENT_ERROR
            );
        }

        return $requirements;
    }

    /**
     * Trying to include installation scripts and return an error message if failed
     * 
     * @return string
     * @access public
     * @see    ____func_see____
     * @since  1.0.0
     */
    public static function includeLCFiles()
    {
        $errorMsg = null;

        if (!defined('XLITE_INSTALL_MODE')) {
            define('XLITE_INSTALL_MODE', 1);
            define('LC_DO_NOT_REBUILD_CACHE', true);
        }

        $includeFiles = array(
            'Includes/install/init.php',
            'Includes/install/install.php'
        );

        foreach ($includeFiles as $includeFile) {

            $file = static::getLCCanonicalDir() . $includeFile;

            if (file_exists($file)) {
                require_once $file;

            } else {
                $errorMsg = st('LiteCommerce software not found in :lcdir (file :filename)', array(':lcdir' => static::getLCDir(), ':filename' => $file));
                break;
            }
        }

        return $errorMsg;
    }

    /**
     * Check requirements in update mode ($phase != 'install')
     * 
     * @return array
     * @access protected
     * @see    ____func_see____
     * @since  1.0.0
     */
    protected static function checkRequirementsUpdate()
    {
        $requirements = array();

        $dbParams = lc_connector_get_database_params();

        $message = null;

        if (!isLiteCommerceInstalled($dbParams, $message)) {

            $requirements['lc_not_installed'] = array(
                'description' => st('The installed LiteCommerce software not found. It is required to install LiteCommerce and specify correct path to them in the LC Connector module settings.'),
                'severity' => REQUIREMENT_WARNING
            );
        }

        return $requirements;
    }

    /**
     * Check requirements in install mode ($phase == 'install')
     * 
     * @return array
     * @access protected
     * @see    ____func_see____
     * @since  1.0.0
     */
    protected static function checkRequirementsInstall()
    {
        $requirements = array();

        $dbParams = lc_connector_get_database_params();

        $message = null;

        if (isLiteCommerceInstalled($dbParams, $message)) {

            $requirements['lc_already_installed'] = array(
                'description' => st('The installed LiteCommerce software found. It means that LiteCommerce will not be installed.'),
                'severity' => REQUIREMENT_WARNING
            );
        
        } else {

            $stopChecking = false;

            if (isset($dbParams['driver']) && 'mysql' != $dbParams['driver']) {
                $requirements['lc_mysql_needed'] = array(
                    'description' => 'LiteCommerce software does not support the specified database type: ', $db_type . '(' . $db_url . ')',
                    'severity' => REQUIREMENT_ERROR
                );
                $stopChecking = true;
            }

            $tablePrefix = \Includes\Utils\ConfigParser::getOptions(array('database_details', 'table_prefix'));

            if (isset($dbParams['prefix']) && $tablePrefix === $dbParams['prefix']) {
                $requirements['lc_db_prefix_reserved'] = array(
                    'description' => st('Tables prefix \':prefix\' is reserved by LiteCommerce. Please specify other prefix in the settings.php file.', array(':prefix' => $tablePrefix)),
                    'severity' => REQUIREMENT_ERROR
                );
                $stopChecking = true;
            }

            if (!$stopChecking) {

                if (!defined('LC_URI')) {
                    define('LC_URI', preg_replace('/\/install(\.php)*/', '', $_SERVER['REQUEST_URI']) . '/modules/lc_connector/litecommerce');
                }

                if (!defined('DB_URL') && !empty($dbParams)) {
                    define('DB_URL', serialize($dbParams));
                }

                $requirements = doCheckRequirements();

                foreach ($requirements as $reqName => $reqData) {

                    $requirements[$reqName]['description'] = 'LiteCommerce: ' . (!empty($reqData['description']) ? $reqData['description'] : $reqData['title']);

                    if (false === $reqData['status']) {

                        if (true === $reqData['critical']) {
                            $requirements[$reqName]['severity'] = REQUIREMENT_ERROR;

                        } else {
                            $requirements[$reqName]['severity'] = REQUIREMENT_WARNING;
                        }

                    } else {
                        $requirements[$reqName]['severity'] = REQUIREMENT_OK;
                    }
                }
            }
        }

        return $requirements;
    }
}
