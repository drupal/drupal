<?php

namespace Drupal\Tests\tour\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * General Tour tests that require JavaScript.
 *
 * @group tour
 */
class TourJavaScriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tour',
    'tour_test',
    'toolbar',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'access toolbar',
      'access tour',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Confirm the 'tips' query argument works.
   */
  public function testQueryArg() {
    $assert_session = $this->assertSession();

    $this->drupalGet('tour-test-1');
    $assert_session->assertNoElementAfterWait('css', '.tip-tour-test-1');
    $assert_session->pageTextContains('Where does the rain in Spain fail?');

    $this->drupalGet('tour-test-1', [
      'query' => [
        'tips' => 'tip-tour-test-1',
      ]
    ]);
    $this->assertNotNull($assert_session->waitForElementVisible('css', '.tip-tour-test-1'));
    $assert_session->pageTextContains('Where does the rain in Spain fail?');
  }
}
