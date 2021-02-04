<?php

namespace Drupal\Tests\auto_updates\Functional;

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
   * Expected message for readiness checkers returning error messages.
   */
  const ERRORS_MESSAGE = 'Your site does not pass some readiness checks for automatic updates. It cannot be automatically updated until further action is performed.';

  /**
   * Expected message for readiness checkers returning warning messages.
   */
  const WARNINGS_MESSAGE = 'Your site does not pass some readiness checks for automatic updates. Depending on the nature of the failures, it might effect the eligibility for automatic updates.';

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
  }

  /**
   * Tests readiness checkers on status report page.
   */
  public function testReadinessChecksStatusReport():void {
    $assert = $this->assertSession();

    // Ensure automated_cron is disabled before installing auto_updates. This
    // ensures we are testing that auto_updates runs the checkers when the
    // module itself is installed and they weren't run on cron.
    $this->container->get('module_installer')->uninstall(['automated_cron']);
    $this->container->get('module_installer')->install(['auto_updates', 'auto_updates_test']);

    // If the site is ready for updates, the users will see the same output
    // regardless of whether the user has permission to run updates.
    $this->drupalLogin($this->reportViewerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site is ready for automatic updates.', 'checked', FALSE);
    $this->drupalLogin($this->checkerRunnerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site is ready for automatic updates.', 'checked', FALSE);

    // Confirm a user without the permission to run readiness checks does not
    // have a link to run the checks when the checks need to be run again.
    TestTime::setFakeTimeByOffset('+2 days');
    $this->drupalLogin($this->reportViewerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site has not recently checked if it is ready to apply automatic updates. Readiness checks were last run %s ago.', 'warning', FALSE);
    $assert->linkNotExists('Run readiness checks');
    // A user without the permission to run the checkers will not see a message
    // on other pages if the checkers need to be run again.
    $this->drupalGet('admin/structure');
    $assert->pageTextNotContains('Your site has not recently run an update readiness check.');
    $assert->linkNotExists('Run readiness checks now.');

    // Confirm a user with the permission to run readiness checks does have a
    // link to run the checks when the checks need to be run again.
    $this->drupalLogin($this->checkerRunnerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site has not recently checked if it is ready to apply automatic updates. Readiness checks were last run %s ago. Run readiness checks now.', 'warning', FALSE);
    TestChecker::setTestMessages(['OMG 🚒. Your server is on 🔥!'], [], new TranslatableMarkup('Summary: 🔥'));

    // Run the readiness checks.
    $this->clickLink('Run readiness checks');
    $assert->statusCodeEquals(200);
    // Confirm redirect back to status report page.
    $assert->addressEquals('/admin/reports/status');
    $this->assertReadinessReportMatches('OMG 🚒. Your server is on 🔥!', 'error', static::ERRORS_MESSAGE);

    // @todo Should we always show when the checks were last run and a link to
    //   run when there is an error?
    // Confirm a user without permission to run the checks sees the same error.
    $this->drupalLogin($this->reportViewerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('OMG 🚒. Your server is on 🔥!', 'error', static::ERRORS_MESSAGE);

    // Confirm that a user with the correct permission can also run the checkers
    // on another admin page.
    TestTime::setFakeTimeByOffset('+4 days');
    $this->drupalLogin($this->checkerRunnerUser);
    TestChecker::setTestMessages(['OMG! Your server is on 💧!'], [], new TranslatableMarkup('Summary: 💧'));
    $this->drupalGet('admin/structure');
    file_put_contents("/Users/ted.bowman/sites/test.html", $this->getSession()->getPage()->getOuterHtml());
    $assert->pageTextContainsOnce('Your site has not recently run an update readiness check. Run readiness checks now.');
    $this->clickLink('Run readiness checks now.');
    $assert->addressEquals('admin/structure');
    $assert->pageTextContainsOnce('OMG! Your server is on 💧!', 'error');

    TestChecker::setTestMessages(
      ['OMG 🔌. Some one unplugged the server! How is this site even running?'],
      ['It looks like it going to rain and your server is outside.'],
      'Errors summary not displayed because only 1 error message',
      'Warnings summary not displayed because only 1 warning message.'
    );
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface $keyValue */
    $keyValue = $this->container->get('keyvalue.expirable')->get('auto_updates');
    $keyValue->delete('readiness_check_last_run');
    // Confirm a new message is displayed if the stored messages are deleted.
    $this->drupalGet('admin/reports/status');
    // Confirm that on the status page if there is only 1 warning or error the
    // the summaries will not be displayed.
    $this->assertReadinessReportMatches('OMG 🔌. Some one unplugged the server! How is this site even running?', 'error', static::ERRORS_MESSAGE);
    $this->assertReadinessReportMatches('It looks like it going to rain and your server is outside.', 'warning', static::WARNINGS_MESSAGE);
    $assert->pageTextNotContains('Errors summary not displayed because only 1 error message');
    $assert->pageTextNotContains('Warnings summary not displayed because only 1 warning message.');

    $this->drupalGet('admin/structure');
    $assert->pageTextContainsOnce(static::ERRORS_MESSAGE);
    // Confirm on admin pages that a single error will be displayed instead of a
    // summary.
    $assert->pageTextContainsOnce('OMG 🔌. Some one unplugged the server! How is this site even running?');
    $assert->pageTextNotContains('Errors summary not displayed because only 1 error message');
    // Warnings are not displayed on admin pages if there are any errors.
    $assert->pageTextNotContains('It looks like it going to rain and your server is outside.');
    $assert->pageTextNotContains('Warnings summary not displayed because only 1 warning message.');

    $keyValue->delete('readiness_check_last_run');
    $error_messages = [
      '😬Your server is in a cloud, a literal cloud!☁️.',
      '😂PHP only has 32k memory.',
    ];
    $warning_messages = [
      'Your server is a smart fridge. Will this work?',
      'Your server case is duct tape!',
    ];
    $errors_summary = 'Errors summary displayed because more than 1 error message';
    $warnings_summary = 'Warnings summary displayed because more than 1 warning message.';
    TestChecker::setTestMessages(
      $error_messages,
      $warning_messages,
      $errors_summary,
      $warnings_summary,
    );
    $this->drupalGet('admin/reports/status');
    // Confirm that both messages and summaries will be displayed on status
    // report when there multiple messages.
    $this->assertReadinessReportMatches("$errors_summary " . implode('', $error_messages), 'error', static::ERRORS_MESSAGE);
    $this->assertReadinessReportMatches("$warnings_summary " . implode('', $warning_messages), 'warning', static::WARNINGS_MESSAGE);
    $this->drupalGet('admin/structure');
    // Confirm on admin pages only the error summary will be displayed if there
    // is more than 1 error.
    $assert->pageTextNotContains($error_messages[0]);
    $assert->pageTextNotContains($error_messages[1]);
    $assert->pageTextContainsOnce($errors_summary);
    $assert->pageTextContainsOnce(static::ERRORS_MESSAGE);
    // Warnings are displayed on admin pages if there are any errors.
    $assert->pageTextNotContains($warning_messages[0]);
    $assert->pageTextNotContains($warning_messages[1]);
    $assert->pageTextNotContains($warnings_summary);

    $keyValue->delete('readiness_check_last_run');
    $warning_messages = [
      'The universe could collapse in on itself in the next second, in which case automatic updates will not run.',
      'An asteroid could hit your server farm, which would also stop automatic updates from running.',
    ];
    $warnings_summary = 'Warnings summary displayed because more than 1 warning message.';
    TestChecker::setTestMessages(
      [],
      $warning_messages,
      NULL,
      $warnings_summary,
    );
    $this->drupalGet('admin/reports/status');
    $assert->pageTextContainsOnce('Update readiness checks');
    // Confirm that warnings will display on the status report if there are no
    // errors.
    $this->assertReadinessReportMatches("$warnings_summary " . implode('', $warning_messages), 'warning', static::WARNINGS_MESSAGE);
    $this->drupalGet('admin/structure');
    // Confirm that the warnings summary is displayed on admin pages if there
    // are no errors.
    $assert->pageTextNotContains(static::ERRORS_MESSAGE);
    $assert->pageTextNotContains($warning_messages[0]);
    $assert->pageTextNotContains($warning_messages[1]);
    $assert->pageTextContainsOnce(static::WARNINGS_MESSAGE);
    $assert->pageTextContainsOnce($warnings_summary);

    $keyValue->delete('readiness_check_last_run');
    $warning_message = 'This is your one and only warning. You have been warned.';
    $warnings_summary = 'No need for this summary with only 1 warning.';
    TestChecker::setTestMessages(
      [],
      [$warning_message],
      NULL,
      $warnings_summary,
    );
    $this->drupalGet('admin/reports/status');
    $assert->pageTextContainsOnce('Update readiness checks');
    $this->assertReadinessReportMatches($warning_message, 'warning', static::WARNINGS_MESSAGE);
    $this->drupalGet('admin/structure');
    $assert->pageTextNotContains(static::ERRORS_MESSAGE);
    // Confirm that a single warning is displayed and not the summary on admin
    // pages if there is only 1 warning and there are no errors.
    $assert->pageTextContainsOnce(static::WARNINGS_MESSAGE);
    $assert->pageTextContainsOnce($warning_message);
    $assert->pageTextNotContains($warnings_summary);
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
    $this->assertReadinessReportMatches('Your site is ready for automatic updates.', 'checked');

    TestChecker::setTestMessages(['😿Oh no! A hacker now owns your files!']);
    $this->container->get('module_installer')->install(['auto_updates_test']);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('😿Oh no! A hacker now owns your files!', 'error', static::ERRORS_MESSAGE);

    // Confirm that installing a module that does not provide a new checker does
    // not run the checkers on install.
    TestChecker::setTestMessages(['Security has been compromised. "pass123" was a bad password!']);
    $this->container->get('module_installer')->install(['help']);
    $this->drupalGet('admin/reports/status');
    // Confirm that new checker message is not displayed because the checker was
    // not run again.
    $this->assertReadinessReportMatches('😿Oh no! A hacker now owns your files!', 'error', static::ERRORS_MESSAGE);
    $assert->pageTextNotContains('Security has been compromised. "pass123" was a bad password!');

    // Confirm the new message is displayed after running the checkers manually.
    // @todo Now that we no longer have the form there is no way to run the
    //  checkers if they have been run recently. Should we add the option on the
    //  status report to run the checks even if they have been run recently.
    // $this->drupalGet('admin/reports/status');
    // $this->assertReadinessReportMatches('1 check failed: Security has been compromised. "pass123" was a bad password!');
  }

  /**
   * Tests that checker message for an uninstalled module is not displayed.
   */
  public function testReadinessCheckerUninstall(): void {
    $assert = $this->assertSession();
    $this->drupalLogin($this->checkerRunnerUser);

    TestChecker::setTestMessages(['😲Your site is running on Commodore 64! Not powerful enough to do updates!']);
    $this->container->get('module_installer')->install(['auto_updates', 'auto_updates_test']);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('😲Your site is running on Commodore 64! Not powerful enough to do updates!', 'error', static::ERRORS_MESSAGE);

    $this->container->get('module_installer')->uninstall(['auto_updates_test']);
    $this->drupalGet('admin/reports/status');
    $assert->pageTextNotContains('😲Your site is running on Commodore 64! Not powerful enough to do updates!');
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
