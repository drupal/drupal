<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests moving search from node to search_node.
 */
#[Group('navigation')]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
class NavigationUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-11.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests the removal of the navigation_shortcuts block.
   */
  public function testShortcutNavigationBlockUpdate(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('navigation'));
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('shortcut'));

    $config = \Drupal::config('navigation.block_layout');
    $sections = $config->get('sections');

    // Confirm the navigation shortcut block exists.
    // UUID of the navigation_shortcuts block.
    $uuid = '2622e40b-8786-4b8c-8883-19e49da53023';
    $this->assertArrayHasKey($uuid, $sections[0]['components']);

    $this->runUpdates();

    // Confirm the navigation shortcut block has been removed.
    $config = \Drupal::config('navigation.block_layout');
    $sections = $config->get('sections');
    $this->assertArrayNotHasKey($uuid, $sections[0]['components']);
  }

}
