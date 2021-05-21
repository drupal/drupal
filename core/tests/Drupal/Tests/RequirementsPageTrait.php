<?php

namespace Drupal\Tests;

/**
 * Provides helper methods for the requirements page.
 */
trait RequirementsPageTrait {

  /**
   * Handles the update requirements page.
   */
  protected function updateRequirementsProblem() {
    // Assert a warning is shown on older test environments.
    $links = $this->getSession()->getPage()->findAll('named', ['link', 'try again']);
    if ($links && version_compare(phpversion(), \Drupal::MINIMUM_SUPPORTED_PHP) < 0) {
      $this->assertSession()->pageTextNotContains('Errors found');
      $this->assertWarningSummaries(['PHP']);
      $this->clickLink('try again');
      $this->checkForMetaRefresh();
    }
  }

  /**
   * Continues installation when the expected warnings are found.
   *
   * This function is no longer called by any core test, but it is retained for
   * use by contrib/custom tests. It is not deprecated, because it remains the
   * recommended function to call for its purpose.
   *
   * @param string[] $expected_warnings
   *   A list of warning summaries to expect on the requirements screen (e.g.
   *   'PHP', 'PHP OPcode caching', etc.). If only the expected warnings
   *   are found, the test will click the "continue anyway" link to go to the
   *   next screen of the installer. If an expected warning is not found, or if
   *   a warning not in the list is present, a fail is raised.
   */
  protected function continueOnExpectedWarnings($expected_warnings = []) {
    $this->assertSession()->pageTextNotContains('Errors found');
    $this->assertWarningSummaries($expected_warnings);
    $this->clickLink('continue anyway');
    $this->checkForMetaRefresh();
  }

  /**
   * Assert the given warning summaries are present on the page.
   *
   * If an expected warning is not found, or if a warning not in the list is
   * present, a fail is raised.
   *
   * @param string[] $warning_summaries
   *   A list of warning summaries to expect on the requirements screen (e.g.
   *   'PHP', 'PHP OPcode caching', etc.).
   */
  protected function assertWarningSummaries(array $warning_summaries) {
    // Allow only details elements that are directly after the warning header
    // or each other. There is no guaranteed wrapper we can rely on across
    // distributions. When there are multiple warnings, the selectors will be:
    // - h3#warning+details summary
    // - h3#warning+details+details summary
    // - etc.
    // We add one more selector than expected warnings to confirm that there
    // isn't any other warning before clicking the link.
    // @todo Make this more reliable in
    //   https://www.drupal.org/project/drupal/issues/2927345.
    $selectors = [];
    for ($i = 0; $i <= count($warning_summaries); $i++) {
      $selectors[] = 'h3#warning' . implode('', array_fill(0, $i + 1, '+details')) . ' summary';
    }
    $warning_elements = $this->cssSelect(implode(', ', $selectors));

    // Confirm that there are only the expected warnings.
    $warnings = [];
    foreach ($warning_elements as $warning) {
      $warnings[] = trim($warning->getText());
    }
    $this->assertEquals($warning_summaries, $warnings);
  }

}
