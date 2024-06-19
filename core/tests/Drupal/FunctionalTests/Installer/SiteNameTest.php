<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the site name can be set during a non-interactive installation.
 *
 * @group Installer
 */
class SiteNameTest extends BrowserTestBase {

  /**
   * The site name to be used when testing.
   *
   * @var string
   */
  protected $siteName;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function installParameters() {
    $this->siteName = $this->randomMachineName();
    $parameters = parent::installParameters();
    $parameters['forms']['install_configure_form']['site_name'] = $this->siteName;
    return $parameters;
  }

  /**
   * Tests that the desired site name appears on the page after installation.
   */
  public function testSiteName(): void {
    $this->drupalGet('');
    $this->assertSession()->pageTextContains($this->siteName);
  }

}
