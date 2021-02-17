<?php

namespace Drupal\Tests\auto_updates\Functional;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult;
use Drupal\auto_updates_test\Datetime\TestTime;
use Drupal\auto_updates_test\ReadinessChecker\TestChecker;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests readiness checkers.
 *
 * @group auto_updates
 */
class ReadinessCheckerTest extends BrowserTestBase {

  use StringTranslationTrait;

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

    $testChecker = $this->createMock(TestChecker::class);
    $testChecker->_serviceId = 'auto_updates_test.checker';
    // Set test checker results.
    $this->testResults['1 error'] = new ReadinessCheckerResult(
      $testChecker,
      new TranslatableMarkup('Summary: 🔥'),
      [t('OMG 🚒. Your server is on 🔥!')],
      NULL,
      []
    );
    $this->testResults['1 error 1 warning'] = new ReadinessCheckerResult(
      $testChecker,
      t('Errors summary not displayed because only 1 error message'),
      [t('OMG 🔌. Some one unplugged the server! How is this site even running?')],
      t('Warnings summary not displayed because only 1 warning message.'),
      [t('It looks like it going to rain and your server is outside.')],
    );
    $this->testResults['2 errors 2 warnings'] = new ReadinessCheckerResult(
      $testChecker,
      t('Errors summary displayed because more than 1 error message'),
      [
        t('😬Your server is in a cloud, a literal cloud!☁️.'),
        t('😂PHP only has 32k memory.'),
      ],
      t('Warnings summary displayed because more than 1 warning message.'),
      [
        t('Your server is a smart fridge. Will this work?'),
        t('Your server case is duct tape!'),
      ]
    );
    $this->testResults['2 warnings'] = new ReadinessCheckerResult(
      $testChecker,
      NULL,
      [],
      t('Warnings summary displayed because more than 1 warning message.'),
      [
        t('The universe could collapse in on itself in the next second, in which case automatic updates will not run.'),
        t('An asteroid could hit your server farm, which would also stop automatic updates from running.'),
      ]
    );
    $this->testResults['1 warning'] = new ReadinessCheckerResult(
      $testChecker,
      NULL,
      [],
      t('No need for this summary with only 1 warning.'),
      [t('This is your one and only warning. You have been warned.')]
    );
  }

  /**
   * Tests readiness checkers on status report page.
   */
  public function testReadinessChecksStatusReport():void {
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
    TestTime::setFakeTimeByOffset('+2 days');
    $this->drupalLogin($this->reportViewerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site has not recently checked if it is ready to apply automatic updates. Readiness checks were last run %s ago.', 'warning', FALSE);
    $assert->linkNotExists('Run readiness checks');

    // Confirm a user with the permission to run readiness checks does have a
    // link to run the checks when the checks need to be run again.
    $this->drupalLogin($this->checkerRunnerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site has not recently checked if it is ready to apply automatic updates. Readiness checks were last run %s ago.Run readiness checks now.', 'warning', FALSE);
    $expected_result = $this->testResults['1 error'];
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

    $expected_result = $this->testResults['1 error 1 warning'];
    TestChecker::setTestResult($expected_result);
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface $keyValue */
    $keyValue = $this->container->get('keyvalue.expirable')->get('auto_updates');
    $keyValue->delete('readiness_check_last_run');
    // Confirm a new message is displayed if the stored messages are deleted.
    $this->drupalGet('admin/reports/status');
    // Confirm that on the status page if there is only 1 warning or error the
    // the summaries will not be displayed.
    $this->assertReadinessReportMatches($expected_result->getErrorMessages()[0], 'error', static::ERRORS_EXPLANATION);
    $this->assertReadinessReportMatches($expected_result->getWarningMessages()[0], 'warning', static::WARNINGS_EXPLANATION);
    $assert->pageTextNotContains($expected_result->getErrorsSummary());
    $assert->pageTextNotContains($expected_result->getWarningsSummary());

    $keyValue->delete('readiness_check_last_run');
    $expected_result = $this->testResults['2 errors 2 warnings'];
    TestChecker::setTestResult($expected_result);
    $this->drupalGet('admin/reports/status');
    // Confirm that both messages and summaries will be displayed on status
    // report when there multiple messages.
    $this->assertReadinessReportMatches($expected_result->getErrorsSummary() . ' ' . implode('', $expected_result->getErrorMessages()), 'error', static::ERRORS_EXPLANATION);
    $this->assertReadinessReportMatches($expected_result->getWarningsSummary() . ' ' . implode('', $expected_result->getWarningMessages()), 'warning', static::WARNINGS_EXPLANATION);

    $keyValue->delete('readiness_check_last_run');
    $expected_result = $this->testResults['2 warnings'];
    TestChecker::setTestResult($expected_result);
    $this->drupalGet('admin/reports/status');
    $assert->pageTextContainsOnce('Update readiness checks');
    // Confirm that warnings will display on the status report if there are no
    // errors.
    $this->assertReadinessReportMatches($expected_result->getWarningsSummary() . ' ' . implode('', $expected_result->getWarningMessages()), 'warning', static::WARNINGS_EXPLANATION);

    $keyValue->delete('readiness_check_last_run');
    $expected_result = $this->testResults['1 warning'];
    TestChecker::setTestResult($expected_result);
    $this->drupalGet('admin/reports/status');
    $assert->pageTextContainsOnce('Update readiness checks');
    $this->assertReadinessReportMatches($expected_result->getWarningMessages()[0], 'warning', static::WARNINGS_EXPLANATION);
  }

  /**
   * Tests readiness checkers results on admin pages..
   */
  public function testReadinessChecksAdminPages():void {
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
    $expected_result = $this->testResults['1 error'];
    TestChecker::setTestResult($expected_result);
    TestTime::setFakeTimeByOffset('+2 days');
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

    $expected_result = $this->testResults['1 error 1 warning'];
    TestChecker::setTestResult($expected_result);
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface $keyValue */
    $keyValue = $this->container->get('keyvalue.expirable')->get('auto_updates');
    $keyValue->delete('readiness_check_last_run');
    // Confirm a new message is displayed if the stored messages are deleted.
    $this->drupalGet('admin/structure');
    $assert->pageTextContainsOnce(static::ERRORS_EXPLANATION);
    // Confirm on admin pages that a single error will be displayed instead of a
    // summary.
    $assert->pageTextContainsOnce($expected_result->getErrorMessages()[0]);
    $assert->pageTextNotContains($expected_result->getErrorsSummary());
    // Warnings are not displayed on admin pages if there are any errors.
    $assert->pageTextNotContains($expected_result->getWarningMessages()[0]);
    $assert->pageTextNotContains($expected_result->getWarningsSummary());

    $keyValue->delete('readiness_check_last_run');
    $expected_result = $this->testResults['2 errors 2 warnings'];
    TestChecker::setTestResult($expected_result);
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

    $keyValue->delete('readiness_check_last_run');
    $expected_result = $this->testResults['2 warnings'];
    TestChecker::setTestResult($expected_result);
    $this->drupalGet('admin/structure');
    // Confirm that the warnings summary is displayed on admin pages if there
    // are no errors.
    $assert->pageTextNotContains(static::ERRORS_EXPLANATION);
    $assert->pageTextNotContains($expected_result->getWarningMessages()[0]);
    $assert->pageTextNotContains($expected_result->getWarningMessages()[1]);
    $assert->pageTextContainsOnce(static::WARNINGS_EXPLANATION);
    $assert->pageTextContainsOnce($expected_result->getWarningsSummary());

    $keyValue->delete('readiness_check_last_run');
    $warning_message = 'This is your one and only warning. You have been warned.';
    $warnings_summary = 'No need for this summary with only 1 warning.';
    $expected_result = $this->testResults['1 warning'];
    TestChecker::setTestResult($expected_result);
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

    $expected_result = $this->testResults['1 error'];
    TestChecker::setTestResult($expected_result);
    $this->container->get('module_installer')->install(['auto_updates_test']);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches($expected_result->getErrorMessages()[0] . 'Run readiness checks now.', 'error', static::ERRORS_EXPLANATION);

    // Confirm that installing a module that does not provide a new checker does
    // not run the checkers on install.
    $unexpected_result = $this->testResults['2 errors 2 warnings'];
    TestChecker::setTestResult($unexpected_result);
    $this->container->get('module_installer')->install(['help']);
    $this->drupalGet('admin/reports/status');
    // Confirm that new checker message is not displayed because the checker was
    // not run again.
    $this->assertReadinessReportMatches($expected_result->getErrorMessages()[0] . 'Run readiness checks now.', 'error', static::ERRORS_EXPLANATION);
    $assert->pageTextNotContains($unexpected_result->getErrorMessages()[0]);
    $assert->pageTextNotContains($unexpected_result->getErrorsSummary());
  }

  /**
   * Tests that checker message for an uninstalled module is not displayed.
   */
  public function testReadinessCheckerUninstall(): void {
    $assert = $this->assertSession();
    $this->drupalLogin($this->checkerRunnerUser);

    $expected_result = $this->testResults['1 error'];
    TestChecker::setTestResult($expected_result);
    $this->container->get('module_installer')->install(['auto_updates', 'auto_updates_test']);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches($expected_result->getErrorMessages()[0] . 'Run readiness checks now.', 'error', static::ERRORS_EXPLANATION);

    $this->container->get('module_installer')->uninstall(['auto_updates_test']);
    $this->drupalGet('admin/reports/status');
    $assert->pageTextNotContains($expected_result->getErrorMessages()[0] . 'Run readiness checks now.');
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

}
