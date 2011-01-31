<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * @file
 * Custom theme settings
 *
 * @category  LiteCommerce themes
 * @package   LiteCommerce3 theme
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2010 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 * @version   SVN: $Id: theme-settings.php 4693 2010-12-10 10:54:38Z xplorer $
 * @link      http://www.litecommerce.com/
 * @see       ____file_see____
 * @since     1.0.0
 */

/**
 * Implements hook_form_FORM_ID_alter() and adds custom theme settings
 *
 * @param array $form       The form.
 * @param mixed $form_state The form state.
 * @return void
 * @see    ____func_see____
 * @since  1.0.0
 */
function lc3_clean_form_system_theme_settings_alter(&$form, &$form_state)
{
    $form['social_links'] = array(

        '#type' => 'fieldset',
        '#title' => t('Social links'),
        '#description' => t('Enable or disable links to your accounts in social network services'),
        '#weight' => -10,

        'theme_social_link_facebook' => array(
            '#type' => 'textfield',
            '#title' => t('Facebook business page'),
            '#default_value' => theme_get_setting('theme_social_link_facebook'),
            '#description' => t('Name of your Facebook business page'),
        ),

        'theme_social_link_twitter' => array(
            '#type' => 'textfield',
            '#title' => t('Twitter account'),
            '#default_value' => theme_get_setting('theme_social_link_twitter'),
            '#description' => t('Name of your Twitter account'),
        ),

    );

}

