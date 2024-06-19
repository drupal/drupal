<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Kernel;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search\Plugin\SearchIndexingInterface;

/**
 * Tests search plugin behaviors.
 *
 * @group help
 *
 * @see \Drupal\help\Plugin\Search\HelpSearch
 */
class HelpSearchPluginTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['help', 'search'];

  /**
   * Tests search plugin annotation and interfaces.
   */
  public function testAnnotation(): void {
    /** @var \Drupal\search\SearchPluginManager $manager */
    $manager = \Drupal::service('plugin.manager.search');
    /** @var \Drupal\help\Plugin\Search\HelpSearch $plugin */
    $plugin = $manager->createInstance('help_search');
    $this->assertInstanceOf(AccessibleInterface::class, $plugin);
    $this->assertInstanceOf(SearchIndexingInterface::class, $plugin);
    $this->assertSame('Help', (string) $plugin->getPluginDefinition()['title']);
    $this->assertTrue($plugin->usesAdminTheme());
  }

}
