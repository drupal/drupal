<?php

namespace Drupal\Tests\filter\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the default text formats for different users.
 *
 * @group filter
 */
class FilterDefaultFormatTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter'];

  /**
   * Tests if the default text format is accessible to users.
   */
  public function testDefaultTextFormats() {
    // Create two text formats, and two users. The first user has access to
    // both formats, but the second user only has access to the second one.
    $admin_user = $this->drupalCreateUser(['administer filters']);
    $this->drupalLogin($admin_user);
    $formats = [];
    for ($i = 0; $i < 2; $i++) {
      $edit = [
        'format' => mb_strtolower($this->randomMachineName()),
        'name' => $this->randomMachineName(),
      ];
      $this->drupalPostForm('admin/config/content/formats/add', $edit, t('Save configuration'));
      $this->resetFilterCaches();
      $formats[] = FilterFormat::load($edit['format']);
    }
    list($first_format, $second_format) = $formats;
    $second_format_permission = $second_format->getPermissionName();
    $first_user = $this->drupalCreateUser([$first_format->getPermissionName(), $second_format_permission]);
    $second_user = $this->drupalCreateUser([$second_format_permission]);

    // Adjust the weights so that the first and second formats (in that order)
    // are the two lowest weighted formats available to any user.
    $edit = [];
    $edit['formats[' . $first_format->id() . '][weight]'] = -2;
    $edit['formats[' . $second_format->id() . '][weight]'] = -1;
    $this->drupalPostForm('admin/config/content/formats', $edit, t('Save'));
    $this->resetFilterCaches();

    // Check that each user's default format is the lowest weighted format that
    // the user has access to.
    $actual = filter_default_format($first_user);
    $expected = $first_format->id();
    $this->assertEqual($actual, $expected, "First user's default format $actual is the expected lowest weighted format $expected that the user has access to.");
    $actual = filter_default_format($second_user);
    $expected = $second_format->id();
    $this->assertEqual($actual, $expected, "Second user's default format $actual is the expected lowest weighted format $expected that the user has access to, and different to the first user's.");

    // Reorder the two formats, and check that both users now have the same
    // default.
    $edit = [];
    $edit['formats[' . $second_format->id() . '][weight]'] = -3;
    $this->drupalPostForm('admin/config/content/formats', $edit, t('Save'));
    $this->resetFilterCaches();
    $this->assertEqual(filter_default_format($first_user), filter_default_format($second_user), 'After the formats are reordered, both users have the same default format.');
  }

  /**
   * Rebuilds text format and permission caches in the thread running the tests.
   */
  protected function resetFilterCaches() {
    filter_formats_reset();
  }

}
