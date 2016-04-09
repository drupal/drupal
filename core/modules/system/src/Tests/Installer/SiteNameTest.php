<?php

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that the site name can be set during a non-interactive installation.
 *
 * @group Installer
 */
class SiteNameTest extends WebTestBase {

  /**
   * The site name to be used when testing.
   *
   * @var string
   */
  protected $siteName;

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
  function testSiteName() {
    $this->drupalGet('');
    $this->assertRaw($this->siteName, 'The site name that was set during the installation appears on the front page after installation.');
  }

}
