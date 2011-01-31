<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * @file
 * Coommon handler
 *
 * @category  Litecommerce connector
 * @package   Litecommerce connector
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2010 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 * @version   SVN: $Id: Admin.php 4959 2011-01-23 18:56:36Z vvs $
 * @link      http://www.litecommerce.com/
 * @see       ____file_see____
 * @since     1.0.0
 */

/**
 * Admin 
 * 
 * @package XLite
 * @see     ____class_see____
 * @since   3.0.0
 */
abstract class LCConnector_Admin extends LCConnector_Abstract
{
    /**
     * Return form description for the module settings
     *
     * @return array
     * @access public
     * @see    ____func_see____
     * @since  3.0.0
     */
    public static function getModuleSettingsForm()
    {
        $form = array(

            'settings' => array(
                'lc_dir' => array(
                    '#type'          => 'textfield',
                    '#title'         => t('LiteCommerce installation dir'),
                    '#required'      => true,
                    '#default_value' => static::getLCDir(),
                ),

                '#type'  => 'fieldset',
                '#title' => t('LC Connector module settings'),
            ),
        );

        $form = system_settings_form($form);
        $form['#submit'][] = 'lc_connector_submit_settings_form';

        // FIXME: it's the hack. See the "submitModuleSettingsForm" method.
        // Unfortunatelly I've not found any solution to update menus immediatelly
        // (when changing the LC path)
        menu_rebuild();

        return $form;
    }

    /**
     * Submit module settings form
     *
     * @param array &$form      Form description
     * @param array &$formState Form state
     *
     * @return void
     * @access public
     * @see    ____func_see____
     * @since  1.0.0
     */
    public static function submitModuleSettingsForm(array &$form, array &$formState)
    {
        menu_rebuild();
    }
}
