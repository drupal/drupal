<?php

/**
 * @file
 * Contains \Drupal\system\Tests\InstallerTest.
 */

namespace Drupal\system\Tests;

use Drupal\Component\Utility\NestedArray;
use Drupal\simpletest\WebTestBase;

/**
 * Allows testing of the interactive installer.
 */
class InstallerTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Installer tests',
      'description' => 'Tests the interactive installer.',
      'group' => 'Installer',
    );
  }

  protected function setUp() {
    global $conf;

    // When running tests through the SimpleTest UI (vs. on the command line),
    // SimpleTest's batch conflicts with the installer's batch. Batch API does
    // not support the concept of nested batches (in which the nested is not
    // progressive), so we need to temporarily pretend there was no batch.
    // Back up the currently running SimpleTest batch.
    $this->originalBatch = batch_get();

    // Create the database prefix for this test.
    $this->prepareDatabasePrefix();

    // Prepare the environment for running tests.
    $this->prepareEnvironment();
    if (!$this->setupEnvironment) {
      return FALSE;
    }

    // Reset all statics and variables to perform tests in a clean environment.
    $conf = array();
    drupal_static_reset();

    // Change the database prefix.
    // All static variables need to be reset before the database prefix is
    // changed, since \Drupal\Core\Utility\CacheArray implementations attempt to
    // write back to persistent caches when they are destructed.
    $this->changeDatabasePrefix();
    if (!$this->setupDatabasePrefix) {
      return FALSE;
    }
    $variable_groups = array(
      'system.file' => array(
        'path.private' =>  $this->private_files_directory,
        'path.temporary' =>  $this->temp_files_directory,
      ),
      'locale.settings' =>  array(
        'translation.path' => $this->translation_files_directory,
      ),
    );
    foreach ($variable_groups as $config_base => $variables) {
      foreach ($variables as $name => $value) {
        NestedArray::setValue($GLOBALS['conf'], array_merge(array($config_base), explode('.', $name)), $value);
      }
    }
    $settings['conf_path'] = (object) array(
      'value' => $this->public_files_directory,
      'required' => TRUE,
    );
    $settings['config_directories'] = (object) array(
      'value' => array(),
      'required' => TRUE,
    );
    $this->writeSettings($settings);

    $this->drupalGet($GLOBALS['base_url'] . '/core/install.php?langcode=en&profile=minimal');
    $this->drupalPost(NULL, array(), 'Save and continue');
    // Reload config directories.
    include $this->public_files_directory . '/settings.php';
    $prefix = substr($this->public_files_directory, strlen(conf_path() . '/files/'));
    foreach ($config_directories as $type => $data) {
      $GLOBALS['config_directories'][$type]['path'] = $prefix . '/files/' . $data['path'];
    }
    $this->rebuildContainer();

    foreach ($variable_groups as $config_base => $variables) {
      $config = config($config_base);
      foreach ($variables as $name => $value) {
        $config->set($name, $value);
      }
      $config->save();
    }

    // Use the test mail class instead of the default mail handler class.
    config('system.mail')->set('interface.default', 'Drupal\Core\Mail\VariableLog')->save();

    drupal_set_time_limit($this->timeLimit);
    // When running from run-tests.sh we don't get an empty current path which
    // would indicate we're on the home page.
    $path = current_path();
    if (empty($path)) {
      _current_path('run-tests');
    }
    $this->setup = TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * During setup(), drupalPost calls refreshVariables() which tries to read
   * variables which are not yet there because the child Drupal is not yet
   * installed.
   */
  protected function refreshVariables() {
    if (!empty($this->setup)) {
      parent::refreshVariables();
    }
  }

  /**
   * {@inheritdoc}
   *
   * This override is necessary because the parent drupalGet() calls t(), which
   * is not available early during installation.
   */
  protected function drupalGet($path, array $options = array(), array $headers = array()) {
    // We are re-using a CURL connection here. If that connection still has
    // certain options set, it might change the GET into a POST. Make sure we
    // clear out previous options.
    $out = $this->curlExec(array(CURLOPT_HTTPGET => TRUE, CURLOPT_URL => $this->getAbsoluteUrl($path), CURLOPT_NOBODY => FALSE, CURLOPT_HTTPHEADER => $headers));
    $this->refreshVariables(); // Ensure that any changes to variables in the other thread are picked up.

    // Replace original page output with new output from redirected page(s).
    if ($new = $this->checkForMetaRefresh()) {
      $out = $new;
    }
    $this->verbose('GET request to: ' . $path .
                   '<hr />Ending URL: ' . $this->getUrl() .
                   '<hr />' . $out);
    return $out;
  }

  /**
   * Ensures that the user page is available after every test installation.
   */
  public function testInstaller() {
    $this->drupalGet('user');
    $this->assertResponse(200);
  }

}
