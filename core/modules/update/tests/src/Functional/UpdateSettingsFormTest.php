<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the the update_settings form.
 *
 * @group update
 * @group Form
 */
class UpdateSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the update_settings form.
   */
  public function testUpdateSettingsForm() {
    $url = Url::fromRoute('update.settings');

    // Users without the appropriate permissions should not be able to access.
    $this->drupalGet($url);
    $this->assertSession()->pageTextContains('Access denied');

    // Users with permission should be able to access the form.
    $permissions = ['administer site configuration'];
    $account = $this->setUpCurrentUser([
      'name' => 'system_admin',
      'pass' => 'adminPass',
    ], $permissions);
    $this->drupalLogin($account);
    $this->drupalGet($url);
    $this->assertSession()->fieldExists('update_notify_emails');

    $values_to_enter = [
      'http://example.com',
      'sofie@example.com',
      'http://example.com/also-not-an-email-address',
      'dries@example.com',
    ];

    // Fill in `http://example.com` as the email address to notify. We expect
    // this to trigger a validation error, because it's not an email address,
    // and for the corresponding form item to be highlighted.
    $this->assertSession()->fieldExists('update_notify_emails')->setValue($values_to_enter[0]);
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->statusMessageNotExists(MessengerInterface::TYPE_STATUS);
    $this->assertSession()->statusMessageNotExists(MessengerInterface::TYPE_WARNING);
    $this->assertSession()->statusMessageContains('"http://example.com" is not a valid email address.', MessengerInterface::TYPE_ERROR);
    $this->assertTrue($this->assertSession()->fieldExists('update_notify_emails')->hasClass('error'));
    $this->assertSame([], $this->config('update.settings')->get('notification.emails'));

    // Next, set an invalid email addresses, but make sure it's second entry.
    $this->assertSession()->fieldExists('update_notify_emails')->setValue(implode("\n", array_slice($values_to_enter, 1, 2)));
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->statusMessageNotExists(MessengerInterface::TYPE_STATUS);
    $this->assertSession()->statusMessageNotExists(MessengerInterface::TYPE_WARNING);
    $this->assertSession()->statusMessageContains('"http://example.com/also-not-an-email-address" is not a valid email address.', MessengerInterface::TYPE_ERROR);
    $this->assertTrue($this->assertSession()->fieldExists('update_notify_emails')->hasClass('error'));
    $this->assertSame([], $this->config('update.settings')->get('notification.emails'));

    // Next, set multiple invalid email addresses, and assert the same as above
    // except the message should be adjusted now.
    $this->assertSession()->fieldExists('update_notify_emails')->setValue(implode("\n", $values_to_enter));
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->statusMessageNotExists(MessengerInterface::TYPE_STATUS);
    $this->assertSession()->statusMessageNotExists(MessengerInterface::TYPE_WARNING);
    $this->assertSession()->statusMessageContains('http://example.com, http://example.com/also-not-an-email-address are not valid email addresses.', MessengerInterface::TYPE_ERROR);
    $this->assertTrue($this->assertSession()->fieldExists('update_notify_emails')->hasClass('error'));
    $this->assertSame([], $this->config('update.settings')->get('notification.emails'));

    // Now fill in valid email addresses, now the form should be saved
    // successfully.
    $this->assertSession()->fieldExists('update_notify_emails')->setValue("$values_to_enter[1]\r\n$values_to_enter[3]");
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->statusMessageContains('The configuration options have been saved.', MessengerInterface::TYPE_STATUS);
    $this->assertSession()->statusMessageNotExists(MessengerInterface::TYPE_WARNING);
    $this->assertSession()->statusMessageNotExists(MessengerInterface::TYPE_ERROR);
    $this->assertFalse($this->assertSession()->fieldExists('update_notify_emails')->hasClass('error'));
    $this->assertSame(['sofie@example.com', 'dries@example.com'], $this->config('update.settings')->get('notification.emails'));
  }

}
