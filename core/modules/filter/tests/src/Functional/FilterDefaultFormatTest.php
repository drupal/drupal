<?php

declare(strict_types=1);

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
   * {@inheritdoc}
   */
  protected static $modules = ['filter'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests if the default text format is accessible to users.
   */
  public function testDefaultTextFormats(): void {
    // Create two text formats, and two users. The first user has access to
    // both formats, but the second user only has access to the second one.
    $admin_user = $this->drupalCreateUser(['administer filters']);
    $this->drupalLogin($admin_user);
    $formats = [];
    for ($i = 0; $i < 2; $i++) {
      $edit = [
        'format' => $this->randomMachineName(),
        'name' => $this->randomMachineName(),
      ];
      $this->drupalGet('admin/config/content/formats/add');
      $this->submitForm($edit, 'Save configuration');
      $this->resetFilterCaches();
      $formats[] = FilterFormat::load($edit['format']);
    }
    [$first_format, $second_format] = $formats;
    $second_format_permission = $second_format->getPermissionName();
    $first_user = $this->drupalCreateUser([
      $first_format->getPermissionName(),
      $second_format_permission,
    ]);
    $second_user = $this->drupalCreateUser([$second_format_permission]);

    // Adjust the weights so that the first and second formats (in that order)
    // are the two lowest weighted formats available to any user.
    $edit = [];
    $edit['formats[' . $first_format->id() . '][weight]'] = -2;
    $edit['formats[' . $second_format->id() . '][weight]'] = -1;
    $this->drupalGet('admin/config/content/formats');
    $this->submitForm($edit, 'Save');
    $this->resetFilterCaches();

    // Check that each user's default format is the lowest weighted format that
    // the user has access to.
    $actual = filter_default_format($first_user);
    $expected = $first_format->id();
    $this->assertEquals($expected, $actual, "First user's default format {$actual} is the expected lowest weighted format {$expected} that the user has access to.");
    $actual = filter_default_format($second_user);
    $expected = $second_format->id();
    $this->assertEquals($expected, $actual, "Second user's default format {$actual} is the expected lowest weighted format {$expected} that the user has access to, and different to the first user's.");

    // Reorder the two formats, and check that both users now have the same
    // default.
    $edit = [];
    $edit['formats[' . $second_format->id() . '][weight]'] = -3;
    $this->drupalGet('admin/config/content/formats');
    $this->submitForm($edit, 'Save');
    $this->resetFilterCaches();
    $this->assertEquals(filter_default_format($first_user), filter_default_format($second_user), 'After the formats are reordered, both users have the same default format.');
  }

  /**
   * Rebuilds text format and permission caches in the thread running the tests.
   */
  protected function resetFilterCaches(): void {
    filter_formats_reset();
  }

}
