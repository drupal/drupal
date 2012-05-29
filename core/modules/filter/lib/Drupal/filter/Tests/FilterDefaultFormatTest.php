<?php

/**
 * @file
 * Definition of Drupal\filter\Tests\FilterDefaultFormatTest.
 */

namespace Drupal\filter\Tests;

use Drupal\simpletest\WebTestBase;

class FilterDefaultFormatTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Default text format functionality',
      'description' => 'Test the default text formats for different users.',
      'group' => 'Filter',
    );
  }

  function testDefaultTextFormats() {
    // Create two text formats, and two users. The first user has access to
    // both formats, but the second user only has access to the second one.
    $admin_user = $this->drupalCreateUser(array('administer filters'));
    $this->drupalLogin($admin_user);
    $formats = array();
    for ($i = 0; $i < 2; $i++) {
      $edit = array(
        'format' => drupal_strtolower($this->randomName()),
        'name' => $this->randomName(),
      );
      $this->drupalPost('admin/config/content/formats/add', $edit, t('Save configuration'));
      $this->resetFilterCaches();
      $formats[] = filter_format_load($edit['format']);
    }
    list($first_format, $second_format) = $formats;
    $first_user = $this->drupalCreateUser(array(filter_permission_name($first_format), filter_permission_name($second_format)));
    $second_user = $this->drupalCreateUser(array(filter_permission_name($second_format)));

    // Adjust the weights so that the first and second formats (in that order)
    // are the two lowest weighted formats available to any user.
    $minimum_weight = db_query("SELECT MIN(weight) FROM {filter_format}")->fetchField();
    $edit = array();
    $edit['formats[' . $first_format->format . '][weight]'] = $minimum_weight - 2;
    $edit['formats[' . $second_format->format . '][weight]'] = $minimum_weight - 1;
    $this->drupalPost('admin/config/content/formats', $edit, t('Save changes'));
    $this->resetFilterCaches();

    // Check that each user's default format is the lowest weighted format that
    // the user has access to.
    $this->assertEqual(filter_default_format($first_user), $first_format->format, t("The first user's default format is the lowest weighted format that the user has access to."));
    $this->assertEqual(filter_default_format($second_user), $second_format->format, t("The second user's default format is the lowest weighted format that the user has access to, and is different than the first user's."));

    // Reorder the two formats, and check that both users now have the same
    // default.
    $edit = array();
    $edit['formats[' . $second_format->format . '][weight]'] = $minimum_weight - 3;
    $this->drupalPost('admin/config/content/formats', $edit, t('Save changes'));
    $this->resetFilterCaches();
    $this->assertEqual(filter_default_format($first_user), filter_default_format($second_user), t('After the formats are reordered, both users have the same default format.'));
  }

  /**
   * Rebuild text format and permission caches in the thread running the tests.
   */
  protected function resetFilterCaches() {
    filter_formats_reset();
    $this->checkPermissions(array(), TRUE);
  }
}
