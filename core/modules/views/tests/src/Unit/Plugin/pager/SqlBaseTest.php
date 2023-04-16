<?php

namespace Drupal\Tests\views\Unit\Plugin\pager;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\pager\SqlBase
 * @group views
 */
class SqlBaseTest extends UnitTestCase {

  /**
   * The mock pager plugin instance.
   *
   * @var \Drupal\views\Plugin\views\pager\SqlBase|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pager;

  /**
   * The mock view instance.
   *
   * @var \Drupal\views\ViewExecutable|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $view;

  /**
   * The mock display plugin instance.
   *
   * @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $display;

  protected function setUp(): void {
    parent::setUp();

    $this->pager = $this->getMockBuilder('Drupal\views\Plugin\views\pager\SqlBase')
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    /** @var \Drupal\views\ViewExecutable|\PHPUnit\Framework\MockObject\MockObject $view */
    $this->view = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();

    $query = $this->getMockBuilder(QueryPluginBase::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->view->query = $query;

    $this->display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Tests the query() method.
   *
   * @see \Drupal\views\Plugin\views\pager\SqlBase::query()
   */
  public function testQuery() {
    $request = new Request([
      'items_per_page' => 'All',
    ]);
    $this->view->expects($this->any())
      ->method('getRequest')
      ->will($this->returnValue($request));

    $options = [];
    $this->pager->init($this->view, $this->display, $options);
    $this->pager->query();
    $this->assertSame(10, $this->pager->options['items_per_page']);

    $options = [
      'expose' => [
        'items_per_page' => TRUE,
        'items_per_page_options_all' => TRUE,
      ],
    ];
    $this->pager->init($this->view, $this->display, $options);
    $this->pager->query();
    $this->assertSame(0, $this->pager->options['items_per_page']);
  }

}
