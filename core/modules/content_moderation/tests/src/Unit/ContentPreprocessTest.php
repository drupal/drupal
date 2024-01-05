<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Unit;

use Drupal\content_moderation\ContentPreprocess;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\node\Entity\Node;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\content_moderation\ContentPreprocess
 *
 * @group content_moderation
 */
class ContentPreprocessTest extends UnitTestCase {

  /**
   * @covers ::isLatestVersionPage
   * @dataProvider routeNodeProvider
   */
  public function testIsLatestVersionPage($route_name, $route_nid, $check_nid, $result, $message) {
    $content_preprocess = new ContentPreprocess($this->setupCurrentRouteMatch($route_name, $route_nid));
    $node = $this->setupNode($check_nid);
    $this->assertEquals($result, $content_preprocess->isLatestVersionPage($node), $message);
  }

  /**
   * Data provider for self::testIsLatestVersionPage().
   */
  public function routeNodeProvider() {
    return [
      ['entity.node.canonical', 1, 1, FALSE, 'Not on the latest version tab route.'],
      ['entity.node.latest_version', 1, 1, TRUE, 'On the latest version tab route, with the route node.'],
      ['entity.node.latest_version', 1, 2, FALSE, 'On the latest version tab route, with a different node.'],
    ];
  }

  /**
   * Mock the current route matching object.
   *
   * @param string $route_name
   *   The route to mock.
   * @param int $nid
   *   The node ID for mocking.
   *
   * @return \Drupal\Core\Routing\CurrentRouteMatch
   *   The mocked current route match object.
   */
  protected function setupCurrentRouteMatch($route_name, $nid) {
    $route_match = $this->prophesize(CurrentRouteMatch::class);
    $route_match->getRouteName()->willReturn($route_name);
    $route_match->getParameter('node')->willReturn($this->setupNode($nid));

    return $route_match->reveal();
  }

  /**
   * Mock a node object.
   *
   * @param int $nid
   *   The node ID to mock.
   *
   * @return \Drupal\node\Entity\Node
   *   The mocked node.
   */
  protected function setupNode($nid) {
    $node = $this->prophesize(Node::class);
    $node->id()->willReturn($nid);

    return $node->reveal();
  }

}
