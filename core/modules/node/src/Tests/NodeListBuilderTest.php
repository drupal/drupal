<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeListBuilderTest.
 */

namespace Drupal\node\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests the admin listing fallback when views is not enabled.
 *
 * @group node
 */
class NodeListBuilderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'user'];

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
  }


  /**
   * Tests that the correct cache contexts are set.
   */
  public function testCacheContexts() {
    /** @var \Drupal\Core\Entity\EntityListBuilderInterface $list_builder */
    $list_builder = $this->container->get('entity.manager')->getListBuilder('node');

    $build = $list_builder->render();
    $this->container->get('renderer')->render($build);

    $this->assertEqual(['url.query_args.pagers:0', 'user.node_grants:view'], $build['#cache']['contexts']);
  }

}
