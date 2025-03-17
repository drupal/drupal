<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

/**
 * Tests installing a profile that implements InstallRequirementsInterface.
 *
 * @group Installer
 */
class ProfileRequirementsTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'profile_install_requirements';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
   * Test Requirements are picked up.
   */
  public function testRequirementsFailure(): void {
    $this->assertSession()->pageTextContains('Testing requirements failed requirements.');
  }

}
