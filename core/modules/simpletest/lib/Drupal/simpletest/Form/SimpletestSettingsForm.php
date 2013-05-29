<?php
/**
 * @file
 * Contains \Drupal\simpletest\Form\SimpletestSettingsForm.
 */

namespace Drupal\simpletest\Form;

use Drupal\system\SystemConfigFormBase;

/**
 * Configure simpletest settings for this site.
 */
class SimpletestSettingsForm extends SystemConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'simpletest_settings_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('simpletest.settings');
    $form['general'] = array(
      '#type' => 'details',
      '#title' => t('General'),
    );
    $form['general']['simpletest_clear_results'] = array(
      '#type' => 'checkbox',
      '#title' => t('Clear results after each complete test suite run'),
      '#description' => t('By default SimpleTest will clear the results after they have been viewed on the results page, but in some cases it may be useful to leave the results in the database. The results can then be viewed at <em>admin/config/development/testing/results/[test_id]</em>. The test ID can be found in the database, simpletest table, or kept track of when viewing the results the first time. Additionally, some modules may provide more analysis or features that require this setting to be disabled.'),
      '#default_value' => $config->get('clear_results'),
    );
    $form['general']['simpletest_verbose'] = array(
      '#type' => 'checkbox',
      '#title' => t('Provide verbose information when running tests'),
      '#description' => t('The verbose data will be printed along with the standard assertions and is useful for debugging. The verbose data will be erased between each test suite run. The verbose data output is very detailed and should only be used when debugging.'),
      '#default_value' => $config->get('verbose'),
    );

    $form['httpauth'] = array(
      '#type' => 'details',
      '#title' => t('HTTP authentication'),
      '#description' => t('HTTP auth settings to be used by the SimpleTest browser during testing. Useful when the site requires basic HTTP authentication.'),
      '#collapsed' => TRUE,
    );
    $form['httpauth']['simpletest_httpauth_method'] = array(
      '#type' => 'select',
      '#title' => t('Method'),
      '#options' => array(
        CURLAUTH_BASIC => t('Basic'),
        CURLAUTH_DIGEST => t('Digest'),
        CURLAUTH_GSSNEGOTIATE => t('GSS negotiate'),
        CURLAUTH_NTLM => t('NTLM'),
        CURLAUTH_ANY => t('Any'),
        CURLAUTH_ANYSAFE => t('Any safe'),
      ),
      '#default_value' => $config->get('httpauth.method'),
    );
    $username = $config->get('httpauth.username');
    $password = $config->get('httpauth.password');
    $form['httpauth']['simpletest_httpauth_username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#default_value' => $username,
    );
    if (!empty($username) && !empty($password)) {
      $form['httpauth']['simpletest_httpauth_username']['#description'] = t('Leave this blank to delete both the existing username and password.');
    }
    $form['httpauth']['simpletest_httpauth_password'] = array(
      '#type' => 'password',
      '#title' => t('Password'),
    );
    if ($password) {
      $form['httpauth']['simpletest_httpauth_password']['#description'] = t('To change the password, enter the new password here.');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    $config = $this->configFactory->get('simpletest.settings');
    // If a username was provided but a password wasn't, preserve the existing
    // password.
    if (!empty($form_state['values']['simpletest_httpauth_username']) && empty($form_state['values']['simpletest_httpauth_password'])) {
      $form_state['values']['simpletest_httpauth_password'] = $config->get('httpauth.password');
    }

    // If a password was provided but a username wasn't, the credentials are
    // incorrect, so throw an error.
    if (empty($form_state['values']['simpletest_httpauth_username']) && !empty($form_state['values']['simpletest_httpauth_password'])) {
      form_set_error('simpletest_httpauth_username', t('HTTP authentication credentials must include a username in addition to a password.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('simpletest.settings')
      ->set('clear_results', $form_state['values']['simpletest_clear_results'])
      ->set('verbose', $form_state['values']['simpletest_verbose'])
      ->set('httpauth.method', $form_state['values']['simpletest_httpauth_method'])
      ->set('httpauth.username', $form_state['values']['simpletest_httpauth_username'])
      ->set('httpauth.password', $form_state['values']['simpletest_httpauth_password'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
