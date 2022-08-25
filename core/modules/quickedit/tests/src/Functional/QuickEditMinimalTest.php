<?php

namespace Drupal\Tests\quickedit\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Quick Edit can be installed with Minimal.
 *
 * @group quickedit
 * @group legacy
 */
class QuickEditMinimalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'quickedit',
    'quickedit_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that Quick Edit works with no admin theme.
   *
   * @see \quickedit_library_info_alter()
   */
  public function testSuccessfulInstall() {
    $editor_user = $this->drupalCreateUser([
      'access in-place editing',
    ]);
    $this->drupalLogin($editor_user);
    $this->assertSame('', $this->config('system.theme')->get('admin'), 'There is no admin theme set on the site.');
  }

}
