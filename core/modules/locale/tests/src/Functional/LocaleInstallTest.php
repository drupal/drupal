<?php

declare(strict_types=1);
namespace Drupal\Tests\locale\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test installation of Locale module.
 *
 * @group locale
 */
class LocaleInstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'file',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests Locale install message.
   */
  public function testLocaleInstallMessage(): void {
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer modules',
    ]);
    $this->drupalLogin($admin_user);

    $edit = [];
    $edit['modules[locale][enable]'] = 'locale';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');

    $this->assertSession()->statusMessageContains('available translations', 'status');
  }

}
