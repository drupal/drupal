<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\SecurityAdvisories;

use Drupal\advisory_feed_test\AdvisoryTestClientMiddleware;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests of security advisories functionality.
 *
 * @group system
 */
class SecurityAdvisoryTest extends BrowserTestBase {

  use CronRunTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'generic_module1_test',
    'advisory_feed_test',
  ];

  /**
   * A user with permission to administer site configuration and updates.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A test PSA endpoint that will display both PSA and non-PSA advisories.
   *
   * @var string
   */
  protected $workingEndpointMixed;

  /**
   * A test PSA endpoint that will only display PSA advisories.
   *
   * @var string
   */
  protected $workingEndpointPsaOnly;

  /**
   * A test PSA endpoint that will only display non-PSA advisories.
   *
   * @var string
   */
  protected $workingEndpointNonPsaOnly;

  /**
   * A non-working test PSA endpoint.
   *
   * @var string
   */
  protected $nonWorkingEndpoint;

  /**
   * A test PSA endpoint that returns invalid JSON.
   *
   * @var string
   */
  protected $invalidJsonEndpoint;

  /**
   * The key/value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $tempStore;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'administer software updates',
    ]);
    $this->drupalLogin($this->user);
    $fixtures_path = $this->baseUrl . '/core/modules/system/tests/fixtures/psa_feed';
    $this->workingEndpointMixed = $this->buildUrl('/advisory-feed-json/valid-mixed');
    $this->workingEndpointPsaOnly = $this->buildUrl('/advisory-feed-json/valid-psa-only');
    $this->workingEndpointNonPsaOnly = $this->buildUrl('/advisory-feed-json/valid-non-psa-only');
    $this->nonWorkingEndpoint = $this->buildUrl('/advisory-feed-json/missing');
    $this->invalidJsonEndpoint = "$fixtures_path/invalid.json";

    $this->tempStore = $this->container->get('keyvalue.expirable')->get('system');
  }

  /**
   * {@inheritdoc}
   */
  protected function writeSettings(array $settings): void {
    // Unset 'system.advisories' to allow testing enabling and disabling this
    // setting.
    unset($settings['config']['system.advisories']);
    parent::writeSettings($settings);
  }

  /**
   * Tests that a security advisory is displayed.
   */
  public function testPsa(): void {
    $assert = $this->assertSession();
    // Setup test PSA endpoint.
    AdvisoryTestClientMiddleware::setTestEndpoint($this->workingEndpointMixed, TRUE);
    $mixed_advisory_links = [
      'Critical Release - SA-2019-02-19',
      'Critical Release - PSA-Really Old',
      // The info for the test modules 'generic_module1_test' and
      // 'generic_module2_test' are altered for this test so match the items in
      // the test json feeds.
      // @see advisory_feed_test_system_info_alter()
      'Generic Module1 Project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02',
      'Generic Module2 project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02',
    ];
    // Confirm that links are not displayed if they are enabled.
    $this->config('system.advisories')->set('enabled', FALSE)->save();
    $this->assertAdvisoriesNotDisplayed($mixed_advisory_links);
    $this->config('system.advisories')->set('enabled', TRUE)->save();

    // A new request for the JSON feed will not be made on admin pages besides
    // the status report.
    $this->assertAdvisoriesNotDisplayed($mixed_advisory_links, ['system.admin']);

    // If both PSA and non-PSA advisories are displayed they should be displayed
    // as errors.
    $this->assertStatusReportLinks($mixed_advisory_links, RequirementSeverity::Error);
    // The advisories will be displayed on admin pages if the response was
    // stored from the status report request.
    $this->assertAdminPageLinks($mixed_advisory_links, RequirementSeverity::Error);

    // Confirm that a user without the correct permission will not see the
    // advisories on admin pages.
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      // We have nothing under admin, so we need access to a child route to
      // access the parent.
      'administer modules',
    ]));
    $this->assertAdvisoriesNotDisplayed($mixed_advisory_links, ['system.admin']);

    // Log back in with user with permission to see the advisories.
    $this->drupalLogin($this->user);
    // Test cache.
    AdvisoryTestClientMiddleware::setTestEndpoint($this->nonWorkingEndpoint);
    $this->assertAdminPageLinks($mixed_advisory_links, RequirementSeverity::Error);
    $this->assertStatusReportLinks($mixed_advisory_links, RequirementSeverity::Error);

    // Tests transmit errors with a JSON endpoint.
    $this->tempStore->delete('advisories_response');
    $this->assertAdvisoriesNotDisplayed($mixed_advisory_links);

    // Test that the site status report displays an error.
    $this->drupalGet(Url::fromRoute('system.status'));
    $assert->pageTextContains('Failed to fetch security advisory data:');

    // Test a PSA endpoint that returns invalid JSON.
    AdvisoryTestClientMiddleware::setTestEndpoint($this->invalidJsonEndpoint, TRUE);
    // Assert that are no logged error messages before attempting to fetch the
    // invalid endpoint.
    $this->assertServiceAdvisoryLoggedErrors([]);
    // On admin pages no message should be displayed if the feed is malformed.
    $this->assertAdvisoriesNotDisplayed($mixed_advisory_links);
    // Assert that there was an error logged for the invalid endpoint.
    $this->assertServiceAdvisoryLoggedErrors(['The security advisory JSON feed from Drupal.org could not be decoded.']);
    // On the status report there should be no announcements section.
    $this->drupalGet(Url::fromRoute('system.status'));
    $assert->pageTextNotContains('Failed to fetch security advisory data:');
    // Assert the error was logged again.
    $this->assertServiceAdvisoryLoggedErrors(['The security advisory JSON feed from Drupal.org could not be decoded.']);

    AdvisoryTestClientMiddleware::setTestEndpoint($this->workingEndpointPsaOnly, TRUE);
    $psa_advisory_links = [
      'Critical Release - PSA-Really Old',
      'Generic Module2 project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02',
    ];
    // Admin page will not display the new links because a new feed request is
    // not attempted.
    $this->assertAdvisoriesNotDisplayed($psa_advisory_links, ['system.admin']);
    // If only PSA advisories are displayed they should be displayed as
    // warnings.
    $this->assertStatusReportLinks($psa_advisory_links, RequirementSeverity::Warning);
    $this->assertAdminPageLinks($psa_advisory_links, RequirementSeverity::Warning);

    AdvisoryTestClientMiddleware::setTestEndpoint($this->workingEndpointNonPsaOnly, TRUE);
    $non_psa_advisory_links = [
      'Critical Release - SA-2019-02-19',
      'Generic Module1 Project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02',
    ];
    // If only non-PSA advisories are displayed they should be displayed as
    // errors.
    $this->assertStatusReportLinks($non_psa_advisory_links, RequirementSeverity::Error);
    $this->assertAdminPageLinks($non_psa_advisory_links, RequirementSeverity::Error);

    // Confirm that advisory fetching can be disabled after enabled.
    $this->config('system.advisories')->set('enabled', FALSE)->save();
    $this->assertAdvisoriesNotDisplayed($non_psa_advisory_links);
    // Assert no other errors were logged.
    $this->assertServiceAdvisoryLoggedErrors([]);
  }

  /**
   * Asserts the correct links appear on an admin page.
   *
   * @param string[] $expected_link_texts
   *   The expected links' text.
   * @param \Drupal\Core\Extension\Requirement\RequirementSeverity $error_or_warning
   *   Whether the links are a warning or an error.
   *
   * @internal
   */
  private function assertAdminPageLinks(array $expected_link_texts, RequirementSeverity $error_or_warning): void {
    $assert = $this->assertSession();
    $this->drupalGet(Url::fromRoute('system.admin'));
    if ($error_or_warning === RequirementSeverity::Error) {
      $assert->pageTextContainsOnce('Error message');
      $assert->pageTextNotContains('Warning message');
    }
    else {
      $assert->pageTextNotContains('Error message');
      $assert->pageTextContainsOnce('Warning message');
    }
    foreach ($expected_link_texts as $expected_link_text) {
      $assert->linkExists($expected_link_text);
    }
  }

  /**
   * Asserts the correct links appear on the status report page.
   *
   * @param string[] $expected_link_texts
   *   The expected links' text.
   * @param \Drupal\Core\Extension\Requirement\RequirementSeverity::Error|\Drupal\Core\Extension\Requirement\RequirementSeverity::Warning $error_or_warning
   *   Whether the links are a warning or an error.
   *
   * @internal
   */
  private function assertStatusReportLinks(array $expected_link_texts, RequirementSeverity $error_or_warning): void {
    $this->drupalGet(Url::fromRoute('system.status'));
    $assert = $this->assertSession();
    $selector = 'h3#' . $error_or_warning->status()
      . ' ~ details.system-status-report__entry:contains("Critical security announcements")';
    $assert->elementExists('css', $selector);
    foreach ($expected_link_texts as $expected_link_text) {
      $assert->linkExists($expected_link_text);
    }
  }

  /**
   * Asserts that security advisory links are not shown on admin pages.
   *
   * @param array $links
   *   The advisory links.
   * @param array $routes
   *   The routes to test.
   *
   * @internal
   */
  private function assertAdvisoriesNotDisplayed(array $links, array $routes = ['system.status', 'system.admin']): void {
    foreach ($routes as $route) {
      $this->drupalGet(Url::fromRoute($route));
      $this->assertSession()->statusCodeEquals(200);
      foreach ($links as $link) {
        $this->assertSession()->linkNotExists($link, "'$link' not displayed on route '$route'.");
      }
    }
  }

  /**
   * Asserts the expected error messages were logged on the system logger.
   *
   * The test module 'advisory_feed_test' must be installed to use this method.
   * The stored error messages are cleared during this method.
   *
   * @param string[] $expected_messages
   *   The expected error messages.
   *
   * @see \Drupal\advisory_feed_test\TestSystemLoggerChannel::log()
   *
   * @internal
   */
  protected function assertServiceAdvisoryLoggedErrors(array $expected_messages): void {
    $state = $this->container->get('state');
    $messages = $state->get('advisory_feed_test.error_messages', []);
    $this->assertSame($expected_messages, $messages);
    $state->set('advisory_feed_test.error_messages', []);
  }

}
