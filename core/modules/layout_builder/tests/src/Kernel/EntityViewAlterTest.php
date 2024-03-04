<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Route;

/**
 * @covers layout_builder_entity_view_alter
 *
 * @group layout_builder
 */
class EntityViewAlterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_discovery',
    'layout_builder',
    'layout_builder_defaults_test',
    'entity_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    entity_test_create_bundle('bundle_with_extra_fields');
    $this->installEntitySchema('entity_test');
    $this->installConfig(['layout_builder_defaults_test']);
  }

  /**
   * Tests that contextual links are removed when rendering Layout Builder.
   */
  public function testContextualLinksRemoved(): void {
    $display = LayoutBuilderEntityViewDisplay::load('entity_test.bundle_with_extra_fields.default');
    $entity = EntityTest::create();
    $build = [
      '#contextual_links' => ['entity.node.canonical'],
    ];
    // Create a fake request that starts with layout_builder.
    $request = Request::create('<front>');
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'layout_builder.test');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/'));
    $request->setSession(new Session(new MockArraySessionStorage()));
    \Drupal::requestStack()->push($request);
    // Assert the contextual links are removed.
    layout_builder_entity_view_alter($build, $entity, $display);
    $this->assertArrayNotHasKey('#contextual_links', $build);
  }

}
