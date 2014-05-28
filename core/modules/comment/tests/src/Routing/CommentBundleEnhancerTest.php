<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Routing\CommentBundleEnhancerTest.
 */
namespace Drupal\comment\Tests\Routing;

use Drupal\comment\Routing\CommentBundleEnhancer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the comment bundle enhancer route enhancer.
 *
 * @see \Drupal\comment\Routing\CommentBundleEnhancer
 */
class CommentBundleEnhancerTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Comment route enhancer test',
      'description' => 'Tests the comment route enhancer.',
      'group' => 'Comment',
    );
  }

  /**
   * Data provider for testEnhancer().
   *
   * @see testEnhancer()
   *
   * @return array
   *   An array of arrays containing strings:
   *     - The bundle name.
   *     - The commented entity type.
   *     - The field name.
   */
  public function providerTestEnhancer() {
    return array(
      array(
        'node__comment',
        'comment',
        'node',
      ),
      array(
        'node__comment_forum__schnitzel',
        'comment_forum__schnitzel',
        'node',
      ),
      array(
        'node__field_foobar',
        FALSE,
        FALSE
      ),
    );
  }
  /**
   * Tests the enhancer method.
   *
   * @param string $bundle
   *   The bundle name to test.
   * @param string $field_name
   *   The field name expected to be extracted from the bundle.
   * @param string $commented_entity_type
   *   The entity type expected to be extracted from the bundle.
   *
   * @see \Drupal\comment\Routing\CommentBundleEnhancer::enhancer()
   *
   * @group Drupal
   * @group Routing
   *
   * @dataProvider providerTestEnhancer
   */
  public function testEnhancer($bundle, $field_name, $commented_entity_type) {
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->any())
      ->method('getBundleInfo')
      ->will($this->returnValue(array(
        'node__comment' => array(),
        // Test two sets of __.
        'node__comment_forum__schnitzel' => array(),
      )));
    $route_enhancer = new CommentBundleEnhancer($entity_manager);

    // Test the enhancer.
    $request = new Request();
    $defaults = array('bundle' => $bundle);
    $new_defaults = $route_enhancer->enhance($defaults, $request);
    if ($commented_entity_type) {
      // A valid comment bundle.
      $this->assertEquals($new_defaults['field_name'], $field_name);
      $this->assertEquals($new_defaults['commented_entity_type'], $commented_entity_type);
    }
    else {
      // Non-comment bundle.
      $this->assertTrue(empty($new_defaults['field_name']));
      $this->assertTrue(empty($new_defaults['commented_entity_type']));
    }
  }

}
