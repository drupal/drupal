<?php

namespace Drupal\Tests\auto_updates\Functional;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult;
use Drupal\auto_updates_test\Datetime\TestTime;
use Drupal\auto_updates_test\ReadinessChecker\TestChecker;
use Drupal\auto_updates_test2\ReadinessChecker\TestChecker2;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests readiness checkers.
 *
 * @group auto_updates
 */
class ReadinessCheckerTest extends BrowserTestBase {

  use StringTranslationTrait;
  use CronRunTrait;

  /**
   * Expected explanation text when readiness checkers return error messages.
   */
  const ERRORS_EXPLANATION = 'Your site does not pass some readiness checks for automatic updates. It cannot be automatically updated until further action is performed.';

  /**
   * Expected explanation text when readiness checkers return warning messages.
   */
  const WARNINGS_EXPLANATION = 'Your site does not pass some readiness checks for automatic updates. Depending on the nature of the failures, it might affect the eligibility for automatic updates.';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user who can view the status report.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $reportViewerUser;

  /**
   * A user who can view the status report and run readiness checkers.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $checkerRunnerUser;

  /**
   * The test checker.
   *
   * @var \Drupal\auto_updates_test\ReadinessChecker\TestChecker
   */
  protected $testChecker;

  /**
   * Test checker results.
   *
   * @var \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult[]
   */
  protected $testResults;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->reportViewerUser = $this->createUser([
      'administer site configuration',
      'access administration pages',
    ]);
    $this->checkerRunnerUser = $this->createUser([
      'administer site configuration',
      'administer software updates',
      'access administration pages',
    ]);

    $test_checker = $this->createMock(TestChecker::class);
    foreach ([1, 2] as $checker_number) {
      $test_checker->_serviceId = "auto_updates_test$checker_number.checker";
      // Set test checker results.
      $this->testResults["checker_$checker_number"]['1 error'] = new ReadinessCheckerResult(
        $test_checker,
        t("$checker_number:Summary: 🔥"),
        [t("$checker_number:OMG 🚒. Your server is on 🔥!")],
        NULL,
        []
      );
      $this->testResults["checker_$checker_number"]['1 error 1 warning'] = new ReadinessCheckerResult(
        $test_checker,
        t("$checker_number:Errors summary not displayed because only 1 error message"),
        [t("$checker_number:OMG 🔌. Some one unplugged the server! How is this site even running?")],
        t("$checker_number:Warnings summary not displayed because only 1 warning message."),
        [t("$checker_number:It looks like it going to rain and your server is outside.")],
      );
      $this->testResults["checker_$checker_number"]['2 errors 2 warnings'] = new ReadinessCheckerResult(
        $test_checker,
        t("$checker_number:Errors summary displayed because more than 1 error message"),
        [
          t("$checker_number:😬Your server is in a cloud, a literal cloud!☁️."),
          t("$checker_number:😂PHP only has 32k memory."),
        ],
        t("$checker_number:Warnings summary displayed because more than 1 warning message."),
        [
          t("$checker_number:Your server is a smart fridge. Will this work?"),
          t("$checker_number:Your server case is duct tape!"),
        ]
      );
      $this->testResults["checker_$checker_number"]['2 warnings'] = new ReadinessCheckerResult(
        $test_checker,
        NULL,
        [],
        t("$checker_number:Warnings summary displayed because more than 1 warning message."),
        [
          t("$checker_number:The universe could collapse in on itself in the next second, in which case automatic updates will not run."),
          t("$checker_number:An asteroid could hit your server farm, which would also stop automatic updates from running."),
        ]
      );
      $this->testResults["checker_$checker_number"]['1 warning'] = new ReadinessCheckerResult(
        $test_checker,
        NULL,
        [],
        t("$checker_number:No need for this summary with only 1 warning."),
        [t("$checker_number:This is your one and only warning. You have been warned.")]
      );
    }

  }

  /**
   * Tests readiness checkers on status report page.
   */
  public function testReadinessChecksStatusReport(): void {
    $assert = $this->assertSession();

    // Ensure automated_cron is disabled before installing auto_updates. This
    // ensures we are testing that auto_updates runs the checkers when the
    // module itself is installed and they weren't run on cron.
    $this->assertFalse($this->container->get('module_handler')->moduleExists('automated_cron'));
    $this->container->get('module_installer')->install(['auto_updates', 'auto_updates_test']);

    // If the site is ready for updates, the users will see the same output
    // regardless of whether the user has permission to run updates.
    $this->drupalLogin($this->reportViewerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site is ready for automatic updates.', 'checked', FALSE);
    $this->drupalLogin($this->checkerRunnerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site is ready for automatic updates. Run readiness checks now.', 'checked', FALSE);

    // Confirm a user without the permission to run readiness checks does not
    // have a link to run the checks when the checks need to be run again.
    // @todo Change this to fake the request time in
    //   https://www.drupal.org/node/3113971.
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value */
    $key_value = $this->container->get('keyvalue.expirable')->get('auto_updates');
    $key_value->delete('readiness_check_last_run');
    $this->drupalLogin($this->reportViewerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site is ready for automatic updates.', 'checked', FALSE);
    $this->drupalLogin($this->checkerRunnerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site is ready for automatic updates. Run readiness checks now.', 'checked', FALSE);

    // Confirm a user with the permission to run readiness checks does have a
    // link to run the checks when the checks need to be run again.
    $this->drupalLogin($this->reportViewerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site is ready for automatic updates.', 'checked', FALSE);
    $this->drupalLogin($this->checkerRunnerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site is ready for automatic updates. Run readiness checks now.', 'checked', FALSE);
    $expected_result = $this->testResults['checker_1']['1 error'];
    TestChecker::setTestResult($expected_result);

    // Run the readiness checks.
    $this->clickLink('Run readiness checks');
    $assert->statusCodeEquals(200);
    // Confirm redirect back to status report page.
    $assert->addressEquals('/admin/reports/status');
    // Assert that when the runners are run manually the message that updates
    // will not be performed because of errors is displayed on the top of the
    // page in message.
    $assert->pageTextMatchesCount(2, '/' . preg_quote(static::ERRORS_EXPLANATION) . '/');
    $this->assertReadinessReportMatches($expected_result->getErrorMessages()[0] . 'Run readiness checks now.', 'error', static::ERRORS_EXPLANATION);

    // @todo Should we always show when the checks were last run and a link to
    //   run when there is an error?
    // Confirm a user without permission to run the checks sees the same error.
    $this->drupalLogin($this->reportViewerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches($expected_result->getErrorMessages()[0], 'error', static::ERRORS_EXPLANATION);

    $expected_result = $this->testResults['checker_1']['1 error 1 warning'];
    TestChecker::setTestResult($expected_result);
    $key_value->delete('readiness_check_last_run');
    // Confirm a new message is displayed if the stored messages are deleted.
    $this->drupalGet('admin/reports/status');
    // Confirm that on the status page if there is only 1 warning or error the
    // the summaries will not be displayed.
    $this->assertReadinessReportMatches($expected_result->getErrorMessages()[0], 'error', static::ERRORS_EXPLANATION);
    $this->assertReadinessReportMatches($expected_result->getWarningMessages()[0], 'warning', static::WARNINGS_EXPLANATION);
    $assert->pageTextNotContains($expected_result->getErrorsSummary());
    $assert->pageTextNotContains($expected_result->getWarningsSummary());

    $key_value->delete('readiness_check_last_run');
    $expected_result = $this->testResults['checker_1']['2 errors 2 warnings'];
    TestChecker::setTestResult($expected_result);
    $this->drupalGet('admin/reports/status');
    // Confirm that both messages and summaries will be displayed on status
    // report when there multiple messages.
    $this->assertReadinessReportMatches($expected_result->getErrorsSummary() . ' ' . implode('', $expected_result->getErrorMessages()), 'error', static::ERRORS_EXPLANATION);
    $this->assertReadinessReportMatches($expected_result->getWarningsSummary() . ' ' . implode('', $expected_result->getWarningMessages()), 'warning', static::WARNINGS_EXPLANATION);

    $key_value->delete('readiness_check_last_run');
    $expected_result = $this->testResults['checker_1']['2 warnings'];
    TestChecker::setTestResult($expected_result);
    $this->drupalGet('admin/reports/status');
    $assert->pageTextContainsOnce('Update readiness checks');
    // Confirm that warnings will display on the status report if there are no
    // errors.
    $this->assertReadinessReportMatches($expected_result->getWarningsSummary() . ' ' . implode('', $expected_result->getWarningMessages()), 'warning', static::WARNINGS_EXPLANATION);

    $key_value->delete('readiness_check_last_run');
    $expected_result = $this->testResults['checker_1']['1 warning'];
    TestChecker::setTestResult($expected_result);
    $this->drupalGet('admin/reports/status');
    $assert->pageTextContainsOnce('Update readiness checks');
    $this->assertReadinessReportMatches($expected_result->getWarningMessages()[0], 'warning', static::WARNINGS_EXPLANATION);
  }

  /**
   * Tests readiness checkers results on admin pages..
   */
  public function testReadinessChecksAdminPages(): void {
    $assert = $this->assertSession();
    $messages_section_selector = '[data-drupal-messages]';

    // Ensure automated_cron is disabled before installing auto_updates. This
    // ensures we are testing that auto_updates runs the checkers when the
    // module itself is installed and they weren't run on cron.
    $this->assertFalse($this->container->get('module_handler')->moduleExists('automated_cron'));
    $this->container->get('module_installer')->install(['auto_updates', 'auto_updates_test']);

    // If site is ready for updates no message will be displayed on admin pages.
    $this->drupalLogin($this->reportViewerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site is ready for automatic updates.', 'checked', FALSE);
    $this->drupalGet('admin/structure');
    $assert->elementNotExists('css', $messages_section_selector);

    // Confirm a user without the permission to run readiness checks does not
    // have a link to run the checks when the checks need to be run again.
    $expected_result = $this->testResults['checker_1']['1 error'];
    TestChecker::setTestResult($expected_result);
    // @todo Change this to use ::delayRequestTime() to simulate running cron
    //   after a 24 wait instead of directly deleting 'readiness_check_last_run'
    //   https://www.drupal.org/node/3113971.
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value */
    $key_value = $this->container->get('keyvalue.expirable')->get('auto_updates');
    $key_value->delete('readiness_check_last_run');
    // A user without the permission to run the checkers will not see a message
    // on other pages if the checkers need to be run again.
    $this->drupalGet('admin/structure');
    $assert->elementNotExists('css', $messages_section_selector);

    // Confirm that a user with the correct permission can also run the checkers
    // on another admin page.
    $this->drupalLogin($this->checkerRunnerUser);
    $this->drupalGet('admin/structure');
    $assert->elementExists('css', $messages_section_selector);
    $assert->pageTextContainsOnce('Your site has not recently run an update readiness check. Run readiness checks now.');
    $this->clickLink('Run readiness checks now.');
    $assert->addressEquals('admin/structure');
    $assert->pageTextContainsOnce($expected_result->getErrorMessages()[0]);

    $expected_result = $this->testResults['checker_1']['1 error 1 warning'];
    TestChecker::setTestResult($expected_result);
    // Confirm a new message is displayed if the cron is run after an hour.
    $this->delayRequestTime();
    $this->cronRun();
    $this->drupalGet('admin/structure');
    $assert->pageTextContainsOnce(static::ERRORS_EXPLANATION);
    // Confirm on admin pages that a single error will be displayed instead of a
    // summary.
    $assert->pageTextContainsOnce($expected_result->getErrorMessages()[0]);
    $assert->pageTextNotContains($expected_result->getErrorsSummary());
    // Warnings are not displayed on admin pages if there are any errors.
    $assert->pageTextNotContains($expected_result->getWarningMessages()[0]);
    $assert->pageTextNotContains($expected_result->getWarningsSummary());

    // Confirm that if cron runs less than hour after it previously ran it will
    // not run the checkers again.
    $unexpected_result = $this->testResults['checker_1']['2 errors 2 warnings'];
    TestChecker::setTestResult($unexpected_result);
    $this->delayRequestTime(30);
    $this->cronRun();
    $this->drupalGet('admin/structure');
    $assert->pageTextNotContains($unexpected_result->getErrorsSummary());
    $assert->pageTextContainsOnce($expected_result->getErrorMessages()[0]);

    // Confirm that is if cron is run over an hour after the checkers were
    // previously run the checkers will be run again.
    $this->delayRequestTime(31);
    $this->cronRun();
    $expected_result = $unexpected_result;
    $this->drupalGet('admin/structure');
    // Confirm on admin pages only the error summary will be displayed if there
    // is more than 1 error.
    $assert->pageTextNotContains($expected_result->getErrorMessages()[0]);
    $assert->pageTextNotContains($expected_result->getErrorMessages()[0]);
    $assert->pageTextContainsOnce($expected_result->getErrorsSummary());
    $assert->pageTextContainsOnce(static::ERRORS_EXPLANATION);
    // Warnings are displayed on admin pages if there are any errors.
    $assert->pageTextNotContains($expected_result->getWarningMessages()[0]);
    $assert->pageTextNotContains($expected_result->getWarningMessages()[1]);
    $assert->pageTextNotContains($expected_result->getWarningsSummary());

    $expected_result = $this->testResults['checker_1']['2 warnings'];
    TestChecker::setTestResult($expected_result);
    $this->delayRequestTime();
    $this->cronRun();
    $this->drupalGet('admin/structure');
    // Confirm that the warnings summary is displayed on admin pages if there
    // are no errors.
    $assert->pageTextNotContains(static::ERRORS_EXPLANATION);
    $assert->pageTextNotContains($expected_result->getWarningMessages()[0]);
    $assert->pageTextNotContains($expected_result->getWarningMessages()[1]);
    $assert->pageTextContainsOnce(static::WARNINGS_EXPLANATION);
    $assert->pageTextContainsOnce($expected_result->getWarningsSummary());

    $expected_result = $this->testResults['checker_1']['1 warning'];
    TestChecker::setTestResult($expected_result);
    $this->delayRequestTime();
    $this->cronRun();
    $this->drupalGet('admin/structure');
    $assert->pageTextNotContains(static::ERRORS_EXPLANATION);
    // Confirm that a single warning is displayed and not the summary on admin
    // pages if there is only 1 warning and there are no errors.
    $assert->pageTextContainsOnce(static::WARNINGS_EXPLANATION);
    $assert->pageTextContainsOnce($expected_result->getWarningMessages()[0]);
    $assert->pageTextNotContains($expected_result->getWarningsSummary());
  }

  /**
   * Tests installing a module with a checker before installing auto_updates.
   */
  public function testReadinessCheckAfterInstall(): void {
    $assert = $this->assertSession();
    $this->drupalLogin($this->checkerRunnerUser);

    $this->drupalGet('admin/reports/status');
    $assert->pageTextNotContains('Update readiness checks');

    $this->container->get('module_installer')->install(['auto_updates']);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site is ready for automatic updates. Run readiness checks now.', 'checked');

    $expected_result = $this->testResults['checker_1']['1 error'];
    TestChecker::setTestResult($expected_result);
    $this->container->get('module_installer')->install(['auto_updates_test']);
    $this->drupalGet('admin/structure');
    $assert->pageTextContainsOnce($expected_result->getErrorMessages()[0]);

    // Confirm that installing a module that does not provide a new checker does
    // not run the checkers on install.
    $unexpected_result = $this->testResults['checker_1']['2 errors 2 warnings'];
    TestChecker::setTestResult($unexpected_result);
    $this->container->get('module_installer')->install(['help']);
    // Check for message on 'admin/structure' instead of the status report
    // because checkers will be run if needed on the status report.
    $this->drupalGet('admin/structure');
    // Confirm that new checker message is not displayed because the checker was
    // not run again.
    $assert->pageTextContainsOnce($expected_result->getErrorMessages()[0]);
    $assert->pageTextNotContains($unexpected_result->getErrorMessages()[0]);
    $assert->pageTextNotContains($unexpected_result->getErrorsSummary());
  }

  /**
   * Tests that checker message for an uninstalled module is not displayed.
   */
  public function testReadinessCheckerUninstall(): void {
    $assert = $this->assertSession();
    $this->drupalLogin($this->checkerRunnerUser);

    $expected_result1 = $this->testResults['checker_1']['1 error'];
    TestChecker::setTestResult($expected_result1);
    $expected_result2 = $this->testResults['checker_2']['1 error'];
    TestChecker2::setTestResult($expected_result2);
    $this->container->get('module_installer')->install([
      'auto_updates',
      'auto_updates_test',
      'auto_updates_test2',
    ]);
    // Check for message on 'admin/structure' instead of the status report
    // because checkers will be run if needed on the status report.
    $this->drupalGet('admin/structure');
    file_put_contents("/Users/ted.bowman/sites/test.html", $this->getSession()->getPage()->getOuterHtml());
    $assert->pageTextContainsOnce($expected_result1->getErrorMessages()[0]);
    $assert->pageTextContainsOnce($expected_result2->getErrorMessages()[0]);

    // Confirm that when on of the module is uninstalled the other module's
    // checker result is still displayed.
    $this->container->get('module_installer')->uninstall(['auto_updates_test2']);
    $this->drupalGet('admin/structure');
    $assert->pageTextNotContains($expected_result2->getErrorMessages()[0]);
    $assert->pageTextContainsOnce($expected_result1->getErrorMessages()[0]);

    // Confirm that when on of the module is uninstalled the other module's
    // checker result is still displayed.
    $this->container->get('module_installer')->uninstall(['auto_updates_test']);
    $this->drupalGet('admin/structure');
    $assert->pageTextNotContains($expected_result2->getErrorMessages()[0]);
    $assert->pageTextNotContains($expected_result1->getErrorMessages()[0]);
  }

  /**
   * Asserts status report readiness report item matches a format.
   *
   * @param string $format
   *   The string to match.
   * @param string $section
   *   The section of the status report in which the string should appear.
   * @param string $message_prefix
   *   The prefix for before the string.
   */
  private function assertReadinessReportMatches(string $format, string $section = 'error', string $message_prefix = ''): void {
    $format = 'Update readiness checks ' . ($message_prefix ? "$message_prefix " : '') . $format;

    $text = $this->getSession()->getPage()->find(
      'css',
      "h3#$section ~ details.system-status-report__entry:contains('Update readiness checks')",
    )->getText();
    $this->assertStringMatchesFormat($format, $text);
  }

  /**
   * Delays the request for the test.
   *
   * @param int $minutes
   *   The number of minutes to delay request time. Defaults to 61 minutes.
   */
  private function delayRequestTime(int $minutes = 61): void {
    static $total_delay = 0;
    $total_delay += $minutes;
    TestTime::setFakeTimeByOffset("+$total_delay minutes");
  }

}
