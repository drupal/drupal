<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Tests the system_regional_settings form.
 *
 * @group system
 * @covers \Drupal\system\Form\RegionalForm
 */
class RegionalSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminAccount;

  /**
   * Tests the system_regional_settings form.
   */
  public function testRegionalSettingsForm(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/config/regional/settings');
    $assert_session->statusCodeEquals(403);

    $this->adminAccount = $this->setUpCurrentUser([], ['administer site configuration']);
    $this->drupalLogin($this->adminAccount);
    $this->drupalGet('admin/config/regional/settings');
    $assert_session->statusCodeEquals(200);
    $assert_session->elementTextEquals('css', 'main h1', 'Regional settings');

    // Check for available fields and submit default form.
    $assert_session->fieldValueEquals('edit-site-default-country', '');
    // @see \Drupal\Core\Test\FunctionalTestSetupTrait::initConfig().
    $assert_session->fieldValueEquals('edit-date-default-timezone', 'Australia/Sydney');
    $this->submitForm([], 'Save configuration');
    $assert_session->pageTextContains('The configuration options have been saved.');

    $edit = [
      'site_default_country' => 'US',
      'date_first_day' => 4,
      'date_default_timezone' => 'America/Chicago',
      'empty_timezone_message' => FALSE,
      'user_default_timezone' => 2,
      'configurable_timezones' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');
    $assert_session->pageTextContains('The configuration options have been saved.');
    // Check if values are properly displayed on form.
    $assert_session->fieldValueEquals('site_default_country', 'US');
    $assert_session->fieldValueEquals('date_first_day', 4);
    $assert_session->fieldValueEquals('date_default_timezone', 'America/Chicago');
    $assert_session->checkboxNotChecked('empty_timezone_message');
    $assert_session->fieldValueEquals('user_default_timezone', 2);
    $assert_session->checkboxChecked('configurable_timezones');

    // Also check saved configuration.
    $date_config = $this->config('system.date');
    $this->assertSame('US', $date_config->get('country.default'));
    $this->assertSame(4, $date_config->get('first_day'));
    $this->assertSame('America/Chicago', $date_config->get('timezone.default'));
    $this->assertFalse($date_config->get('timezone.user.warn'));
    $this->assertSame(2, $date_config->get('timezone.user.default'));
    $this->assertTrue($date_config->get('timezone.user.configurable'));
  }

}
