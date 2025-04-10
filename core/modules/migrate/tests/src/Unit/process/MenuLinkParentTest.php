<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\process\MenuLinkParent;

// cspell:ignore plid

/**
 * Tests the menu link parent process plugin.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\MenuLinkParent
 * @group migrate
 */
class MenuLinkParentTest extends MigrateProcessTestCase {

  /**
   * A MigrationInterface prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $migration;

  /**
   * A MigrateLookupInterface prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $migrateLookup;

  /**
   * A Path validator prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $pathValidator;

  /**
   * The menu link entity storage handler.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $menuLinkStorage;

  /**
   * The menu link plugin manager.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $menuLinkManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migration = $this->prophesize(MigrationInterface::class);
    $this->migrateLookup = $this->prophesize(MigrateLookupInterface::class);
    $this->menuLinkManager = $this->prophesize(MenuLinkManagerInterface::class);
    $this->menuLinkStorage = $this->prophesize(EntityStorageInterface::class);
    $container = new ContainerBuilder();
    $container->set('migrate.lookup', $this->migrateLookup->reveal());

    $this->pathValidator = $this->prophesize(PathValidatorInterface::class);
    $container->set('path.validator', $this->pathValidator->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests that an exception is thrown for invalid options.
   *
   * @param array $configuration
   *   The plugin configuration being tested.
   * @param bool $is_valid
   *   TRUE if the configuration is valid, FALSE if not.
   *
   * @dataProvider providerConstructorException
   */
  public function testConstructorException(array $configuration, bool $is_valid): void {
    if (!$is_valid) {
      $this->expectException('TypeError');
      $this->expectExceptionMessage('Cannot assign string to property ' . MenuLinkParent::class . '::$lookupMigrations of type array');
    }
    $plugin = new MenuLinkParent($configuration, 'map', [], $this->migrateLookup->reveal(), $this->menuLinkManager->reveal(), $this->menuLinkStorage->reveal(), $this->migration->reveal());
    if ($is_valid) {
      $this->assertInstanceOf(MenuLinkParent::class, $plugin);
    }
  }

  /**
   * Provides data for testConstructorException().
   */
  public static function providerConstructorException(): array {
    return [
      'default configuration is valid' => [
        'configuration' => [],
        'is_valid' => TRUE,
      ],
      'lookup_migrations = null is valid' => [
        'configuration' => ['lookup_migrations' => NULL],
        'is_valid' => TRUE,
      ],
      'bypass migration lookup is valid' => [
        'configuration' => ['lookup_migrations' => []],
        'is_valid' => TRUE,
      ],
      'a list of migrations is valid' => [
        'configuration' => ['lookup_migrations' => ['this_migration', 'another_migration']],
        'is_valid' => TRUE,
      ],
      'a single string is not valid' => [
        'configuration' => ['lookup_migrations' => 'this_migration'],
        'is_valid' => FALSE,
      ],
    ];
  }

  /**
   * Tests that an exception is thrown when the parent menu link is not found.
   *
   * @param string[] $source_value
   *   The source value(s) for the migration process plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\migrate\MigrateSkipRowException
   *
   * @dataProvider providerTransformException
   */
  public function testTransformException(array $source_value): void {
    [$parent_id, $menu_name] = $source_value;
    $this->migrateLookup->lookup(NULL, [1])->willReturn([]);
    $plugin = new MenuLinkParent([], 'map', [], $this->migrateLookup->reveal(), $this->menuLinkManager->reveal(), $this->menuLinkStorage->reveal(), $this->migration->reveal());
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage("No parent link found for plid '$parent_id' in menu '$menu_name'.");
    $plugin->transform($source_value, $this->migrateExecutable, $this->row, 'destination');
  }

  /**
   * Provides data for testTransformException().
   */
  public static function providerTransformException() {
    // The parent ID does not for the following tests.
    return [
      'parent link external and could not be loaded' => [
        'source_value' => [1, 'admin', 'http://example.com'],
      ],
      'parent link path/menu name not passed' => [
        'source_value' => [1, NULL, NULL],
      ],
      'parent link is an internal URI that does not exist' => [
        'source_value' => [1, NULL, 'admin/structure'],
      ],
    ];
  }

  /**
   * Tests the menu link content process plugin.
   *
   * @param string[] $source_value
   *   The source value(s) for the migration process plugin.
   * @param string $lookup_result
   *   The ID value to be returned from migration_lookup.
   * @param string $plugin_id
   *   The menu link plugin ID.
   * @param string $route_name
   *   A route to create.
   * @param string $expected_result
   *   The expected value(s) of the migration process plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\migrate\MigrateSkipRowException
   *
   * @dataProvider providerMenuLinkParent
   */
  public function testMenuLinkParent(array $source_value, $lookup_result, $plugin_id, $route_name, $expected_result): void {
    [$parent_id, $menu_name, $parent_link_path] = $source_value;
    $this->migrateLookup->lookup(NULL, [$parent_id])
      ->willReturn([['id' => $lookup_result]]);
    if ($route_name) {
      $plugin_definition = ['menu_name' => $menu_name];
      $static_override = $this->prophesize(StaticMenuLinkOverridesInterface::class);
      $static_override = $static_override->reveal();
      $menu_link = new MenuLinkDefault([], $plugin_id, $plugin_definition, $static_override);
      $this->menuLinkManager->loadLinksByRoute($route_name, [], 'admin')
        ->willReturn([$plugin_id => $menu_link]);

      $url = new Url($route_name, [], []);
      $this->pathValidator->getUrlIfValidWithoutAccessCheck($parent_link_path)
        ->willReturn($url);
    }
    $result = $this->doTransform($source_value, $plugin_id);
    $this->assertSame($expected_result, $result);
  }

  /**
   * Provides data for testMenuLinkParent().
   */
  public static function providerMenuLinkParent() {
    return [
      'menu link is route item' => [
        'source_value' => [0, NULL, NULL],
        'lookup_result' => NULL,
        'plugin_id' => NULL,
        'route_name' => NULL,
        'expected_result' => '',
      ],
      'parent id exists' => [
        'source_value' => [1, NULL, NULL],
        'lookup_result' => 1,
        'plugin_id' => 'menu_link_content:abc',
        'route_name' => NULL,
        'expected_result' => 'menu_link_content:abc',
      ],
      'no parent id internal route' => [
        'source_value' => [20, 'admin', 'admin/content'],
        'lookup_result' => NULL,
        'plugin_id' => 'system.admin_structure',
        'route_name' => 'system.admin_content',
        'expected_result' => 'system.admin_structure',
      ],
      'external' => [
        'source_value' => [9054, 'admin', 'http://example.com'],
        'lookup_result' => 9054,
        'plugin_id' => 'menu_link_content:fe151460-dfa2-4133-8864-c1746f28ab27',
        'route_name' => NULL,
        'expected_result' => 'menu_link_content:fe151460-dfa2-4133-8864-c1746f28ab27',
      ],
    ];
  }

  /**
   * Helper to finish setup and run the test.
   *
   * @param string[] $source_value
   *   The source value(s) for the migration process plugin.
   * @param string $plugin_id
   *   The menu link plugin ID.
   *
   * @return string
   *   The transformed menu link.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   */
  public function doTransform(array $source_value, $plugin_id) {
    [$parent_id, $menu_name, $parent_link_path] = $source_value;

    $menu_link_content = $this->prophesize(MenuLinkContent::class);
    $menu_link_content->getPluginId()->willReturn($plugin_id);

    $this->menuLinkStorage->load($parent_id)->willReturn($menu_link_content);
    $this->menuLinkStorage->loadByProperties([
      'menu_name' => $menu_name,
      'link.uri' => $parent_link_path,
    ])->willReturn([
      $parent_id => $menu_link_content,
    ]);

    $plugin = new MenuLinkParent([], 'menu_link', [], $this->migrateLookup->reveal(), $this->menuLinkManager->reveal(), $this->menuLinkStorage->reveal(), $this->migration->reveal());
    return $plugin->transform($source_value, $this->migrateExecutable, $this->row, 'destination');
  }

  /**
   * Tests the lookup_migrations option.
   *
   * @param int $plid
   *   The ID of the parent menu link.
   * @param array $configuration
   *   The plugin configuration being tested.
   * @param string $expected_result
   *   The expected value(s) of the migration process plugin.
   *
   * @dataProvider providerLookupMigrations
   */
  public function testLookupMigrations(int $plid, array $configuration, string $expected_result): void {
    $source_value = [$plid, 'some_menu', 'https://www.example.com'];

    $this->migration->id()
      ->willReturn('this_migration');
    $this->migrateLookup->lookup('this_migration', [1])
      ->willReturn([['id' => 101]]);
    $this->migrateLookup->lookup('some_migration', [2])
      ->willReturn([['id' => 202]]);
    $this->migrateLookup->lookup('some_migration', [3])
      ->willReturn([]);
    $this->migrateLookup->lookup('another_migration', [3])
      ->willReturn([['id' => 303]]);

    $menu_link_content_this = $this->prophesize(MenuLinkContent::class);
    $menu_link_content_this->getPluginId()->willReturn('menu_link_content:this_migration');
    $this->menuLinkStorage->load(101)->willReturn($menu_link_content_this);
    $menu_link_content_some = $this->prophesize(MenuLinkContent::class);
    $menu_link_content_some->getPluginId()->willReturn('menu_link_content:some_migration');
    $this->menuLinkStorage->load(202)->willReturn($menu_link_content_some);
    $menu_link_content_another = $this->prophesize(MenuLinkContent::class);
    $menu_link_content_another->getPluginId()->willReturn('menu_link_content:another_migration');
    $this->menuLinkStorage->load(303)->willReturn($menu_link_content_another);

    $plugin = new MenuLinkParent($configuration, 'menu_link', [], $this->migrateLookup->reveal(), $this->menuLinkManager->reveal(), $this->menuLinkStorage->reveal(), $this->migration->reveal());
    $result = $plugin->transform($source_value, $this->migrateExecutable, $this->row, 'destination');
    $this->assertSame($expected_result, $result);
  }

  /**
   * Provides data for testLookupMigrations().
   */
  public static function providerLookupMigrations(): array {
    return [
      'default configuration' => [
        'plid' => 1,
        'configuration' => [],
        'expected_result' => 'menu_link_content:this_migration',
      ],
      'some migration' => [
        'plid' => 2,
        'configuration' => ['lookup_migrations' => ['some_migration', 'another_migration']],
        'expected_result' => 'menu_link_content:some_migration',
      ],
      'another migration' => [
        'plid' => 3,
        'configuration' => ['lookup_migrations' => ['some_migration', 'another_migration']],
        'expected_result' => 'menu_link_content:another_migration',
      ],
    ];
  }

}
