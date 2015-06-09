<?php

/**
 * @file
 * Contains Drupal\system\Tests\System\SettingsRewriteTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\Site\Settings;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the drupal_rewrite_settings() function.
 *
 * @group system
 */
class SettingsRewriteTest extends KernelTestBase {
  /**
   * Tests the drupal_rewrite_settings() function.
   */
  function testDrupalRewriteSettings() {
    include_once \Drupal::root() . '/core/includes/install.inc';
    $site_path = $this->container->get('site.path');
    $tests = array(
      array(
        'original' => '$no_index_value_scalar = TRUE;',
        'settings' => array(
          'no_index_value_scalar' => (object) array(
            'value' => FALSE,
            'comment' => 'comment',
          ),
        ),
        'expected' => '$no_index_value_scalar = false; // comment',
      ),
      array(
        'original' => '$no_index_value_scalar = TRUE;',
        'settings' => array(
          'no_index_value_foo' => array(
            'foo' => array(
              'value' => (object) array(
                'value' => NULL,
                'required' => TRUE,
                'comment' => 'comment',
              ),
            ),
          ),
        ),
        'expected' => <<<'EXPECTED'
$no_index_value_scalar = TRUE;
$no_index_value_foo['foo']['value'] = NULL; // comment
EXPECTED
      ),
      array(
        'original' => '$no_index_value_array = array("old" => "value");',
        'settings' => array(
          'no_index_value_array' => (object) array(
            'value' => FALSE,
            'required' => TRUE,
            'comment' => 'comment',
          ),
        ),
        'expected' => '$no_index_value_array = array("old" => "value");
$no_index_value_array = false; // comment',
      ),
      array(
        'original' => '$has_index_value_scalar["foo"]["bar"] = NULL;',
        'settings' => array(
          'has_index_value_scalar' => array(
            'foo' => array(
              'bar' => (object) array(
                'value' => FALSE,
                'required' => TRUE,
                'comment' => 'comment',
              ),
            ),
          ),
        ),
        'expected' => '$has_index_value_scalar["foo"]["bar"] = false; // comment',
      ),
      array(
        'original' => '$has_index_value_scalar["foo"]["bar"] = "foo";',
        'settings' => array(
          'has_index_value_scalar' => array(
            'foo' => array(
              'value' => (object) array(
                'value' => array('value' => 2),
                'required' => TRUE,
                'comment' => 'comment',
              ),
            ),
          ),
        ),
        'expected' => <<<'EXPECTED'
$has_index_value_scalar["foo"]["bar"] = "foo";
$has_index_value_scalar['foo']['value'] = array (
  'value' => 2,
); // comment
EXPECTED
      ),
    );
    foreach ($tests as $test) {
      $filename = Settings::get('file_public_path', $site_path . '/files') . '/mock_settings.php';
      file_put_contents(\Drupal::root() . '/' . $filename, "<?php\n" . $test['original'] . "\n");
      drupal_rewrite_settings($test['settings'], $filename);
      $this->assertEqual(file_get_contents(\Drupal::root() . '/' . $filename), "<?php\n" . $test['expected'] . "\n");
    }

    // Test that <?php gets added to the start of an empty settings file.
    // Set the array of settings that will be written to the file.
    $test = array(
      'settings' => array(
        'no_index' => (object) array(
          'value' => TRUE,
          'required' => TRUE,
        ),
      ),
      'expected' => '$no_index = true;'
    );
    // Make an empty file.
    $filename = Settings::get('file_public_path', $site_path . '/files') . '/mock_settings.php';
    file_put_contents(\Drupal::root() . '/' . $filename, "");

    // Write the setting to the file.
    drupal_rewrite_settings($test['settings'], $filename);

    // Check that the result is just the php opening tag and the settings.
    $this->assertEqual(file_get_contents(\Drupal::root() . '/' . $filename), "<?php\n" . $test['expected'] . "\n");
  }
}
