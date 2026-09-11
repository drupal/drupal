<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\Core\Utility\PhpRequirements;

/**
 * Provides helper methods for the requirements page.
 */
trait RequirementsPageTrait {

  /**
   * Handles the update requirements page.
   */
  protected function updateRequirementsProblem(): void {
    // Assert a warning is shown on older test environments.
    $links = $this->getSession()->getPage()->findAll('named', ['link', 'try again']);

    // Get the default Drupal core PHP requirements.
    if ($links && version_compare(phpversion(), PhpRequirements::getMinimumSupportedPhp()) < 0) {
      $this->assertSession()->pageTextNotContains('Errors found');
      $this->assertWarningSummaries(['PHP']);
      $this->clickLink('try again');
      $this->checkForMetaRefresh();
    }
  }

  /**
   * Continues installation when the expected warnings are found.
   *
   * @param string[] $expected_warnings
   *   A list of warning summaries to expect on the requirements screen (e.g.
   *   'PHP', 'PHP OPcode caching', etc.). If only the expected warnings
   *   are found, the test will click the "continue anyway" link to go to the
   *   next screen of the installer. If an expected warning is not found, or if
   *   a warning not in the list is present, a fail is raised.
   */
  protected function continueOnExpectedWarnings($expected_warnings = []): void {
    $this->assertSession()->pageTextNotContains('Errors found');
    $this->assertWarningSummaries($expected_warnings);
    $this->clickLink('continue anyway');
    $this->checkForMetaRefresh();
  }

  /**
   * Asserts the given warning summaries are present on the page.
   *
   * If an expected warning is not found, or if a warning not in the list is
   * present, a fail is raised.
   *
   * @param string[] $summaries
   *   A list of warning summaries to expect on the requirements screen (e.g.
   *   'PHP', 'PHP OPcode caching', etc.).
   */
  protected function assertWarningSummaries(array $summaries): void {
    $this->assertRequirementSummaries($summaries, 'warning');
  }

  /**
   * Asserts the given error summaries are present on the page.
   *
   * If an expected error is not found, or if an error not in the list is
   * present, a fail is raised.
   *
   * @param string[] $summaries
   *   A list of error summaries to expect on the requirements screen (e.g.
   *   'PHP', 'PHP OPcode caching', etc.).
   */
  protected function assertErrorSummaries(array $summaries): void {
    $this->assertRequirementSummaries($summaries, 'error');
  }

  /**
   * Asserts the given requirements section summaries are present on the page.
   *
   * If an expected requirements message  is not found, or if a message not in
   * the list is present, a fail is raised.
   *
   * @param string[] $summaries
   *   A list of warning summaries to expect on the requirements screen (e.g.
   *   'PHP', 'PHP OPcode caching', etc.).
   * @param string $type
   *   The type of requirement, either 'warning' or 'error'.
   */
  protected function assertRequirementSummaries(array $summaries, string $type): void {
    // The selectors are different for the admin themes, because they override
    // the status report template. Each of them uses its own details wrapper
    // class.
    $content = $this->getSession()->getPage()->getContent();
    $details_wrappers = [
      'default_admin/css/theme/maintenance-page.css' => 'default-admin-details__wrapper',
      'claro/css/theme/maintenance-page.css' => 'claro-details__wrapper',
    ];
    $details_wrapper = NULL;
    foreach ($details_wrappers as $stylesheet => $class) {
      if (stripos($content, $stylesheet) !== FALSE) {
        $details_wrapper = $class;
        break;
      }
    }

    $selectors = [];
    if ($details_wrapper) {
      // In the admin themes each requirement heading is present in a div with
      // the class system-status-report__status-title. There is one summary
      // element per requirement type and it is adjacent to a div with the
      // theme's details wrapper class.
      $selectors[] = 'summary#' . $type . '+.' . $details_wrapper . ' .system-status-report__status-title';
    }
    else {
      // Allow only details elements that are directly after the warning/error
      // header or each other. There is no guaranteed wrapper we can rely on
      // across distributions. When there are multiple warnings, the selectors
      // will be:
      // - h3#warning+details summary
      // - h3#warning+details+details summary
      // - etc.
      // For errors, the selectors are the same except that they are h3#error.
      // We add one more selector than expected requirements to confirm that
      // there isn't any other requirement message before clicking the link.
      // @todo Make this more reliable in
      //   https://www.drupal.org/project/drupal/issues/2927345.
      for ($i = 0; $i <= count($summaries); $i++) {
        $selectors[] = 'h3#' . $type . implode('', array_fill(0, $i + 1, '+details')) . ' summary';
      }
    }
    $elements = $this->cssSelect(implode(', ', $selectors));

    // Confirm that there are only the expected requirements.
    $requirements = [];
    foreach ($elements as $requirement) {
      $requirements[] = trim($requirement->getText());
    }
    $this->assertEquals($summaries, $requirements);
  }

}
