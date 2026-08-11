<?php

declare(strict_types=1);

namespace Drupal\Tests\default_admin\Kernel;

use Drupal\Core\Routing\RouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Drupal\default_admin\Hook\PreprocessHooks;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\Route;

/**
 * Tests breadcrumb preprocessing for entities.
 */
#[Group('default_admin')]
#[RunTestsInSeparateProcesses]
class BreadcrumbPreprocessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'node',
    'entity_test',
    'link',
    'menu_link_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('menu_link_content');
    $this->installConfig(['filter', 'node']);
    $this->container->get('theme_installer')->install(['default_admin']);
  }

  /**
   * Tests an entity type whose bundle key does not resolve to a bundle entity.
   *
   * The entity_test type declares a bundle key but no bundle entity type, so
   * its bundle field is a plain string and reading ::entity off it yields
   * NULL. Calling ::label() on that is a fatal error, which took down every
   * edit form of every such entity type, menu links among them.
   */
  public function testEntityWithBundleKeyButNoBundleEntity(): void {
    $entity = EntityTest::create(['name' => 'A test entity']);
    $entity->save();

    $entity_type = $entity->getEntityType();
    $this->assertNotEmpty($entity_type->getKey('bundle'));
    $this->assertNull($entity_type->getBundleEntityType());
    $this->assertNull($entity->get($entity_type->getKey('bundle'))->entity);

    $variables = $this->preprocessBreadcrumb($entity, 'entity.entity_test.edit_form');

    // With no bundle entity to name it, the entity type's own singular label
    // has to stand in.
    $this->assertSame('Edit Entity Test Bundle', (string) end($variables['breadcrumb'])['text']);
  }

  /**
   * Tests the case this was reported for: editing a custom menu link.
   *
   * Menu links are what users actually hit this on, because a menu link's
   * bundle key holds the bare string 'menu_link_content' rather than a
   * reference to a bundle entity.
   *
   * The assertion deliberately does not touch the bundle field. Whether the
   * menu link keeps its bundle key or loses it, the breadcrumb has no bundle
   * entity to name it and falls back to the entity type's singular label, so
   * this stays true either way.
   *
   * @see https://www.drupal.org/project/drupal/issues/3613636
   */
  public function testMenuLinkEditForm(): void {
    $link = MenuLinkContent::create([
      'title' => 'A menu link',
      'link' => ['uri' => 'internal:/'],
      'menu_name' => 'admin',
    ]);
    $link->save();

    $variables = $this->preprocessBreadcrumb($link, 'entity.menu_link_content.edit_form');

    $this->assertSame('Edit Custom menu link', (string) end($variables['breadcrumb'])['text']);
  }

  /**
   * Tests that a resolvable bundle entity still supplies the label.
   */
  public function testEntityWithBundleEntity(): void {
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    $node = Node::create(['type' => 'article', 'title' => 'A node']);
    $node->save();

    $variables = $this->preprocessBreadcrumb($node, 'entity.node.edit_form');

    $this->assertSame('Edit Article', (string) end($variables['breadcrumb'])['text']);
  }

  /**
   * Runs the breadcrumb preprocessor against a route holding the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the route resolves to.
   * @param string $route_name
   *   The route name to report as current.
   *
   * @return array
   *   The preprocessed variables.
   */
  protected function preprocessBreadcrumb($entity, string $route_name): array {
    $entity_type_id = $entity->getEntityTypeId();
    $route = new Route('/' . $entity_type_id . '/{' . $entity_type_id . '}/edit');
    $route->setOption('parameters', [
      $entity_type_id => ['type' => 'entity:' . $entity_type_id],
    ]);
    $route_match = new RouteMatch($route_name, $route, [$entity_type_id => $entity]);

    $hooks = new PreprocessHooks(
      $this->container->get('theme.manager'),
      $this->container->get('request_stack'),
      $route_match,
      $this->container->get('config.factory'),
      $this->container->get('current_user'),
      $this->container->get('entity_type.manager'),
      $this->container->get('entity_type.bundle.info'),
      $this->container->get('plugin.manager.block'),
      $this->container->get('renderer'),
      $this->container->get('module_handler'),
    );

    $variables = [
      'breadcrumb' => [
        ['text' => 'Home', 'url' => '/'],
      ],
    ];
    $hooks->preprocessBreadcrumb($variables);

    return $variables;
  }

}
