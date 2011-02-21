<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * @file
 * Ecommerce CMS profile
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
 * XLITE_INSTALL_MODE constant indicates the installation process
 */
define ('XLITE_INSTALL_MODE', 1);

/**
 * LC_DO_NOT_REBUILD_CACHE constant prevents the automatical cache building when top.inc.php is reached
 */
define('LC_DO_NOT_REBUILD_CACHE', true);

global $conf;

$conf['theme_settings'] = array(
    'default_logo' => 0,
    'logo_path' => 'profiles/litecommerce/lc_logo.png',
);

/**
 * Returns the array of Ecommerce CMS specific tasks
 */
function _litecommerce_install_tasks(&$install_state) {

    $install_state['license_confirmed'] = isset($install_state['license_confirmed']) || (isset($_COOKIE['lc']) && '1' == $_COOKIE['lc']);

    if ('litecommerce_setup_form' == $install_state['active_task']) {
        variable_set('is_litecommerce_installed', _litecommerce_is_lc_installed());
    }    

    $is_litecommerce_installed = variable_get('is_litecommerce_installed');

    $params = _litecommerce_get_setup_params();
    $is_setup_needed = !isset($params['setup_passed']);

    $tasks = array(
        'litecommerce_preset_locale' => array(
            'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
        ),
        'litecommerce_license_form' => array(
            'display_name' => st('License agreements'),
            'type' => 'form',
            'run' => !empty($install_state['license_confirmed']) ? INSTALL_TASK_SKIP : INSTALL_TASK_RUN_IF_NOT_COMPLETED,
        ),
        'litecommerce_setup_form' => array(
            'display_name' => st('Set up LiteCommerce'),
            'type' => 'form',
            'run' => !$is_setup_needed ? INSTALL_TASK_SKIP : INSTALL_TASK_RUN_IF_REACHED,
        ),
        'litecommerce_software_install' => array(
            'display_name' => st('Install LiteCommerce'),
            'type' => 'batch',
        ),
    );

    return $tasks;
}

/**
 * Alter the default installation tasks list
 * 
 * Extends the tasks list with the profile specific tasks
 */
function litecommerce_install_tasks_alter(&$tasks, $install_state) {

    $lcTasks = _litecommerce_install_tasks($install_state);

    $excludedTasks = array(
        'install_select_locale',
    );

    $newTasks = array();

    foreach ($tasks as $key => $value) {

        if (!in_array($key, $excludedTasks)) {
            $newTasks[$key] = $value;
        }

        if ('install_select_profile' == $key) {
            $newTasks['litecommerce_preset_locale'] = $lcTasks['litecommerce_preset_locale'];
        }

        if ('install_load_profile' == $key) {
            $newTasks['litecommerce_license_form'] = $lcTasks['litecommerce_license_form'];
        }

        if ('install_bootstrap_full' == $key) {
            $newTasks['litecommerce_setup_form'] = $lcTasks['litecommerce_setup_form'];
            $newTasks['litecommerce_software_install'] = $lcTasks['litecommerce_software_install'];
        }
    }

    $tasks = $newTasks;
}



function litecommerce_preset_locale($install_state) {
    if ('en' != $install_state['parameters']['locale']) {
        $install_state['parameters']['locale'] = 'en';
        install_goto(install_redirect_url($install_state));
    }
}



/**
 * Implements license agreement form
 */
function litecommerce_license_form($form, &$form_state, &$install_state) {

    drupal_set_title(st('License agreements'));

    $licenseText =<<< OUT

<br />

This package contains the following parts distributed under the <a href="http://www.gnu.org/licenses/gpl-2.0.html" target="new">http://www.gnu.org/licenses/gpl-2.0.html</a> ("GNU General Public License v.2.0"): <br />
&nbsp;&nbsp;- Drupal 7 <br />
&nbsp;&nbsp;- additional Drupal modules that may be useful for most e-commerce websites <br />
&nbsp;&nbsp;- theme developed by <a href="http://www.qtmsoft.com/" target="new">http://www.qtmsoft.com</a> <br />
&nbsp;&nbsp;- LiteCommerce Connector module developed by <a href="http://www.qtmsoft.com/" target="new">http://www.qtmsoft.com</a> <br />

<br />

Also, this package installs <a href="http://www.litecommerce.com/" target="new">http://www.litecommerce.com</a> e-commerce software, distributed under the <a href="http://opensource.org/licenses/osl-3.0.php" target="new">http://opensource.org/licenses/osl-3.0.php</a> ("Open Software License"). LiteCommerce 3 is not a part of Drupal and can be downloaded, installed and used as a separate web application for building e-commerce websites.

<br /><br />

In order to continue the installation script, you must accept both the license agreements.

<br /><br />

OUT;

    $form = array();

    $form['license'] = array(
        '#type' => 'fieldset',
        '#title' => st('License agreements'),
        '#collapsible' => FALSE,
        '#description' => $licenseText
    );

    $form['license']['content'] = array(
        '#description' => $licenseText
    );

    $form['license']['license_confirmed'] = array(
        '#type' => 'checkbox',
        '#return_value' => 1,
        '#title' => st('I understand and accept <i>both</i> the license agreements')
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => st('Save and continue'),
    );

    return $form;
}

/**
 * Implements license agreement form validation
 */
function litecommerce_license_form_validate($form, &$form_state) {

    if (empty($form_state['values']['license_confirmed'])) {
        form_error($form['license']['license_confirmed'], st('You should confirm the license agreement before proceeding'), 'error');
    }
}

/**
 * Implements license agreement form processing
 */
function litecommerce_license_form_submit($form, &$form_state) {

    global $install_state;
    
    $install_state['license_confirmed'] = TRUE;
    setcookie('lc', '1');
}



/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Allows the profile to alter the 'set up database' form.
 * TODO: this is used a hacker method (via system module's method emulation) - need to be reworked
 */
function system_form_install_settings_form_alter(&$form, $form_state) {

    foreach($form['driver']['#options'] as $key=>$value) {
        if ('mysql' != $key) {
            unset($form['driver']['#options'][$key]);
        }
    }
    
    if (count($form['driver']['#options']) == 0) {
        unset($form['driver']);
        unset($form['settings']);
        unset($form['actions']);

        $form['msg'] = array(
            '#type' => 'fieldset',
            '#title' => 'Error',
            '#description' => st('Database could not be installed because the PHP configuration does not support MySQL (PDO extension with MySQL driver support required). Please check your PHP configuration and try again.'),
        );

    } else {

        $form['settings']['mysql']['advanced_options']['db_prefix']['#default_value'] = 'drupal_';
        $form['settings']['mysql']['advanced_options']['db_prefix']['#description'] = st('Drupal and LiteCommerce will share the same database; to distinguish between them, it is recommended to specify a prefix for the Drupal tables (e.g. \'drupal_\'). Note: LiteCommerce tables will be created with the \'xlite_\' prefix; therefore, please avoid using the same prefix for the Drupal tables.');

        $form['settings']['mysql']['advanced_options']['unix_socket'] = $form['settings']['mysql']['advanced_options']['port'];
        $form['settings']['mysql']['advanced_options']['unix_socket']['#title'] = st('Database socket');
        $form['settings']['mysql']['advanced_options']['unix_socket']['#description'] = st('If your database server uses a non-standard socket, specify it (e.g.: /tmp/mysql-5.1.34.sock). If specified, the socket will be used for connecting to the database server instead of host:port');
        $form['settings']['mysql']['advanced_options']['unix_socket']['#maxlength'] = 255;

        $form['driver']['#disabled'] = TRUE;
        $form['driver']['#required'] = FALSE;
        $form['driver']['#description'] = st('Type of database to be used for storing your Drupal and LiteCommerce data.');

        if (is_array($form['#validate'])) {
            array_unshift($form['#validate'], 'litecommerce_install_settings_form_validate');

        } else {
            $form['#validate'] = array('litecommerce_install_settings_form_validate');
        }
    }
}

/**
 * Postprocessing of the 'set up database' form
 * 
 * Extends an array $params by the data for final LC installation
 */
function litecommerce_install_settings_form_validate($form, &$form_state) {
    
    $unix_socket = trim($form_state['values']['mysql']['unix_socket']);

    if (empty($unix_socket)) {
        unset($form_state['values']['mysql']['unix_socket']);
    }

    $drupal_prefix = trim($form_state['values']['mysql']['db_prefix']);

    $xlite_prefix = \Includes\Utils\ConfigParser::getOptions(array('database_details', 'table_prefix'));

    if ($drupal_prefix == $xlite_prefix) {
        form_set_error('mysql][db_prefix', st('A prefix for the Drupal tables cannot be :db_prefix as it is reserved for the LiteCommerce tables.', array(':db_prefix' => $xlite_prefix)));
    }
}



/**
 * Implements the 'Set up LiteCommerce' form
 */
function litecommerce_setup_form($form, &$form_state, &$install_state) {

    drupal_set_title(st('Install LiteCommerce'));

    if (_litecommerce_include_lc_files()) {

        global $lcSettings;

        $form['litecommerce_settings'] = array(
            '#type' => 'fieldset',
            '#title' => st('LiteCommerce installation settings'),
            '#collapsible' => FALSE,
            '#description' => st('LiteCommerce software will be installed in the directory <i>:lcdir</i> <br />Please choose the installation options below and continue.<br /><br />', array(':lcdir' => lc_connector_get_litecommerce_dir())),
            '#weight' => 10,
        );

        $form['litecommerce_settings']['lc_install_demo'] = array(
            '#type' => 'checkbox',
            '#title' => st('Install sample catalog'),
            '#default_value' => '1',
            '#description' => st('Specify whether you would like to setup sample categories and products?'),
            '#weight' => 20,
        );

        $form['actions'] = array('#type' => 'actions');
        $form['actions']['save'] = array(
            '#type' => 'submit',
            '#value' => st('Save and continue'),
        );

        if (true == _litecommerce_is_lc_installed()) {

            $form['litecommerce_installed'] = array(
                '#type' => 'fieldset',
                '#title' => st('Existing LiteCommerce installation found'),
                '#collapsible' => FALSE,
                '#description' => st('An existing LiteCommerce installation has been found. If you choose to proceed, all the existing data will be lost.'),
                '#weight' => 5,
            );

            $form['litecommerce_installed']['lc_skip_installation'] = array(
                '#type' => 'checkbox',
                '#title' => st('Do not install LiteCommerce'),
                '#default_value' => '1',
                '#weight' => 10,
                '#attributes' => array('onClick' => "javascript: if (this.checked) document.getElementById('edit-litecommerce-settings').style.display='none'; else document.getElementById('edit-litecommerce-settings').style.display='block';"),
            );

            drupal_set_message(st('A previous LiteCommerce installation found!'), 'warning');


        }

    } else {

        $form['litecommerce_settings'] = array(
            '#type' => 'fieldset',
            '#title' => st('LiteCommerce installation settings'),
            '#description' => 'Installation cannot proceed because of an error'
        );
    }

    return $form;
}

/**
 * Processes the 'Set up LiteCommerce' form
 */
function litecommerce_setup_form_submit($form, &$form_state) {

    $params = _litecommerce_get_setup_params();

    $params['demo'] = isset($form_state['values']['lc_install_demo']) && !empty($form_state['values']['lc_install_demo']);

    if (isset($form_state['values']['lc_skip_installation']) && !empty($form_state['values']['lc_skip_installation'])) {
        variable_set('lc_skip_installation', true);
    }

    $params['setup_passed'] = true;

    variable_set('lc_setup_params', $params);
}



/**
 * Implements LiteCommerce installation batch process
 *
 * Prepares the array of actions that need to be completed during the installation process
 */
function litecommerce_software_install(&$install_state) {

    $batch = array();

    $skipLcInstallation = variable_get('lc_skip_installation', false);

    if (false == $skipLcInstallation) {

        $steps = array();

        $steps[] = array(
            'function' => 'doUpdateConfig',
            'message' => st('Config file updated'),
        );
        $steps[] = array(
            'function' => 'doInstallDirs',
            'message' => st('Directories installed'),
        );
        $steps[] = array(
            'function' => 'doRemoveCache',
            'message' => st('Prepare for building cache'),
        );
        $steps[] = array(
            'function' => 'doPrepareFixtures',
            'message' => st('Fixtures prepared'),
        );
        $steps[] = array(
            'function' => 'doBuildCache',
            'message' => st('Building cache. Pass 1'),
        );
        $steps[] = array(
            'function' => 'doBuildCache',
            'message' => st('Building cache. Pass 2'),
        );
        $steps[] = array(
            'function' => '_litecommerce_software_installation_postconfigure',
            'message' => st('LiteCommerce installation complete'),
        );

        $operations = array();

        foreach ($steps as $step) {
            $operations[] = array('_litecommerce_software_install_batch', array($step));
        }

        $batch = array(
            'operations' => $operations,
            'title' => st('Installing LiteCommerce'),
            'error_message' => st('An error occurred during the installation.'),
            'finished' => '_litecommerce_software_install_finished',
        );
    }

    return $batch;
}

/**
 * Performs the batch process step
 */
function _litecommerce_software_install_batch($step, &$context) {

    // Function name
    $function = $step['function']; 

    // Allow any output from functions
    $silentMode = false;

    if (_litecommerce_include_lc_files()) {

        if (function_exists($function)) {

            $params = _litecommerce_get_setup_params();

            // Suppress any direct output from the function
            ob_start();

            if (in_array($function, array('doUpdateConfig', 'doPrepareFixtures'))) {
                $result = $function($params);
            
            } else {
                $result = $function();
            }

            $output = ob_get_contents();

            ob_end_clean();

            x_install_log($function, array('result' => $result, 'output' => $output));

            if (false === $result) {
                // Print output and break the batch process if function is failed
                drupal_set_message(sprintf('Function %s failed.', $function), 'error');
                die($output);
            }

            $context['results'][] = $step['function'];
            $context['message'] = $step['message'];
        }
    }
}

/**
 * Performs the final actions of LiteCommerce installation process
 *
 * Updates an administrator profile with the specific Drupal data
 * and inserts the option 'drupal_root_url' to the LC config table
 */
function _litecommerce_software_installation_postconfigure() {

    $result = true;

    // Insert Drupal URL option into the xlite_config
    db_query('UPDATE xlite_config SET value = :value WHERE name = :name', array(':name' => 'drupal_root_url', ':value' => drupal_detect_baseurl()));

    return $result;
}

/**
 * Finish the LiteCommerce installation batch process
 */
function _litecommerce_software_install_finished($success, $results, $operations) {

    if (!$success) {
        drupal_set_message(st('LiteCommerce installation failed.'));
    
    } else {
        variable_set('is_litecommerce_installed', true);
    }
}



/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Allows the profile to alter the site configuration form.
 */
function litecommerce_form_install_configure_form_alter(&$form, $form_state) {

    // Pre-populate the site name with the server name.
    $form['site_information']['site_name']['#default_value'] = st('My Ecommerce CMS');

    if (is_array($form['#submit'])) {
        array_push($form['#submit'], 'litecommerce_form_install_configure_form_submit');

    } else {
        $form['#submit'] = array('litecommerce_form_install_configure_form_submit');
    }
}

/**
 * Postprocessing of the site configuration form
 * 
 * Extends an array $params by the data for final LC installation
 */
function litecommerce_form_install_configure_form_submit($form, &$form_state) {

    $result = false;

    $params = _litecommerce_get_setup_params();

    $params['name'] = trim($form_state['values']['account']['name']); // Admin username
    $params['login'] = trim($form_state['values']['account']['mail']); // Admin e-mail
    $params['password'] = trim($form_state['values']['account']['pass']); // Admin password
    $params['site_name'] = trim($form_state['values']['site_name']); // Site name
    $params['site_mail'] = trim($form_state['values']['site_mail']); // Site e-mail
    $params['site_default_country'] = trim($form_state['values']['site_default_country']); // Site default country

    variable_set('lc_setup_params', $params);

    if (_litecommerce_include_lc_files()) {

        ob_start();
        $result = doCreateAdminAccount($params);
        $output = ob_get_contents();
        ob_end_clean();
    }

    if (false === $result) {
        drupal_set_message(st('Creation of LiteCommerce administrator account failed. <br />' . $output), 'error');
    
    } else {
        // Update LiteCommerce admin profile with additional data
        db_query('UPDATE xlite_profiles SET cms_profile_id = :cms_profile_id, cms_name = :cms_name WHERE login = :login AND access_level = :access_level', array('cms_profile_id' => 1, 'cms_name' => '____DRUPAL____', 'login' => $params['login'], 'access_level' => 100));
    }

    foreach(array('lc_skip_installation', 'is_litecommerce_installed') as $var) {
        variable_set($var, null);
    }

}



/**
 * Checks availability and includes LiteCommerce installation scripts
 */
function _litecommerce_include_lc_files() {

    $result = false;

    $lc_install_file = detect_lc_connector_uri() . DIRECTORY_SEPARATOR . 'lc_connector.install';

    if (file_exists($lc_install_file)) {

        require_once $lc_install_file;

        $errorMsg = LCConnector_Install::includeLCFiles();

        if (!empty($errorMsg)) {
            drupal_set_message(st('Failed to include LiteCommerce files'), 'error');
        
        } else {
            $result = true;
        }
    }
    
    return $result;
}

/**
 * Detects an LC Connector module directory
 *
 * Returns a realpath of a directory or URI or empty string if module not found
 * 
 * @param bool $realpath If true then realpath of the directory will be returned. Else a URI will be returned
 */
function detect_lc_connector_uri($realpath = false) {

    $result = &drupal_static(__FUNCTION__, null);

    if (!isset($result)) {

        $files = drupal_system_listing('/^lc_connector\.info$/', 'modules', 'name', 0);

        if (!empty($files)) {
            $result = dirname($files['lc_connector']->uri);
        }

        if (!isset($result) || FALSE == realpath($result)) {
            drupal_set_message(st('Installation cannot continue because the LC Connector module could not be found. The module is required for installing the Ecommerce CMS package.'), 'error');
            $result = '';
        }
    }

    return (!empty($result) && $realpath ? realpath($result) : $result);
}

/**
 * Checks if LiteCommerce has already been installed
 */
function _litecommerce_is_lc_installed() {

//    $result = &drupal_static(__FUNCTION__, null);

    $result = null;

    if (!isset($result)) {

        if (_litecommerce_include_lc_files()) {

            $params = _litecommerce_get_setup_params();
            $message = null;

            $result = isLiteCommerceInstalled($params, $message);
        }
    }

    return $result;
}

/**
 * Prepare array of LiteCommerce setup parameters
 */
function _litecommerce_get_setup_params() {

    $lc_install_file = detect_lc_connector_uri() . DIRECTORY_SEPARATOR . 'lc_connector.install';

    if (file_exists($lc_install_file)) {

        require_once $lc_install_file;

        $params = variable_get('lc_setup_params');

        $dbParams = lc_connector_get_database_params();

        if (empty($params) && !empty($dbParams)) {

            $params = $dbParams;

            $url = parse_url(drupal_detect_baseurl() . '/modules/lc_connector/litecommerce');
            $params['xlite_http_host'] = $url['host'] . (!empty($url['port']) ? ':' . $url['port'] : '');
            $params['xlite_web_dir'] = $url['path'];
        }
    
    } else {
        $params = array();
        drupal_set_message(st('LC Connector module not found (:file)', array(':file' => $lc_install_file)), 'error');
    }

    return $params;
}

