<?php

namespace Drupal\Tests\user\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JS components added to the PasswordConfirm render element.
 *
 * @group user
 */
class PasswordWidgetThemeFunctionTest extends WebDriverTestBase {
  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'password_theme_function_test';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * User for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->assert = $this->assertSession();

    // Create a user.
    $this->testUser = $this->createUser();
    $this->drupalLogin($this->testUser);
  }

  /**
   * Tests password widget theme functions and its deprecations.
   *
   * @group legacy
   */
  public function testPasswordConfirmWidgetJsComponents() {
    $this->expectDeprecation('Javascript Deprecation: Returning <span> without data-drupal-selector="password-match-status-text" attribute is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/3152101');
    $this->expectDeprecation('Javascript Deprecation: The js-password-strength__indicator class is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Replace js-password-strength__indicator with a data-drupal-selector="password-strength-indicator" attribute. See https://www.drupal.org/node/3152101');
    $this->expectDeprecation('Javascript Deprecation: The js-password-strength__text class is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Replace js-password-strength__text with a data-drupal-selector="password-strength-text" attribute. See https://www.drupal.org/node/3152101');
    $this->expectDeprecation('Javascript Deprecation: The message property is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. The markup should be constructed using messageTips property and Drupal.theme.passwordSuggestions. See https://www.drupal.org/node/3130352');
    $assert_session = $this->assertSession();

    $this->drupalGet($this->testUser->toUrl('edit-form'));

    $this->assertNotNull($assert_session->waitForText('Overridden passwordStrength:'));
    $assert_session->elementTextContains('css', '.password-strength__meter', 'Overridden passwordStrength:');
    $assert_session->elementTextContains('css', '.password-confirm-message', 'Overridden passwordConfirmMessage:');
    $this->getSession()->getPage()->fillField('pass[pass1]', 'a');
    $assert_session->elementTextContains('css', '.password-suggestions', 'Overridden passwordSuggestions:');
  }

}
