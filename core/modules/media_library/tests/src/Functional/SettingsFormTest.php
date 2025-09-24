<?php

declare(strict_types=1);

namespace Drupal\Tests\media_library\Functional;

use Drupal\media_library\Form\SettingsForm;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Media Library settings form.
 */
#[CoversClass(SettingsForm::class)]
#[Group('media_library')]
#[RunTestsInSeparateProcesses]
class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media_library'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the Media Library settings form.
   */
  public function testSettingsForm(): void {
    $account = $this->drupalCreateUser([
      'access administration pages',
      'administer media',
    ]);
    $this->drupalLogin($account);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/admin/config');
    $page->clickLink('Media Library settings');
    $page->checkField('Enable advanced UI');
    $page->pressButton('Save configuration');
    $assert_session->checkboxChecked('Enable advanced UI');
    $page->uncheckField('Enable advanced UI');
    $page->pressButton('Save configuration');
    $assert_session->checkboxNotChecked('Enable advanced UI');
  }

}
