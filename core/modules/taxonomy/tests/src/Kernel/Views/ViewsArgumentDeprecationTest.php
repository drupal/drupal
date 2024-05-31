<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Views;

use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\TermStorage;
use Drupal\taxonomy_test\Plugin\views\argument\TaxonomyViewsArgumentTest;

/**
 * Tests deprecation messages in views argument plugins.
 *
 * @group taxonomy
 */
class ViewsArgumentDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'taxonomy',
    'taxonomy_test',
    'views',
  ];

  /**
   * Test the deprecation message in ViewsArgument plugin.
   *
   * @group legacy
   */
  public function testDeprecation(): void {
    $this->expectDeprecation('Passing either \Drupal\Core\Entity\EntityStorageInterface or \Drupal\Core\Entity\EntityTypeManagerInterface to Drupal\views\Plugin\views\argument\EntityArgument::__construct() as argument 4 is deprecated in drupal:10.3.0 and will be removed before drupal:11.0.0. Pass a Drupal\Core\Entity\EntityRepositoryInterface instead. See https://www.drupal.org/node/3441945');
    $this->expectDeprecation('Not passing the \Drupal\Core\Entity\EntityTypeManagerInterface to Drupal\views\Plugin\views\argument\EntityArgument::__construct() as argument 5 is deprecated in drupal:10.3.0 and will be required before drupal:11.0.0. See https://www.drupal.org/node/3441945');

    $plugin = \Drupal::service('plugin.manager.views.argument')->createInstance('taxonomy_views_argument_test', []);
    $this->assertInstanceOf(TaxonomyViewsArgumentTest::class, $plugin);

    $this->expectDeprecation('The property termStorage (taxonomy_term storage service) is deprecated in Drupal\taxonomy_test\Plugin\views\argument\TaxonomyViewsArgumentTest and will be removed before Drupal 11.0.0. See https://www.drupal.org/node/3441945');

    $storage = $plugin->termStorage;
    $this->assertInstanceOf(TermStorage::class, $storage);

  }

}
