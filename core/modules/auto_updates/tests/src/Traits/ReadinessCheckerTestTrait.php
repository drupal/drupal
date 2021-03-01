<?php

namespace Drupal\Tests\auto_updates\Traits;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult;
use Drupal\auto_updates_test\ReadinessChecker\TestChecker1;
use Drupal\auto_updates_test2\ReadinessChecker\TestChecker2;

/**
 * Common methods for testing readiness checkers.
 */
trait ReadinessCheckerTestTrait {

  /**
   * Test checker results.
   *
   * @var \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult[][][]
   */
  protected $testResults;

  /**
   * Creates ReadinessCheckerResult object to be used in tests.
   */
  protected function createTestReadinessCheckerResults(): void {
    // Set up various checker results for the test checkers.

    foreach ([1, 2] as $checker_number) {
      $test_checker = $checker_number === 1 ?
        $this->createMock(TestChecker1::class) :
        $this->createMock(TestChecker2::class);
      $test_checker->_serviceId = "auto_updates_test$checker_number.checker";
      // Set test checker results.
      $this->testResults["checker_$checker_number"]['1 error'] = [
        ReadinessCheckerResult::createErrorResult(
          $test_checker,
          [t("$checker_number:OMG 🚒. Your server is on 🔥!")],
          t("$checker_number:Summary: 🔥")
        ),
      ];
      $this->testResults["checker_$checker_number"]['1 error 1 warning'] = [
        ReadinessCheckerResult::createErrorResult(
          $test_checker,
          [t("$checker_number:OMG 🔌. Some one unplugged the server! How is this site even running?")],
          t("$checker_number:Summary: 🔥"),
        ),
        ReadinessCheckerResult::createWarningResult(
          $test_checker,
          [t("$checker_number:It looks like it going to rain and your server is outside.")],
          t("$checker_number:Warnings summary not displayed because only 1 warning message.")
        ),
      ];
      $this->testResults["checker_$checker_number"]['2 errors 2 warnings'] = [
        ReadinessCheckerResult::createErrorResult(
          $test_checker,
          [
            t("$checker_number:😬Your server is in a cloud, a literal cloud!☁️."),
            t("$checker_number:😂PHP only has 32k memory."),
          ],
          t("$checker_number:Errors summary displayed because more than 1 error message")
        ),
        ReadinessCheckerResult::createWarningResult(
          $test_checker,
          [
            t("$checker_number:Your server is a smart fridge. Will this work?"),
            t("$checker_number:Your server case is duct tape!"),
          ],
          t("$checker_number:Warnings summary displayed because more than 1 warning message.")
        ),

      ];
      $this->testResults["checker_$checker_number"]['2 warnings'] = [
        ReadinessCheckerResult::createWarningResult(
          $test_checker,
          [
            t("$checker_number:The universe could collapse in on itself in the next second, in which case automatic updates will not run."),
            t("$checker_number:An asteroid could hit your server farm, which would also stop automatic updates from running."),
          ],
          t("$checker_number:Warnings summary displayed because more than 1 warning message.")
        ),
      ];
      $this->testResults["checker_$checker_number"]['1 warning'] = [
        ReadinessCheckerResult::createWarningResult(
          $test_checker,
          [t("$checker_number:This is your one and only warning. You have been warned.")],
          t("$checker_number:No need for this summary with only 1 warning."),
        ),
      ];
    }
  }

}
