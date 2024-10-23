<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

/**
 * Tests that an install profile can implement hook_requirements().
 *
 * @group Installer
 */
class InstallerProfileRequirementsTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_requirements';

  /**
   * {@inheritdoc}
   */
  protected function setUpSettings(): void {
    // This form will never be reached.
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpRequirementsProblem(): void {
    // The parent method asserts that there are no requirements errors, but
    // this test expects a requirements error in the test method below.
    // Therefore, we override this method to suppress the parent's assertions.
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite(): void {
    // This form will never be reached.
  }

  /**
   * Assert that the profile failed hook_requirements().
   */
  public function testHookRequirementsFailure(): void {
    $this->assertSession()->pageTextContains('Testing requirements failed requirements.');
  }

}
