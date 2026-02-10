<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Plugin\Scaffold\Functional;

use Drupal\BuildTests\Framework\BuildTestBase;
use Drupal\Tests\Composer\Plugin\ExecTrait;
use Drupal\Tests\Composer\Plugin\Scaffold\Fixtures;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests DrupalInstalled.php hash changes when scaffolding is run.
 */
#[Group('Scaffold')]
#[Group('#slow')]
class DrupalInstalledTest extends BuildTestBase {
  use ExecTrait;

  /**
   * Directory to perform the tests in.
   *
   * @var string
   */
  protected $fixturesDir;

  /**
   * The Fixtures object.
   *
   * @var \Drupal\Tests\Composer\Plugin\Scaffold\Fixtures
   */
  protected $fixtures;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fixtures = new Fixtures();
    $this->fixtures->createIsolatedComposerCacheDir();
    $this->fixturesDir = $this->fixtures->tmpDir($this->name());
    $replacements = ['SYMLINK' => 'false', 'PROJECT_ROOT' => $this->fixtures->projectRoot()];
    $this->fixtures->cloneFixtureProjects($this->fixturesDir, $replacements);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Remove any temporary directories et. al. that were created.
    $this->fixtures->tearDown();

    parent::tearDown();
  }

  /**
   * Tests DrupalInstalled.php hash changes when scaffolding is run.
   */
  public function testDrupalInstalledHash(): void {
    $topLevelProjectDir = 'drupal-installed-fixture';
    $sut = $this->fixturesDir . '/' . $topLevelProjectDir;

    $this->mustExec("composer install --no-ansi", $sut);
    $original_version_hash = sha1_file($sut . '/vendor/drupal/DrupalInstalled.php');

    // Require two fixtures and ensure that the DrupalInstalled.php file is
    // updated.
    $this->mustExec("composer require --no-ansi --no-interaction fixtures/empty-file:dev-main fixtures/scaffold-override-fixture:dev-main", $sut);
    $two_fixtures_hash = sha1_file($sut . '/vendor/drupal/DrupalInstalled.php');
    $this->assertNotEquals($original_version_hash, $two_fixtures_hash);

    // Remove one fixture and ensure the hash is not equal to the original or
    // the hash with two fixtures.
    $this->mustExec("composer remove --no-ansi --no-interaction fixtures/empty-file", $sut);
    $one_fixture_hash = sha1_file($sut . '/vendor/drupal/DrupalInstalled.php');
    $this->assertNotEquals($original_version_hash, $one_fixture_hash);
    $this->assertNotEquals($two_fixtures_hash, $one_fixture_hash);

    // Add the fixture back and ensure the hash is changed and equal to the
    // previous hash for two fixtures.
    $this->mustExec("composer require --no-ansi --no-interaction fixtures/empty-file:dev-main", $sut);
    $this->assertEquals($two_fixtures_hash, sha1_file($sut . '/vendor/drupal/DrupalInstalled.php'));
  }

}
