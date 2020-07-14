<?php

namespace Drupal\Tests\help_topics\Kernel;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search\Plugin\SearchIndexingInterface;

/**
 * Tests search plugin behaviors.
 *
 * @group help_topics
 *
 * @see \Drupal\help_topics\Plugin\Search\HelpSearch
 */
class HelpSearchPluginTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['help', 'help_topics', 'search'];

  /**
   * Tests search plugin annotation and interfaces.
   */
  public function testAnnotation() {
    /** @var \Drupal\search\SearchPluginManager $manager */
    $manager = \Drupal::service('plugin.manager.search');
    /** @var \Drupal\help_topics\Plugin\Search\HelpSearch $plugin */
    $plugin = $manager->createInstance('help_search');
    $this->assertInstanceOf(AccessibleInterface::class, $plugin);
    $this->assertInstanceOf(SearchIndexingInterface::class, $plugin);
    $this->assertSame('Help', (string) $plugin->getPluginDefinition()['title']);
    $this->assertTrue($plugin->usesAdminTheme());
  }

}
