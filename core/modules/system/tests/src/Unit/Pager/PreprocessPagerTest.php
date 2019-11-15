<?php

namespace Drupal\Tests\system\Unit\Pager;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Tests pager preprocessing.
 *
 * @group system
 */
class PreprocessPagerTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $pager_manager = $this->getMockBuilder('Drupal\Core\Pager\PagerManager')
      ->disableOriginalConstructor()
      ->getMock();
    $pager = $this->getMockBuilder('Drupal\Core\Pager\Pager')
      ->disableOriginalConstructor()
      ->getMock();
    $url_generator = $this->getMockBuilder('Drupal\Core\Routing\UrlGenerator')
      ->disableOriginalConstructor()
      ->getMock();

    $pager->method('getTotalPages')->willReturn(2);
    $pager->method('getCurrentPage')->willReturn(1);

    $url_generator->method('generateFromRoute')->willReturn('');

    $pager_manager->method('getPager')->willReturn($pager);
    $pager_manager->method('getUpdatedParameters')->willReturn('');

    $container = new ContainerBuilder();
    $container->set('pager.manager', $pager_manager);
    $container->set('url_generator', $url_generator);
    \Drupal::setContainer($container);
  }

  /**
   * Tests template_preprocess_pager() when an empty #quantity is passed.
   *
   * @covers ::template_preprocess_pager
   */
  public function testQuantityNotSet() {
    require_once $this->root . '/core/includes/pager.inc';
    $variables = [
      'pager' => [
        '#element' => '',
        '#parameters' => [],
        '#quantity' => '',
        '#route_name' => '',
        '#tags' => '',
      ],
    ];
    template_preprocess_pager($variables);

    $this->assertEquals(['first', 'previous'], array_keys($variables['items']));
  }

}
