<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\layout_builder\DefaultsSectionStorageInterface;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * @coversDefaultClass \Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage
 *
 * @group layout_builder
 * @group #slow
 */
class OverridesSectionStorageTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_discovery',
    'layout_builder',
    'entity_test',
    'field',
    'system',
    'user',
    'language',
  ];

  /**
   * The plugin.
   *
   * @var \Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpCurrentUser();
    $this->installEntitySchema('entity_test');

    $definition = $this->container->get('plugin.manager.layout_builder.section_storage')->getDefinition('overrides');
    $this->plugin = OverridesSectionStorage::create($this->container, [], 'overrides', $definition);
  }

  /**
   * @covers ::access
   * @dataProvider providerTestAccess
   *
   * @param bool $expected
   *   The expected outcome of ::access().
   * @param bool $is_enabled
   *   Whether Layout Builder is enabled for this display.
   * @param array $section_data
   *   Data to store as the sections value for Layout Builder.
   * @param string[] $permissions
   *   An array of permissions to grant to the user.
   */
  public function testAccess($expected, $is_enabled, array $section_data, array $permissions) {
    $display = LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    if ($is_enabled) {
      $display->enableLayoutBuilder();
    }
    $display
      ->setOverridable()
      ->save();

    $entity = EntityTest::create([OverridesSectionStorage::FIELD_NAME => $section_data]);
    $entity->save();

    $account = $this->setUpCurrentUser([], $permissions);

    $this->plugin->setContext('entity', EntityContext::fromEntity($entity));
    $this->plugin->setContext('view_mode', new Context(new ContextDefinition('string'), 'default'));

    // Check access with both the global current user as well as passing one in.
    $result = $this->plugin->access('view');
    $this->assertSame($expected, $result);
    $result = $this->plugin->access('view', $account);
    $this->assertSame($expected, $result);

    // Create a translation.
    ConfigurableLanguage::createFromLangcode('es')->save();
    $entity = EntityTest::load($entity->id());
    $translation = $entity->addTranslation('es');
    $translation->save();
    $this->plugin->setContext('entity', EntityContext::fromEntity($translation));

    // Perform the same checks again but with a non default translation which
    // should always deny access.
    $result = $this->plugin->access('view');
    $this->assertFalse($result);
    $result = $this->plugin->access('view', $account);
    $this->assertFalse($result);
  }

  /**
   * Provides test data for ::testAccess().
   */
  public function providerTestAccess() {
    $section_data = [
      new Section('layout_onecol', [], [
        '10000000-0000-1000-a000-000000000000' => new SectionComponent('10000000-0000-1000-a000-000000000000', 'content', ['id' => 'foo']),
      ]),
    ];

    // Data provider values are:
    // - the expected outcome of the call to ::access()
    // - whether Layout Builder has been enabled for this display
    // - any section data
    // - any permissions to grant to the user.
    $data = [];
    $data['disabled, no data, no permissions'] = [
      FALSE, FALSE, [], [],
    ];
    $data['disabled, data, no permissions'] = [
      FALSE, FALSE, $section_data, [],
    ];
    $data['enabled, no data, no permissions'] = [
      FALSE, TRUE, [], [],
    ];
    $data['enabled, data, no permissions'] = [
      FALSE, TRUE, $section_data, [],
    ];
    $data['enabled, no data, configure any layout'] = [
      TRUE, TRUE, [], ['configure any layout'],
    ];
    $data['enabled, data, configure any layout'] = [
      TRUE, TRUE, $section_data, ['configure any layout'],
    ];
    $data['enabled, no data, bundle overrides'] = [
      TRUE, TRUE, [], ['configure all entity_test entity_test layout overrides'],
    ];
    $data['enabled, data, bundle overrides'] = [
      TRUE, TRUE, $section_data, ['configure all entity_test entity_test layout overrides'],
    ];
    $data['enabled, no data, bundle edit overrides, no edit access'] = [
      FALSE, TRUE, [], ['configure editable entity_test entity_test layout overrides'],
    ];
    $data['enabled, data, bundle edit overrides, no edit access'] = [
      FALSE, TRUE, $section_data, ['configure editable entity_test entity_test layout overrides'],
    ];
    $data['enabled, no data, bundle edit overrides, edit access'] = [
      TRUE, TRUE, [], ['configure editable entity_test entity_test layout overrides', 'administer entity_test content'],
    ];
    $data['enabled, data, bundle edit overrides, edit access'] = [
      TRUE, TRUE, $section_data, ['configure editable entity_test entity_test layout overrides', 'administer entity_test content'],
    ];
    return $data;
  }

  /**
   * @covers ::getContexts
   */
  public function testGetContexts() {
    $entity = EntityTest::create();
    $entity->save();

    $context = EntityContext::fromEntity($entity);
    $this->plugin->setContext('entity', $context);

    $expected = [
      'entity',
      'view_mode',
    ];
    $result = $this->plugin->getContexts();
    $this->assertEquals($expected, array_keys($result));
    $this->assertSame($context, $result['entity']);
  }

  /**
   * @covers ::getContextsDuringPreview
   */
  public function testGetContextsDuringPreview() {
    $entity = EntityTest::create();
    $entity->save();

    $context = EntityContext::fromEntity($entity);
    $this->plugin->setContext('entity', $context);

    $expected = [
      'view_mode',
      'layout_builder.entity',
    ];
    $result = $this->plugin->getContextsDuringPreview();
    $this->assertEquals($expected, array_keys($result));
    $this->assertSame($context, $result['layout_builder.entity']);
  }

  /**
   * @covers ::getDefaultSectionStorage
   */
  public function testGetDefaultSectionStorage() {
    $entity = EntityTest::create();
    $entity->save();
    $this->plugin->setContext('entity', EntityContext::fromEntity($entity));
    $this->plugin->setContext('view_mode', new Context(ContextDefinition::create('string'), 'default'));
    $this->assertInstanceOf(DefaultsSectionStorageInterface::class, $this->plugin->getDefaultSectionStorage());
  }

  /**
   * @covers ::getTempstoreKey
   */
  public function testGetTempstoreKey() {
    $entity = EntityTest::create();
    $entity->save();
    $this->plugin->setContext('entity', EntityContext::fromEntity($entity));
    $this->plugin->setContext('view_mode', new Context(new ContextDefinition('string'), 'default'));

    $result = $this->plugin->getTempstoreKey();
    $this->assertSame('entity_test.1.default.en', $result);
  }

  /**
   * @covers ::deriveContextsFromRoute
   */
  public function testDeriveContextsFromRoute() {
    $display = LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $entity = EntityTest::create();
    $entity->save();
    $entity = EntityTest::load($entity->id());

    $result = $this->plugin->deriveContextsFromRoute('entity_test.1', [], '', []);
    $this->assertSame(['entity', 'view_mode'], array_keys($result));
    $this->assertSame($entity, $result['entity']->getContextValue());
    $this->assertSame('default', $result['view_mode']->getContextValue());
  }

  /**
   * @covers ::isOverridden
   */
  public function testIsOverridden() {
    $display = LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $entity = EntityTest::create();
    $entity->set(OverridesSectionStorage::FIELD_NAME, [new Section('layout_onecol')]);
    $entity->save();
    $entity = EntityTest::load($entity->id());

    $context = EntityContext::fromEntity($entity);
    $this->plugin->setContext('entity', $context);

    $this->assertTrue($this->plugin->isOverridden());
    $this->plugin->removeSection(0);
    $this->assertTrue($this->plugin->isOverridden());
    $this->plugin->removeAllSections(TRUE);
    $this->assertTrue($this->plugin->isOverridden());
    $this->plugin->removeAllSections();
    $this->assertFalse($this->plugin->isOverridden());
  }

}
