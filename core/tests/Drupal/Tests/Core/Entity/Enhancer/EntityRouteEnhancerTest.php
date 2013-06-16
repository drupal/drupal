<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\Enhancer\EntityRouteEnhancerTest.
 */

namespace Drupal\Tests\Core\Entity\Enhancer;

use Drupal\Core\ContentNegotiation;
use Drupal\Core\Entity\Enhancer\EntityRouteEnhancer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the entity route enhancer.
 *
 * @see \Drupal\Core\Entity\Enhancer\EntityRouteEnhancer
 */
class EntityRouteEnhancerTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Entity route enhancer test',
      'description' => 'Tests the entity route enhancer.',
      'group' => 'Entity'
    );
  }

  /**
   * Tests the enhancer method.
   *
   * @see \Drupal\Core\Entity\Enhancer\EntityRouteEnhancer::enhancer()
   */
  public function testEnhancer() {
    $negotation = $this->getMock('Drupal\core\ContentNegotiation', array('getContentType'));
    $negotation->expects($this->any())
      ->method('getContentType')
      ->will($this->returnValue('html'));

    $route_enhancer = new EntityRouteEnhancer($negotation);

    // Set a controller to ensure it is not overridden.
    $request = new Request();
    $defaults = array();
    $defaults['_controller'] = 'Drupal\Tests\Core\Controller\TestController::content';
    $defaults['_entity_form'] = 'entity_test.default';
    $new_defaults = $route_enhancer->enhance($defaults, $request);
    $this->assertEquals($defaults, $new_defaults, '_controller got overridden.');

    // Set _entity_form and ensure that the form controller is set.
    $defaults = array();
    $defaults['_entity_form'] = 'entity_test.default';
    $defaults = $route_enhancer->enhance($defaults, $request);
    $this->assertEquals('\Drupal\Core\Entity\HtmlEntityFormController::content', $defaults['_controller'], 'The entity form controller was not set.');

    // Set _entity_list and ensure that the entity list controller is set.
    $defaults = array();
    $defaults['_entity_list'] = 'entity_test.default';
    $defaults = $route_enhancer->enhance($defaults, $request);
    $this->assertEquals('controller.page:content', $defaults['_controller']);
    $this->assertEquals('\Drupal\Core\Entity\Controller\EntityListController::listing', $defaults['_content'], 'The entity list controller was not set.');
    $this->assertEquals('entity_test.default', $defaults['entity_type']);
    $this->assertFalse(isset($defaults['_entity_list']));
  }

}
