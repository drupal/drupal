<?php

namespace Drupal\Tests\simpletest\Functional;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBaseTest;
use Drupal\Tests\action\Unit\Menu\ActionLocalTasksTest;
use Drupal\Tests\BrowserTestBase;

/**
 * Test various aspects of testing through the UI form.
 *
 * @group #slow
 * @group simpletest
 * @group legacy
 */
class SimpletestUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['simpletest'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->createUser(['administer unit tests']));
  }

  /**
   * Tests that unit, kernel, and functional tests work through the UI.
   *
   * @expectedDeprecation Drupal\simpletest\TestDiscovery is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Test\TestDiscovery instead. See https://www.drupal.org/node/2949692
   */
  public function testTestingThroughUI() {
    $url = Url::fromRoute('simpletest.test_form');
    $assertion = $this->assertSession();

    $this->drupalGet($url);
    $settings = $this->getDrupalSettings();
    $this->assertTrue(strpos($settings['simpleTest']['images'][0], 'core/misc/menu-collapsed.png') > 0, 'drupalSettings contains a link to core/misc/menu-collapsed.png.');

    // We can not test WebTestBase tests here since they require a valid .htkey
    // to be created. However this scenario is covered by the testception of
    // \Drupal\simpletest\Tests\SimpleTestTest.
    $tests = [
      // A KernelTestBase test.
      KernelTestBaseTest::class,
      // A PHPUnit unit test.
      ActionLocalTasksTest::class,
      // A PHPUnit functional test.
      ThroughUITest::class,
    ];

    foreach ($tests as $test) {
      $edit = [
        "tests[$test]" => TRUE,
      ];
      $this->drupalPostForm($url, $edit, t('Run tests'));
      $assertion->pageTextContains('0 fails, 0 exceptions');
    }
  }

}
