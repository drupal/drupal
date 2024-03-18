<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Views;

use Drupal\KernelTests\KernelTestBase;
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
    $this->expectDeprecation('Calling Drupal\taxonomy\Plugin\views\argument\Taxonomy::__construct() with the $termStorage argument as \Drupal\Core\Entity\EntityStorageInterface is deprecated in drupal:10.3.0 and it will require Drupal\Core\Entity\EntityRepositoryInterface in drupal:11.0.0. See https://www.drupal.org/node/3427843');
    $plugin = \Drupal::service('plugin.manager.views.argument')->createInstance('taxonomy_views_argument_test', []);
    $this->assertInstanceOf(TaxonomyViewsArgumentTest::class, $plugin);
  }

}
