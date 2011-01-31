<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * @file
 * Admin area routines
 *
 * @category  Litecommerce connector
 * @package   Litecommerce connector
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2010 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://www.litecommerce.com/
 * @see       ____file_see____
 * @since     1.0.0
 */

/**
 * Get module settings form
 *
 * @return array
 * @see    ____func_see____
 * @since  3.0.0
 */
function lc_connector_get_settings_form()
{
    return LCConnector_Admin::getModuleSettingsForm();
}

/**
 * Submit module settings form
 *
 * @param array &$form      Form description
 * @param array &$formState Form state
 *
 * @return void
 * @see    ____func_see____
 * @since  1.0.0
 */
function lc_connector_submit_settings_form(array &$form, array &$formState)
{
    return LCConnector_Admin::submitModuleSettingsForm($form, $formState);
}
