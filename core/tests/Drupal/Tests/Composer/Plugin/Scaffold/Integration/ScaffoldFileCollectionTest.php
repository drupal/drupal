<?php

namespace Drupal\Tests\Composer\Plugin\Scaffold\Integration;

use PHPUnit\Framework\TestCase;
use Drupal\Tests\Composer\Plugin\Scaffold\Fixtures;
use Drupal\Composer\Plugin\Scaffold\Operations\AppendOp;
use Drupal\Composer\Plugin\Scaffold\Operations\SkipOp;
use Drupal\Composer\Plugin\Scaffold\Operations\ScaffoldFileCollection;

/**
 * @coversDefaultClass \Drupal\Composer\Plugin\Scaffold\Operations\ScaffoldFileCollection
 *
 * @group Scaffold
 */
class ScaffoldFileCollectionTest extends TestCase {

  /**
   * @covers ::__construct
   */
  public function testCreate() {
    $fixtures = new Fixtures();
    $locationReplacements = $fixtures->getLocationReplacements();
    $scaffold_file_fixtures = [
      'fixtures/drupal-assets-fixture' => [
        '[web-root]/index.php' => $fixtures->replaceOp('drupal-assets-fixture', 'index.php'),
        '[web-root]/.htaccess' => $fixtures->replaceOp('drupal-assets-fixture', '.htaccess'),
        '[web-root]/robots.txt' => $fixtures->replaceOp('drupal-assets-fixture', 'robots.txt'),
        '[web-root]/sites/default/default.services.yml' => $fixtures->replaceOp('drupal-assets-fixture', 'default.services.yml'),
      ],
      'fixtures/drupal-profile' => [
        '[web-root]/sites/default/default.services.yml' => $fixtures->replaceOp('drupal-profile', 'profile.default.services.yml'),
      ],
      'fixtures/drupal-drupal' => [
        '[web-root]/.htaccess' => new SkipOp(),
        '[web-root]/robots.txt' => $fixtures->appendOp('drupal-drupal-test-append', 'append-to-robots.txt'),
      ],
    ];
    $sut = new ScaffoldFileCollection($scaffold_file_fixtures, $locationReplacements);
    $resolved_file_mappings = iterator_to_array($sut);
    // Confirm that the keys of the output are the same as the keys of the
    // input.
    $this->assertEquals(array_keys($scaffold_file_fixtures), array_keys($resolved_file_mappings));
    // '[web-root]/robots.txt' is now a SkipOp, as it is now part of an
    // append operation.
    $this->assertEquals([
      '[web-root]/index.php',
      '[web-root]/.htaccess',
      '[web-root]/robots.txt',
      '[web-root]/sites/default/default.services.yml',
    ], array_keys($resolved_file_mappings['fixtures/drupal-assets-fixture']));
    $this->assertInstanceOf(SkipOp::class, $resolved_file_mappings['fixtures/drupal-assets-fixture']['[web-root]/robots.txt']->op());

    $this->assertEquals([
      '[web-root]/sites/default/default.services.yml',
    ], array_keys($resolved_file_mappings['fixtures/drupal-profile']));

    $this->assertEquals([
      '[web-root]/.htaccess',
      '[web-root]/robots.txt',
    ], array_keys($resolved_file_mappings['fixtures/drupal-drupal']));

    // Test that .htaccess is skipped.
    $this->assertInstanceOf(SkipOp::class, $resolved_file_mappings['fixtures/drupal-assets-fixture']['[web-root]/.htaccess']->op());
    // Test that the expected append operation exists.
    $this->assertInstanceOf(AppendOp::class, $resolved_file_mappings['fixtures/drupal-drupal']['[web-root]/robots.txt']->op());
  }

}
