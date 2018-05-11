<?php

/**
 * @file
 * Contains \Drupal\Tests\views_ui\Unit\ViewListBuilderTest.
 */

namespace Drupal\Tests\views_ui\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Entity\View;
use Drupal\views\ViewExecutableFactory;
use Drupal\views_ui\ViewListBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\views_ui\ViewListBuilder
 * @group views_ui
 */
class ViewListBuilderTest extends UnitTestCase {

  /**
   * Tests the listing of displays on a views list builder.
   *
   * @see \Drupal\views_ui\ViewListBuilder::getDisplaysList()
   * @covers ::buildRow
   */
  public function testBuildRowEntityList() {
    $storage = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $display_manager = $this->getMockBuilder('\Drupal\views\Plugin\ViewsPluginManager')
      ->disableOriginalConstructor()
      ->getMock();

    $display_manager->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValueMap([
        [
          'default',
          TRUE,
          [
            'id' => 'default',
            'title' => 'Master',
            'theme' => 'views_view',
            'no_ui' => TRUE,
            'admin' => '',
          ],
        ],
        [
          'page',
          TRUE,
          [
            'id' => 'page',
            'title' => 'Page',
            'uses_menu_links' => TRUE,
            'uses_route' => TRUE,
            'contextual_links_locations' => ['page'],
            'theme' => 'views_view',
            'admin' => 'Page admin label',
          ],
        ],
        [
          'embed',
          TRUE,
          [
            'id' => 'embed',
            'title' => 'embed',
            'theme' => 'views_view',
            'admin' => 'Embed admin label',
          ],
        ],
      ]));

    $default_display = $this->getMock('Drupal\views\Plugin\views\display\DefaultDisplay',
      ['initDisplay'],
      [[], 'default', $display_manager->getDefinition('default')]
    );
    $route_provider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
    $state = $this->getMock('\Drupal\Core\State\StateInterface');
    $menu_storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $page_display = $this->getMock('Drupal\views\Plugin\views\display\Page',
      ['initDisplay', 'getPath'],
      [[], 'default', $display_manager->getDefinition('page'), $route_provider, $state, $menu_storage]
    );
    $page_display->expects($this->any())
      ->method('getPath')
      ->will($this->onConsecutiveCalls(
        $this->returnValue('test_page'),
        $this->returnValue('<object>malformed_path</object>'),
        $this->returnValue('<script>alert("placeholder_page/%")</script>')));

    $embed_display = $this->getMock('Drupal\views\Plugin\views\display\Embed', ['initDisplay'],
      [[], 'default', $display_manager->getDefinition('embed')]
    );

    $values = [];
    $values['status'] = FALSE;
    $values['display']['default']['id'] = 'default';
    $values['display']['default']['display_title'] = 'Display';
    $values['display']['default']['display_plugin'] = 'default';

    $values['display']['page_1']['id'] = 'page_1';
    $values['display']['page_1']['display_title'] = 'Page 1';
    $values['display']['page_1']['display_plugin'] = 'page';
    $values['display']['page_1']['display_options']['path'] = 'test_page';

    $values['display']['page_2']['id'] = 'page_2';
    $values['display']['page_2']['display_title'] = 'Page 2';
    $values['display']['page_2']['display_plugin'] = 'page';
    $values['display']['page_2']['display_options']['path'] = '<object>malformed_path</object>';

    $values['display']['page_3']['id'] = 'page_3';
    $values['display']['page_3']['display_title'] = 'Page 3';
    $values['display']['page_3']['display_plugin'] = 'page';
    $values['display']['page_3']['display_options']['path'] = '<script>alert("placeholder_page/%")</script>';

    $values['display']['embed']['id'] = 'embed';
    $values['display']['embed']['display_title'] = 'Embedded';
    $values['display']['embed']['display_plugin'] = 'embed';

    $display_manager->expects($this->any())
      ->method('createInstance')
      ->will($this->returnValueMap([
        ['default', $values['display']['default'], $default_display],
        ['page', $values['display']['page_1'], $page_display],
        ['page', $values['display']['page_2'], $page_display],
        ['page', $values['display']['page_3'], $page_display],
        ['embed', $values['display']['embed'], $embed_display],
      ]));

    $container = new ContainerBuilder();
    $user = $this->getMock('Drupal\Core\Session\AccountInterface');
    $request_stack = new RequestStack();
    $request_stack->push(new Request());
    $views_data = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $route_provider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
    $executable_factory = new ViewExecutableFactory($user, $request_stack, $views_data, $route_provider);
    $container->set('views.executable', $executable_factory);
    $container->set('plugin.manager.views.display', $display_manager);
    \Drupal::setContainer($container);

    // Setup a view list builder with a mocked buildOperations method,
    // because t() is called on there.
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $view_list_builder = new TestViewListBuilder($entity_type, $storage, $display_manager);
    $view_list_builder->setStringTranslation($this->getStringTranslationStub());

    // Create new view with test values.
    $view = new View($values, 'view');

    // Get the row object created by ViewListBuilder for this test view.
    $row = $view_list_builder->buildRow($view);

    // Expected output array for view's displays.
    $expected_displays = [
      '0' => [
        'display' => 'Embed admin label',
        'path' => FALSE,
      ],
      '1' => [
        'display' => 'Page admin label',
        'path' => '/<object>malformed_path</object>',
      ],
      '2' => [
        'display' => 'Page admin label',
        'path' => '/<script>alert("placeholder_page/%")</script>',
      ],
      '3' => [
        'display' => 'Page admin label',
        'path' => '/test_page',
      ],
    ];

    // Compare the expected and generated output.
    $this->assertEquals($expected_displays, $row['data']['displays']['data']['#displays']);
  }

}

class TestViewListBuilder extends ViewListBuilder {

  public function buildOperations(EntityInterface $entity) {
    return [];
  }

}
