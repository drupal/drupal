<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the interactive installer installing the standard profile.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
class StandardInstallerTest extends ConfigAfterInstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * Ensures that the welcome page is shown after installation.
   */
  public function testInstaller(): void {
    $this->assertSession()->addressEquals('admin/welcome');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite(): void {
    // Test that the correct theme is being used. This is the last installer
    // step. The profile has installed claro and olivero by now, and a hook
    // implementation of the install theme attaches the stylesheet, so this also
    // covers that the install theme keeps its hook implementations.
    $this->assertSession()->responseNotContains('olivero');
    $this->assertSession()->responseContains('default_admin/css/theme/install-page.css');
    parent::setUpSite();
  }

  /**
   * Ensures that the exported standard configuration is up to date.
   */
  public function testStandardConfig(): void {
    $skipped_config = [];
    // FunctionalTestSetupTrait::installParameters() uses Drupal as site name
    // and simpletest@example.com as mail address.
    $skipped_config['system.site'][] = 'name: Drupal';
    $skipped_config['system.site'][] = 'mail: simpletest@example.com';
    // \Drupal\filter\Entity\FilterFormat::toArray() drops the roles of filter
    // formats.
    $skipped_config['filter.format.basic_html'][] = 'roles:';
    $skipped_config['filter.format.basic_html'][] = '- authenticated';
    $skipped_config['filter.format.full_html'][] = 'roles:';
    $skipped_config['filter.format.full_html'][] = '- administrator';
    $skipped_config['filter.format.restricted_html'][] = 'roles:';
    $skipped_config['filter.format.restricted_html'][] = '- anonymous';
    // The site UUID is set dynamically for each installation.
    $skipped_config['system.site'][] = 'uuid: ' . $this->config('system.site')->get('uuid');

    $this->assertInstalledConfig($skipped_config);
  }

}
