<?php

namespace Drupal\KernelTests\Core\Site;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the drupal_rewrite_settings() function.
 *
 * @group system
 */
class SettingsRewriteTest extends KernelTestBase {

  /**
   * Tests the drupal_rewrite_settings() function.
   */
  public function testDrupalRewriteSettings() {
    include_once $this->root . '/core/includes/install.inc';
    $site_path = $this->container->get('site.path');
    $tests = [
      [
        'original' => '$no_index_value_scalar = TRUE;',
        'settings' => [
          'no_index_value_scalar' => (object) [
            'value' => FALSE,
            'comment' => 'comment',
          ],
        ],
        'expected' => '$no_index_value_scalar = false; // comment',
      ],
      [
        'original' => '$no_index_value_scalar = TRUE;',
        'settings' => [
          'no_index_value_foo' => [
            'foo' => [
              'value' => (object) [
                'value' => NULL,
                'required' => TRUE,
                'comment' => 'comment',
              ],
            ],
          ],
        ],
        'expected' => <<<'EXPECTED'
$no_index_value_scalar = TRUE;
$no_index_value_foo['foo']['value'] = NULL; // comment
EXPECTED
      ],
      [
        'original' => '$no_index_value_array = array("old" => "value");',
        'settings' => [
          'no_index_value_array' => (object) [
            'value' => FALSE,
            'required' => TRUE,
            'comment' => 'comment',
          ],
        ],
        'expected' => '$no_index_value_array = array("old" => "value");
$no_index_value_array = false; // comment',
      ],
      [
        'original' => '$has_index_value_scalar["foo"]["bar"] = NULL;',
        'settings' => [
          'has_index_value_scalar' => [
            'foo' => [
              'bar' => (object) [
                'value' => FALSE,
                'required' => TRUE,
                'comment' => 'comment',
              ],
            ],
          ],
        ],
        'expected' => '$has_index_value_scalar["foo"]["bar"] = false; // comment',
      ],
      [
        'original' => '$has_index_value_scalar["foo"]["bar"] = "foo";',
        'settings' => [
          'has_index_value_scalar' => [
            'foo' => [
              'value' => (object) [
                'value' => ['value' => 2],
                'required' => TRUE,
                'comment' => 'comment',
              ],
            ],
          ],
        ],
        'expected' => <<<'EXPECTED'
$has_index_value_scalar["foo"]["bar"] = "foo";
$has_index_value_scalar['foo']['value'] = array (
  'value' => 2,
); // comment
EXPECTED
      ],
    ];
    foreach ($tests as $test) {
      $filename = Settings::get('file_public_path', $site_path . '/files') . '/mock_settings.php';
      file_put_contents($filename, "<?php\n" . $test['original'] . "\n");
      drupal_rewrite_settings($test['settings'], $filename);
      $this->assertEqual(file_get_contents($filename), "<?php\n" . $test['expected'] . "\n");
    }

    // Test that <?php gets added to the start of an empty settings file.
    // Set the array of settings that will be written to the file.
    $test = [
      'settings' => [
        'no_index' => (object) [
          'value' => TRUE,
          'required' => TRUE,
        ],
      ],
      'expected' => '$no_index = true;',
    ];
    // Make an empty file.
    $filename = Settings::get('file_public_path', $site_path . '/files') . '/mock_settings.php';
    file_put_contents($filename, "");

    // Write the setting to the file.
    drupal_rewrite_settings($test['settings'], $filename);

    // Check that the result is just the php opening tag and the settings.
    $this->assertEqual(file_get_contents($filename), "<?php\n" . $test['expected'] . "\n");
  }

}
