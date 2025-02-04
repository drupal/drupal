<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\FunctionalJavascript;

use Behat\Mink\Element\Element;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

// cspell:ignore navigationuser linksuserwrapper

/**
 * Tests for \Drupal\navigation\Plugin\NavigationBlock\NavigationUserBlock.
 *
 * @group navigation
 */
class NavigationUserBlockTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'navigation', 'test_page_test', 'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with permission to administer navigation blocks and access navigation.
   *
   * @var object
   */
  protected $adminUser;

  /**
   * An authenticated user to test navigation block caching.
   *
   * @var object
   */
  protected $normalUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an admin user, log in and enable test navigation blocks.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'access navigation',
      'change own username',
    ]);

    // Create additional users to test caching modes.
    $this->normalUser = $this->drupalCreateUser([
      'access navigation',
    ]);

    // Note that we don't need to setup a user navigation block b/c it's
    // installed by default.
  }

  /**
   * Test output of user navigation block with regards to contents.
   */
  public function testNavigationUserBlock(): void {
    $test_page_url = Url::fromRoute('test_page_test.test_page');
    // Login as a limited access user, and verify that the username is displayed
    // correctly.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet($test_page_url);

    // Wait for the default 'My Account' text to be replaced.
    $this->getSession()->getPage()->waitFor(10, function (Element $page) {
      return $page->find('css', '[aria-controls="navigation-link-navigationuser-linksuserwrapper"] > .toolbar-button__label')->getText() !== 'My Account';
    });
    // We should see the users name in the navigation menu.
    $rendered_user_name = $this->cssSelect('[aria-controls="navigation-link-navigationuser-linksuserwrapper"] > .toolbar-button__label')[0]->getText();
    $this->assertEquals($this->normalUser->getDisplayName(), $rendered_user_name);

    // Login as an admin access user, and verify that the username is displayed
    // correctly.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($test_page_url);
    // Wait for the default 'My Account' text to be replaced.
    $this->getSession()->getPage()->waitFor(10, function (Element $page) {
      return $page->find('css', '[aria-controls="navigation-link-navigationuser-linksuserwrapper"] > .toolbar-button__label')->getText() !== 'My Account';
    });
    // We should see the users name in the navigation menu.
    $rendered_user_name = $this->cssSelect('[aria-controls="navigation-link-navigationuser-linksuserwrapper"] > .toolbar-button__label')[0]->getText();
    $this->assertEquals($this->adminUser->getDisplayName(), $rendered_user_name);

    // Change the users name, assert that the changes reflect in the navigation.
    $new_username = $this->randomMachineName();
    $this->drupalGet('user/' . $this->adminUser->id() . '/edit');
    $this->submitForm(['name' => $new_username], 'Save');
    // Wait for the default 'My Account' text to be replaced.
    $this->getSession()->getPage()->waitFor(10, function (Element $page) {
      return $page->find('css', '[aria-controls="navigation-link-navigationuser-linksuserwrapper"] > .toolbar-button__label')->getText() !== 'My Account';
    });
    // We should see the users name in the navigation menu.
    $rendered_user_name = $this->cssSelect('[aria-controls="navigation-link-navigationuser-linksuserwrapper"] > .toolbar-button__label')[0]->getText();
    $this->assertEquals($new_username, $rendered_user_name);
  }

}
