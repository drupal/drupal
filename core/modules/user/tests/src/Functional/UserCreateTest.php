<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the create user administration page.
 *
 * @group user
 */
class UserCreateTest extends BrowserTestBase {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['image'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Create a user through the administration interface and ensure that it
   * displays in the user list.
   */
  public function testUserAdd() {
    $user = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($user);

    $this->assertEquals(REQUEST_TIME, $user->getCreatedTime(), 'Creating a user sets default "created" timestamp.');
    $this->assertEquals(REQUEST_TIME, $user->getChangedTime(), 'Creating a user sets default "changed" timestamp.');

    // Create a field.
    $field_name = 'test_field';
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'user',
      'module' => 'image',
      'type' => 'image',
      'cardinality' => 1,
      'locked' => FALSE,
      'indexes' => ['target_id' => ['target_id']],
      'settings' => [
        'uri_scheme' => 'public',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'user',
      'label' => 'Picture',
      'bundle' => 'user',
      'description' => t('Your virtual face or picture.'),
      'required' => FALSE,
      'settings' => [
        'file_extensions' => 'png gif jpg jpeg',
        'file_directory' => 'pictures',
        'max_filesize' => '30 KB',
        'alt_field' => 0,
        'title_field' => 0,
        'max_resolution' => '85x85',
        'min_resolution' => '',
      ],
    ])->save();

    // Test user creation page for valid fields.
    $this->drupalGet('admin/people/create');
    $this->assertSession()->fieldValueEquals('edit-status-0', '1');
    $this->assertSession()->fieldValueEquals('edit-status-1', '1');
    $this->assertSession()->checkboxChecked('edit-status-1');

    // Test that browser autocomplete behavior does not occur.
    $this->assertNoRaw('data-user-info-from-browser');

    // Test that the password strength indicator displays.
    $config = $this->config('user.settings');

    $config->set('password_strength', TRUE)->save();
    $this->drupalGet('admin/people/create');
    $this->assertRaw(t('Password strength:'));

    $config->set('password_strength', FALSE)->save();
    $this->drupalGet('admin/people/create');
    $this->assertNoRaw(t('Password strength:'));

    // We create two users, notifying one and not notifying the other, to
    // ensure that the tests work in both cases.
    foreach ([FALSE, TRUE] as $notify) {
      $name = $this->randomMachineName();
      $edit = [
        'name' => $name,
        'mail' => $this->randomMachineName() . '@example.com',
        'pass[pass1]' => $pass = $this->randomString(),
        'pass[pass2]' => $pass,
        'notify' => $notify,
      ];
      $this->drupalGet('admin/people/create');
      $this->submitForm($edit, 'Create new account');

      if ($notify) {
        $this->assertSession()->pageTextContains('A welcome message with further instructions has been emailed to the new user ' . $edit['name'] . '.');
        $this->assertCount(1, $this->drupalGetMails(), 'Notification email sent');
      }
      else {
        $this->assertSession()->pageTextContains('Created a new user account for ' . $edit['name'] . '. No email has been sent.');
        $this->assertCount(0, $this->drupalGetMails(), 'Notification email not sent');
      }

      $this->drupalGet('admin/people');
      $this->assertSession()->pageTextContains($edit['name']);
      $user = user_load_by_name($name);
      $this->assertTrue($user->isActive(), 'User is not blocked');
    }

    // Test that the password '0' is considered a password.
    // @see https://www.drupal.org/node/2563751.
    $name = $this->randomMachineName();
    $edit = [
      'name' => $name,
      'mail' => $this->randomMachineName() . '@example.com',
      'pass[pass1]' => 0,
      'pass[pass2]' => 0,
      'notify' => FALSE,
    ];
    $this->drupalGet('admin/people/create');
    $this->submitForm($edit, 'Create new account');
    $this->assertSession()->pageTextContains("Created a new user account for $name. No email has been sent");
    $this->assertNoText('Password field is required');
  }

}
