<?php

namespace Drupal\Tests\views\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\views\Plugin\views\cache\Time;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests deprecated views functionality.
 *
 * @group views
 * @group legacy
 */
class ViewsLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views'];

  /**
   * Tests the Time cache plugin.
   */
  public function testTimeCache() {
    $request = Request::createFromGlobals();
    $this->expectDeprecation('The request object must not be passed to Drupal\views\Plugin\views\cache\Time::__construct(). It is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/3154016');
    $plugin = new Time([], 'time',
      \Drupal::service('plugin.manager.views.cache')->getDefinition('time'),
      \Drupal::service('date.formatter'), $request
    );
    $view = $this->prophesize(ViewExecutable::class);
    $view->getRequest()->willReturn($request);
    $plugin->view = $view->reveal();
    $this->assertInstanceOf(Request::class, $plugin->request);
    $this->expectDeprecation('The request property of Drupal\views\Plugin\views\cache\Time is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/3154016');
    $this->assertSame($request, $plugin->request);
  }

}
