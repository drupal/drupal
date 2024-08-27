<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Common setup and utility methods to test projects that use semver releases.
 *
 * For classes that extend this class, the XML fixtures they use will start with
 * ::$projectTitle.
 *
 * @group update
 */
abstract class UpdateSemverTestBase extends UpdateTestBase {

  use CronRunTrait;
  use UpdateTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The title of the project being tested.
   *
   * @var string
   */
  protected $projectTitle;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'view update notifications',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * {@inheritdoc}
   */
  protected function refreshUpdateStatus($xml_map, $url = 'update-test') {
    if (!isset($xml_map['drupal'])) {
      $xml_map['drupal'] = '8.0.0';
    }
    parent::refreshUpdateStatus($xml_map, $url);
  }

  /**
   * Sets the project installed version.
   *
   * @param string $version
   *   The version number.
   */
  abstract protected function setProjectInstalledVersion($version);

}
