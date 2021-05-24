<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Tests registration of user under different configurations.
 *
 * @group user
 */
class UserRegistrationTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['field_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testRegistrationWithEmailVerification() {
    $config = $this->config('user.settings');
    // Require email verification.
    $config->set('verify_mail', TRUE)->save();

    // Set registration to administrator only and ensure the user registration
    // page is inaccessible.
    $config->set('register', UserInterface::REGISTER_ADMINISTRATORS_ONLY)->save();
    $this->drupalGet('user/register');
    $this->assertSession()->statusCodeEquals(403);

    // Allow registration by site visitors without administrator approval.
    $config->set('register', UserInterface::REGISTER_VISITORS)->save();
    $edit = [];
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $this->drupalGet('user/register');
    $this->submitForm($edit, 'Create new account');
    $this->assertSession()->pageTextContains('A welcome message with further instructions has been sent to your email address.');

    /** @var EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('user');
    $accounts = $storage->loadByProperties(['name' => $name, 'mail' => $mail]);
    $new_user = reset($accounts);
    $this->assertTrue($new_user->isActive(), 'New account is active after registration.');
    $resetURL = user_pass_reset_url($new_user);
    $this->drupalGet($resetURL);
    $this->assertSession()->titleEquals('Set password | Drupal');

    // Allow registration by site visitors, but require administrator approval.
    $config->set('register', UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)->save();
    $edit = [];
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $this->drupalGet('user/register');
    $this->submitForm($edit, 'Create new account');
    $this->container->get('entity_type.manager')->getStorage('user')->resetCache();
    $accounts = $storage->loadByProperties(['name' => $name, 'mail' => $mail]);
    $new_user = reset($accounts);
    $this->assertFalse($new_user->isActive(), 'New account is blocked until approved by an administrator.');
  }

  public function testRegistrationWithoutEmailVerification() {
    $config = $this->config('user.settings');
    // Don't require email verification and allow registration by site visitors
    // without administrator approval.
    $config
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();

    $edit = [];
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';

    // Try entering a mismatching password.
    $edit['pass[pass1]'] = '99999.0';
    $edit['pass[pass2]'] = '99999';
    $this->drupalGet('user/register');
    $this->submitForm($edit, 'Create new account');
    $this->assertSession()->pageTextContains('The specified passwords do not match.');

    // Enter a correct password.
    $edit['pass[pass1]'] = $new_pass = $this->randomMachineName();
    $edit['pass[pass2]'] = $new_pass;
    $this->drupalGet('user/register');
    $this->submitForm($edit, 'Create new account');
    $this->container->get('entity_type.manager')->getStorage('user')->resetCache();
    $accounts = $this->container->get('entity_type.manager')->getStorage('user')
      ->loadByProperties(['name' => $name, 'mail' => $mail]);
    $new_user = reset($accounts);
    $this->assertNotNull($new_user, 'New account successfully created with matching passwords.');
    $this->assertSession()->pageTextContains('Registration successful. You are now logged in.');
    $this->drupalLogout();

    // Allow registration by site visitors, but require administrator approval.
    $config->set('register', UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)->save();
    $edit = [];
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $edit['pass[pass1]'] = $pass = $this->randomMachineName();
    $edit['pass[pass2]'] = $pass;
    $this->drupalGet('user/register');
    $this->submitForm($edit, 'Create new account');
    $this->assertSession()->pageTextContains('Thank you for applying for an account. Your account is currently pending approval by the site administrator.');

    // Try to log in before administrator approval.
    $auth = [
      'name' => $name,
      'pass' => $pass,
    ];
    $this->drupalGet('user/login');
    $this->submitForm($auth, 'Log in');
    $this->assertSession()->pageTextContains('The username ' . $name . ' has not been activated or is blocked.');

    // Activate the new account.
    $accounts = $this->container->get('entity_type.manager')->getStorage('user')
      ->loadByProperties(['name' => $name, 'mail' => $mail]);
    $new_user = reset($accounts);
    $admin_user = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($admin_user);
    $edit = [
      'status' => 1,
    ];
    $this->drupalGet('user/' . $new_user->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->drupalLogout();

    // Log in after administrator approval.
    $this->drupalGet('user/login');
    $this->submitForm($auth, 'Log in');
    $this->assertSession()->pageTextContains('Member for');
  }

  public function testRegistrationEmailDuplicates() {
    // Don't require email verification and allow registration by site visitors
    // without administrator approval.
    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();

    // Set up a user to check for duplicates.
    $duplicate_user = $this->drupalCreateUser();

    $edit = [];
    $edit['name'] = $this->randomMachineName();
    $edit['mail'] = $duplicate_user->getEmail();

    // Attempt to create a new account using an existing email address.
    $this->drupalGet('user/register');
    $this->submitForm($edit, 'Create new account');
    $this->assertSession()->pageTextContains('The email address ' . $duplicate_user->getEmail() . ' is already taken.');

    // Attempt to bypass duplicate email registration validation by adding spaces.
    $edit['mail'] = '   ' . $duplicate_user->getEmail() . '   ';

    $this->drupalGet('user/register');
    $this->submitForm($edit, 'Create new account');
    $this->assertSession()->pageTextContains('The email address ' . $duplicate_user->getEmail() . ' is already taken.');
  }

  /**
   * Tests that UUID isn't cached in form state on register form.
   *
   * This is a regression test for https://www.drupal.org/node/2500527 to ensure
   * that the form is not cached on GET requests.
   */
  public function testUuidFormState() {
    \Drupal::service('module_installer')->install(['image']);

    // Add a picture field in order to ensure that no form cache is written,
    // which breaks registration of more than 1 user every 6 hours.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'user_picture',
      'entity_type' => 'user',
      'type' => 'image',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'user_picture',
      'entity_type' => 'user',
      'bundle' => 'user',
    ]);
    $field->save();

    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'user',
      'bundle' => 'user',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $form_display->setComponent('user_picture', [
      'type' => 'image_image',
    ]);
    $form_display->save();

    // Don't require email verification and allow registration by site visitors
    // without administrator approval.
    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();

    $edit = [];
    $edit['name'] = $this->randomMachineName();
    $edit['mail'] = $edit['name'] . '@example.com';
    $edit['pass[pass2]'] = $edit['pass[pass1]'] = $this->randomMachineName();

    // Create one account.
    $this->drupalGet('user/register');
    $this->submitForm($edit, 'Create new account');
    $this->assertSession()->statusCodeEquals(200);

    $user_storage = \Drupal::entityTypeManager()->getStorage('user');

    $this->assertNotEmpty($user_storage->loadByProperties(['name' => $edit['name']]));
    $this->drupalLogout();

    // Create a second account.
    $edit['name'] = $this->randomMachineName();
    $edit['mail'] = $edit['name'] . '@example.com';
    $edit['pass[pass2]'] = $edit['pass[pass1]'] = $this->randomMachineName();

    $this->drupalGet('user/register');
    $this->submitForm($edit, 'Create new account');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertNotEmpty($user_storage->loadByProperties(['name' => $edit['name']]));
  }

  public function testRegistrationDefaultValues() {
    // Don't require email verification and allow registration by site visitors
    // without administrator approval.
    $config_user_settings = $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();

    // Set the default timezone to Brussels.
    $config_system_date = $this->config('system.date')
      ->set('timezone.user.configurable', 1)
      ->set('timezone.default', 'Europe/Brussels')
      ->save();

    // Check the presence of expected cache tags.
    $this->drupalGet('user/register');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:user.settings');

    $edit = [];
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $edit['pass[pass1]'] = $new_pass = $this->randomMachineName();
    $edit['pass[pass2]'] = $new_pass;
    $this->submitForm($edit, 'Create new account');

    // Check user fields.
    $accounts = $this->container->get('entity_type.manager')->getStorage('user')
      ->loadByProperties(['name' => $name, 'mail' => $mail]);
    $new_user = reset($accounts);
    $this->assertEquals($name, $new_user->getAccountName(), 'Username matches.');
    $this->assertEquals($mail, $new_user->getEmail(), 'Email address matches.');
    // Verify that the creation time is correct.
    $this->assertGreaterThan(REQUEST_TIME - 20, $new_user->getCreatedTime());
    $this->assertEquals($config_user_settings->get('register') == UserInterface::REGISTER_VISITORS ? 1 : 0, $new_user->isActive(), 'Correct status field.');
    $this->assertEquals($config_system_date->get('timezone.default'), $new_user->getTimezone(), 'Correct time zone field.');
    $this->assertEquals(\Drupal::languageManager()->getDefaultLanguage()->getId(), $new_user->langcode->value, 'Correct language field.');
    $this->assertEquals(\Drupal::languageManager()->getDefaultLanguage()->getId(), $new_user->preferred_langcode->value, 'Correct preferred language field.');
    $this->assertEquals($mail, $new_user->init->value, 'Correct init field.');
  }

  /**
   * Tests username and email field constraints on user registration.
   *
   * @see \Drupal\user\Plugin\Validation\Constraint\UserNameUnique
   * @see \Drupal\user\Plugin\Validation\Constraint\UserMailUnique
   */
  public function testUniqueFields() {
    $account = $this->drupalCreateUser();

    $edit = ['mail' => 'test@example.com', 'name' => $account->getAccountName()];
    $this->drupalGet('user/register');
    $this->submitForm($edit, 'Create new account');
    $this->assertRaw(new FormattableMarkup('The username %value is already taken.', ['%value' => $account->getAccountName()]));

    $edit = ['mail' => $account->getEmail(), 'name' => $this->randomString()];
    $this->drupalGet('user/register');
    $this->submitForm($edit, 'Create new account');
    $this->assertRaw(new FormattableMarkup('The email address %value is already taken.', ['%value' => $account->getEmail()]));
  }

  /**
   * Tests Field API fields on user registration forms.
   */
  public function testRegistrationWithUserFields() {
    // Create a field on 'user' entity type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_user_field',
      'entity_type' => 'user',
      'type' => 'test_field',
      'cardinality' => 1,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'label' => 'Some user field',
      'bundle' => 'user',
      'required' => TRUE,
    ]);
    $field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('user', 'user')
      ->setComponent('test_user_field', ['type' => 'test_field_widget'])
      ->save();
    $display_repository->getFormDisplay('user', 'user', 'register')
      ->save();

    // Check that the field does not appear on the registration form.
    $this->drupalGet('user/register');
    $this->assertNoText($field->label());
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:core.entity_form_display.user.user.register');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:user.settings');

    // Have the field appear on the registration form.
    $display_repository->getFormDisplay('user', 'user', 'register')
      ->setComponent('test_user_field', ['type' => 'test_field_widget'])
      ->save();

    $this->drupalGet('user/register');
    $this->assertSession()->pageTextContains($field->label());
    $this->assertRegistrationFormCacheTagsWithUserFields();

    // Check that validation errors are correctly reported.
    $edit = [];
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    // Missing input in required field.
    $edit['test_user_field[0][value]'] = '';
    $this->submitForm($edit, 'Create new account');
    $this->assertRegistrationFormCacheTagsWithUserFields();
    $this->assertRaw(t('@name field is required.', ['@name' => $field->label()]));
    // Invalid input.
    $edit['test_user_field[0][value]'] = '-1';
    $this->submitForm($edit, 'Create new account');
    $this->assertRegistrationFormCacheTagsWithUserFields();
    $this->assertRaw(t('%name does not accept the value -1.', ['%name' => $field->label()]));

    // Submit with valid data.
    $value = rand(1, 255);
    $edit['test_user_field[0][value]'] = $value;
    $this->submitForm($edit, 'Create new account');
    // Check user fields.
    $accounts = $this->container->get('entity_type.manager')->getStorage('user')
      ->loadByProperties(['name' => $name, 'mail' => $mail]);
    $new_user = reset($accounts);
    $this->assertEquals($value, $new_user->test_user_field->value, 'The field value was correctly saved.');

    // Check that the 'add more' button works.
    $field_storage->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $field_storage->save();
    $this->drupalGet('user/register');
    $this->assertRegistrationFormCacheTagsWithUserFields();
    // Add two inputs.
    $value = rand(1, 255);
    $edit = [];
    $edit['test_user_field[0][value]'] = $value;
    $this->submitForm($edit, 'Add another item');
    $this->submitForm($edit, 'Add another item');
    // Submit with three values.
    $edit['test_user_field[1][value]'] = $value + 1;
    $edit['test_user_field[2][value]'] = $value + 2;
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $this->submitForm($edit, 'Create new account');
    // Check user fields.
    $accounts = $this->container->get('entity_type.manager')->getStorage('user')
      ->loadByProperties(['name' => $name, 'mail' => $mail]);
    $new_user = reset($accounts);
    $this->assertEquals($value, $new_user->test_user_field[0]->value, 'The field value was correctly saved.');
    $this->assertEquals($value + 1, $new_user->test_user_field[1]->value, 'The field value was correctly saved.');
    $this->assertEquals($value + 2, $new_user->test_user_field[2]->value, 'The field value was correctly saved.');
  }

  /**
   * Asserts the presence of cache tags on registration form with user fields.
   */
  protected function assertRegistrationFormCacheTagsWithUserFields() {
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:core.entity_form_display.user.user.register');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:field.field.user.user.test_user_field');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:field.storage.user.test_user_field');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:user.settings');
  }

}
