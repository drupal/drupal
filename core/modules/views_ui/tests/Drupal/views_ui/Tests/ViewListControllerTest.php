<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\ViewListControllerTest
 */

namespace Drupal\views_ui\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Entity\View;
use Drupal\views\ViewExecutableFactory;
use Drupal\views_ui\ViewListController;

class ViewListControllerTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Views List Controller Unit Test',
      'description' => 'Unit tests the views list controller',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests the listing of displays on a views list.
   *
   * @see \Drupal\views_ui\ViewListController::getDisplaysList().
   */
  public function testBuildRowEntityList() {
    $storage_controller = $this->getMockBuilder('Drupal\views\ViewStorageController')
      ->disableOriginalConstructor()
      ->getMock();
    $display_manager = $this->getMockBuilder('\Drupal\views\Plugin\ViewsPluginManager')
      ->disableOriginalConstructor()
      ->getMock();

    $display_manager->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValueMap(array(
        array(
          'default',
          array(
            'id' => 'default',
            'title' => 'Master',
            'theme' => 'views_view',
            'no_ui' => TRUE,
          )
        ),
        array(
          'page',
          array(
            'id' => 'page',
            'title' => 'Page',
            'uses_hook_menu' => TRUE,
            'uses_route' => TRUE,
            'contextual_links_locations' => array('page'),
            'theme' => 'views_view',
            'admin' => 'Page admin label',
          )
        ),
        array(
          'embed',
          array(
            'id' => 'embed',
            'title' => 'embed',
            'theme' => 'views_view',
            'admin' => 'Embed admin label',
          )
        ),
      )));


    $default_display = $this->getMock('Drupal\views\Plugin\views\display\DefaultDisplay',
      array('initDisplay'),
      array(array(), 'default', $display_manager->getDefinition('default'))
    );
    $route_provider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
    $state = $this->getMock('\Drupal\Core\KeyValueStore\StateInterface');
    $page_display = $this->getMock('Drupal\views\Plugin\views\display\Page',
      array('initDisplay', 'getPath'),
      array(array(), 'default', $display_manager->getDefinition('page'), $route_provider, $state)
    );
    $page_display->expects($this->any())
      ->method('getPath')
      ->will($this->returnValue('test_page'));

    $embed_display = $this->getMock('Drupal\views\Plugin\views\display\Embed', array('initDisplay'),
      array(array(), 'default', $display_manager->getDefinition('embed'))
    );

    $values = array();
    $values['status'] = FALSE;
    $values['display']['default']['id'] = 'default';
    $values['display']['default']['display_title'] = 'Display';
    $values['display']['default']['display_plugin'] = 'default';

    $values['display']['page_1']['id'] = 'page_1';
    $values['display']['page_1']['display_title'] = 'Page 1';
    $values['display']['page_1']['display_plugin'] = 'page';
    $values['display']['page_1']['display_options']['path'] = 'test_page';

    $values['display']['embed']['id'] = 'embed';
    $values['display']['embed']['display_title'] = 'Embedded';
    $values['display']['embed']['display_plugin'] = 'embed';

    $display_manager->expects($this->any())
      ->method('createInstance')
      ->will($this->returnValueMap(array(
        array('default', $values['display']['default'], $default_display),
        array('page', $values['display']['page_1'], $page_display),
        array('embed', $values['display']['embed'], $embed_display),
      )));

    $container = new ContainerBuilder();
    $user = $this->getMock('Drupal\Core\Session\AccountInterface');
    $executable_factory = new ViewExecutableFactory($user);
    $container->set('views.executable', $executable_factory);
    $container->set('plugin.manager.views.display', $display_manager);
    \Drupal::setContainer($container);

    // Setup a view list controller with a mocked buildOperations method,
    // because t() is called on there.
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $view_list_controller = new TestViewListController($entity_type, $storage_controller, $display_manager);
    $view_list_controller->setTranslationManager($this->getStringTranslationStub());

    $view = new View($values, 'view');

    $row = $view_list_controller->buildRow($view);

    $this->assertEquals(array('Embed admin label', 'Page admin label'), $row['data']['view_name']['data']['#displays'], 'Wrong displays got added to view list');
    $this->assertEquals($row['data']['path'], '/test_page', 'The path of the page display is not added.');
  }

}

class TestViewListController extends ViewListController {

  public function buildOperations(EntityInterface $entity) {
    return array();
  }

}
