<?php

variable_set('default_nodes_main', 1);

// vim: set ts=4 sw=4 sts=4 et:

/**
 * @file
 * Theme functions
 *
 * @category  LiteCommerce themes
 * @package   LiteCommerce3 theme
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2010 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 * @version   SVN: $Id: template.php 4852 2011-01-12 01:08:10Z vvs $
 * @link      http://www.litecommerce.com/
 * @see       ____file_see____
 * @since     1.0.0
 */

require 'preprocess.inc';

/**
 * Implementation of hook_theme().
 * This function provides a one-stop reference for all overriden and custom theme functions.
 *
 * Returns an array of arrays. The key to each sub-array is the internal name of the hook, and the array contains info about the hook.
 * Each array may contain the following items:
 * "arguments": (required) An array of arguments that this theme hook uses. Keys are variable names, values are default values.
 * "template": If specified, this theme implementation is a template, and this is the template file WITHOUT an extension.
 * "file": The file the implementation resides in. This file will be included prior to the theme being rendered.
 * "function": If specified, this will be the function name to invoke for this implementation.
 * "path": Custom path (relative to the Drupal root directory) of the file or the template to be used.
 * "pattern": A pattern to be used to allow this theme implementation to have a dynamic name. Use __ to differentiate the dynamic portion of the theme. For example, the pattern might be: 'forum__'. Then, when the forum is themed, call: theme(array('forum__'. $tid, 'forum'), $forum).
 * "preprocess functions": A list of functions used to preprocess this data. Ordinarily it's automatically filled in.
 * "override preprocess functions": Set to TRUE when a theme does NOT want the standard preprocess functions to run.
 * "type": (automatically derived) Where the theme hook is defined: 'module', 'theme_engine', or 'theme'.
 * "theme path": (automatically derived) The directory path of the theme or module, so that it doesn't need to be looked up.
 * "theme paths": (automatically derived) Array of template suggestions where .tpl.php files related to this theme hook may be found.
 *
 * @param array  $existing An array of existing implementations that may be used for override purposes
 * @param string $type     What 'type' is being processed: module, base_theme_engine, theme_engine, base_theme, theme
 * @param string $theme    The actual name of theme that is being being checked (mostly only useful for theme engine)
 * @param string $path     The directory path of the theme or module, so that it doesn't need to be looked up
 *
 * @return array
 * @see    ____func_see____
 * @since  1.0.0
 */
function lc3_clean_theme(&$existing, $type, $theme, $path)
{
    return array(
        // theme wrapper for the main menu
        'menu_tree__main_menu' => array(
            'file' => 'templates/theme.inc',
            'render element' => 'tree',
        ),
        // custom theme function for breadcrumbs
        'breadcrumb' => array(
            'file' => 'templates/theme.inc',
        ),
        // taxonomy tags
        'field__taxonomy_term_reference' => array(
            'file' => 'templates/theme.inc',
        ),
        // popup boxes
        'popup_box' => array(
            'file' => 'templates/theme.inc',
            'render element' => 'popup',
        ),
        // buttons
        'button' => array(
            'file' => 'templates/theme.inc',
            'render element' => 'element',
        ),
        // Drupal pagers
        'pager' => array(
            'file' => 'templates/theme.inc',
        ),
        // popup status messages
        'status_messages' => array(
            'file' => 'templates/theme.inc',
        ),
        // sort indicator for tables
        'tablesort_indicator' => array(
            'file' => 'templates/theme.inc',
        ),
/*
        // Quoted comments
        'quote' => array(
            'arguments' => array(
                'quote_content' => NULL,
                'quote_author' => NULL,
            ),
            'file' => 'templates/theme.inc',
        ),
        // Status messages
*/
    );
}


/**
 * Adds account links to a page
 * 
 * @param array $page Structured array defining the page
 *
 * @return void
 * @access public
 */
function lc3_clean_page_alter(&$page)
{
    global $user;

    // Remove left sidebar
    if (
        arg(0) == 'store'
        && isset($page['sidebar_first'])
        && in_array(arg(1), array('cart', 'checkout', 'product'))
    ) {
        unset($page['sidebar_first']);
    }

    // Render the account links
    $disabledPopup = (arg(0) == 'user') || (isset($user->uid) && $user->uid);

    $links =& $page['account_links'];

    if (isset($user->uid) && $user->uid) {

        $links = array(

            'greeting-message' => array(
                '#markup' => '<span class="greeting-message"><span>' . t('Hello, ') . '</span>' . check_plain($user->name) . '</span>',
            ),

            'account-links' => array(

                '#theme' => 'links__account_links',

                '#attributes' => array(
                    'class' => array('account-links', 'inline'),
                ),

                '#links' => array(

                    'account-link-1' => array(
                        'href'       => 'user',
                        'title'      => t('My account'),
                        'attributes' => array('class' => array('account')),
                    ),

                    'account-link-2' => array(
                        'href'       => 'user/logout',
                        'title'      => t('Log out'),
                        'attributes' => array('class' => array('log-in')),
                    ),

                ),
            ),
        );

    } else {

        $attributes = $disabledPopup ? array() : array('onclick' => 'javascript: lc3_clean_popup_div("login-popup-box", true); return false;');

        $links = array(

            'account-links' => array(

                '#theme' => 'links__account_links',

                '#attributes' => array(
                    'class' => array('account-links', 'inline'),
                ),

                '#links' => array(

                    'account-link-1' => array(
                        'href'       => 'user',
                        'title'      => t('Log in'),
                        'attributes' => $attributes + array('class' => array('log-in')),
                    ),

                    'account-link-2' => array(
                        'href'       => 'user/register',
                        'title'      => t('Register'),
                        'attributes' => array('class' => array('register')),
                    ),

                ),
            ),
        );

    }

    if (!$disabledPopup) {

        // Render popup forms
        module_load_include('inc', 'user', 'user.pages');

        $popups =& $page['page_bottom']['blockui-popups'];

        $popups = array(

            'login-popup' => array(
                '#theme_wrappers' => array('popup_box'),
                '#id' => 'login-popup-box',
                '#subject' => t('User login'),
                'form' => drupal_get_form('user_login', true),
            ),

            'recovery-password-popup' => array(
                '#theme_wrappers' => array('popup_box'),
                '#id' => 'password-popup-box',
                '#subject' => t('Request new password'),
                'form' => drupal_get_form('user_pass'),
            ),

        );

        $popups['login-popup']['form']['#action'] = url('user',  array('query' => drupal_get_destination()));
        $popups['recovery-password-popup']['form']['#action'] = url('user/password', array('query' => drupal_get_destination()));

    }

}

/**
 * Alter the 'user-login' form
 * 
 * @param array  $form        Nested array of form elements that comprise the form.
 * @param array  $form_state  A keyed array containing the current state of the form
 * @param string $form_id     String representing the name of the form itself
 *
 * @return void
 * @access public
 *
 * @see hook_form_alter()
 * @see drupal_prepare_form()
 */
function lc3_clean_form_user_login_alter(&$form, &$form_state, $form_id)
{
    // Check whether the form is to be displayed in a popup layer
    $popup = (arg(0)!=='user');

    if ($popup) {

        // Make the login button larger
        $form['actions']['submit']['#attributes']['class'][] = 'action';

        //Display the button right below the form fields
        $form['actions']['#weight'] = 0.5;

        // Shorten the OpenID field title
        if (isset($form['openid_identifier']['#title'])) {
            $form['openid_identifier']['#title'] = t('OpenID');
        }

        // Add the "recover password" link
        $attributes = array(
            'title' => t('Request new password via e-mail.'),
            'onclick' => "javascript: lc3_clean_popup_div('password-popup-box'); return false;",
        );
        $form['account-links'] = array(
            '#weight' => 2,
            '#theme' => 'item_list',
            '#items' => array(
                array(
                    'data' => l(t('Forgot password?'), 'user/password', array('attributes' => $attributes)),
                    'class' => array('restore-password'),
                ),
            ),
            '#attributes' => array(
                'class' => array('user-account'),
            ),
        );

    }

}

/**
 * Alter the 'user-pass' form
 * 
 * @param array  $form        Nested array of form elements that comprise the form.
 * @param array  $form_state  A keyed array containing the current state of the form
 * @param string $form_id     String representing the name of the form itself
 *
 * @return void
 * @access public
 *
 * @see hook_form_alter()
 * @see drupal_prepare_form()
 */
function lc3_clean_form_user_pass_alter(&$form, &$form_state, $form_id)
{
    // Make the login button larger
    $form['actions']['submit']['#attributes']['class'][] = 'action';

    // Shorten the label
    $form['name']['#title'] = t('Username or e-mail');
}

/**
 * Hook for altering any form
 * 
 * @param array  $form        Nested array of form elements that comprise the form.
 * @param array  $form_state  A keyed array containing the current state of the form
 * @param string $form_id     String representing the name of the form itself
 *
 * @return void
 * @access public
 *
 * @see hook_form_alter()
 * @see drupal_prepare_form()
 */
function lc3_clean_form_alter(&$form, &$form_state, $form_id)
{
    global $user;

    // Alter comment forms when the user is logged in
    if (preg_match('/^comment_node_([\w\d_]+)_form$/', $form_id, $matches) && $user->uid) {

        // Add an extra class to comment forms
        $form['#attributes']['class'][] = 'comment-form-is-logged';

        // Add ":" to the name label
        $form['author']['_author']['#title'] .= ': ';

    }

}

function lc3_clean_preprocess_block(&$vars)
{
    if (!$vars['block']->title) {
        $vars['classes_array'][] = 'block-without-title';
    }
}

function lc3_clean_html_head_alter(&$head_elements)
{
    $head_elements['lc3_clean_content_script_type'] = array(
        '#type' => 'html_tag',
        '#tag'  => 'meta',
        '#attributes' => array(
            'http-equiv' => 'Content-Script-Type',
            'content'    => 'text/javascript',
        ),
        '#weight' => -1000,
    );

    $head_elements['lc3_clean_content_style_type'] = array(
        '#type' => 'html_tag',
        '#tag'  => 'meta',
        '#attributes' => array(
            'http-equiv' => 'Content-Style-Type',
            'content'    => 'text/css',
        ),
        '#weight' => -1000,
    );
}
