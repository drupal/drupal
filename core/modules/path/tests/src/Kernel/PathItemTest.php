<?php

namespace Drupal\Tests\path\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests loading and storing data using PathItem.
 *
 * @group path
 */
class PathItemTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['path', 'path_alias', 'node', 'user', 'system', 'language', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('path_alias');

    $this->installSchema('node', ['node_access']);

    $node_type = NodeType::create(['type' => 'foo']);
    $node_type->save();

    $this->installConfig(['language']);
    ConfigurableLanguage::createFromLangcode('de')->save();
  }

  /**
   * Test creating, loading, updating and deleting aliases through PathItem.
   */
  public function testPathItem() {
    /** @var \Drupal\path_alias\AliasRepositoryInterface $alias_repository */
    $alias_repository = \Drupal::service('path_alias.repository');

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    $node = Node::create([
      'title' => 'Testing create()',
      'type' => 'foo',
      'path' => ['alias' => '/foo'],
    ]);
    $this->assertFalse($node->get('path')->isEmpty());
    $this->assertEquals('/foo', $node->get('path')->alias);

    $node->save();
    $this->assertFalse($node->get('path')->isEmpty());
    $this->assertEquals('/foo', $node->get('path')->alias);

    $stored_alias = $alias_repository->lookupBySystemPath('/' . $node->toUrl()->getInternalPath(), $node->language()->getId());
    $this->assertEquals('/foo', $stored_alias['alias']);

    $node_storage->resetCache();

    /** @var \Drupal\node\NodeInterface $loaded_node */
    $loaded_node = $node_storage->load($node->id());
    $this->assertFalse($loaded_node->get('path')->isEmpty());
    $this->assertEquals('/foo', $loaded_node->get('path')->alias);
    $node_storage->resetCache();
    $loaded_node = $node_storage->load($node->id());
    $this->assertEquals('/foo', $loaded_node->get('path')[0]->get('alias')->getValue());

    $node_storage->resetCache();
    $loaded_node = $node_storage->load($node->id());
    $values = $loaded_node->get('path')->getValue();
    $this->assertEquals('/foo', $values[0]['alias']);

    $node_storage->resetCache();
    $loaded_node = $node_storage->load($node->id());
    $this->assertEquals('/foo', $loaded_node->path->alias);

    // Add a translation, verify it is being saved as expected.
    $translation = $loaded_node->addTranslation('de', $loaded_node->toArray());
    $translation->get('path')->alias = '/furchtbar';
    $translation->save();

    // Assert the alias on the English node, the German translation, and the
    // stored aliases.
    $node_storage->resetCache();
    $loaded_node = $node_storage->load($node->id());
    $this->assertEquals('/foo', $loaded_node->path->alias);
    $translation = $loaded_node->getTranslation('de');
    $this->assertEquals('/furchtbar', $translation->path->alias);

    $stored_alias = $alias_repository->lookupBySystemPath('/' . $node->toUrl()->getInternalPath(), $node->language()->getId());
    $this->assertEquals('/foo', $stored_alias['alias']);
    $stored_alias = $alias_repository->lookupBySystemPath('/' . $node->toUrl()->getInternalPath(), $translation->language()->getId());
    $this->assertEquals('/furchtbar', $stored_alias['alias']);

    $loaded_node->get('path')->alias = '/bar';
    $this->assertFalse($loaded_node->get('path')->isEmpty());
    $this->assertEquals('/bar', $loaded_node->get('path')->alias);

    $loaded_node->save();
    $this->assertFalse($loaded_node->get('path')->isEmpty());
    $this->assertEquals('/bar', $loaded_node->get('path')->alias);

    $node_storage->resetCache();
    $loaded_node = $node_storage->load($node->id());
    $this->assertFalse($loaded_node->get('path')->isEmpty());
    $this->assertEquals('/bar', $loaded_node->get('path')->alias);

    $loaded_node->get('path')->alias = '/bar';
    $this->assertFalse($loaded_node->get('path')->isEmpty());
    $this->assertEquals('/bar', $loaded_node->get('path')->alias);

    $loaded_node->save();
    $this->assertFalse($loaded_node->get('path')->isEmpty());
    $this->assertEquals('/bar', $loaded_node->get('path')->alias);

    $stored_alias = $alias_repository->lookupBySystemPath('/' . $node->toUrl()->getInternalPath(), $node->language()->getId());
    $this->assertEquals('/bar', $stored_alias['alias']);

    $old_alias = $alias_repository->lookupByAlias('/foo', $node->language()->getId());
    $this->assertNull($old_alias);

    // Reload the node to make sure that it is possible to set a value
    // immediately after loading.
    $node_storage->resetCache();
    $loaded_node = $node_storage->load($node->id());
    $loaded_node->get('path')->alias = '/foobar';
    $loaded_node->save();

    $node_storage->resetCache();
    $loaded_node = $node_storage->load($node->id());
    $this->assertFalse($loaded_node->get('path')->isEmpty());
    $this->assertEquals('/foobar', $loaded_node->get('path')->alias);
    $stored_alias = $alias_repository->lookupBySystemPath('/' . $node->toUrl()->getInternalPath(), $node->language()->getId());
    $this->assertEquals('/foobar', $stored_alias['alias']);

    $old_alias = $alias_repository->lookupByAlias('/bar', $node->language()->getId());
    $this->assertNull($old_alias);

    $loaded_node->get('path')->alias = '';
    $this->assertEquals('', $loaded_node->get('path')->alias);

    $loaded_node->save();

    $stored_alias = $alias_repository->lookupBySystemPath('/' . $node->toUrl()->getInternalPath(), $node->language()->getId());
    $this->assertNull($stored_alias);

    // Check that reading, updating and reading the computed alias again in the
    // same request works without clearing any caches in between.
    $loaded_node = $node_storage->load($node->id());
    $loaded_node->get('path')->alias = '/foo';
    $loaded_node->save();

    $this->assertFalse($loaded_node->get('path')->isEmpty());
    $this->assertEquals('/foo', $loaded_node->get('path')->alias);
    $stored_alias = $alias_repository->lookupBySystemPath('/' . $node->toUrl()->getInternalPath(), $node->language()->getId());
    $this->assertEquals('/foo', $stored_alias['alias']);

    $loaded_node->get('path')->alias = '/foobar';
    $loaded_node->save();

    $this->assertFalse($loaded_node->get('path')->isEmpty());
    $this->assertEquals('/foobar', $loaded_node->get('path')->alias);
    $stored_alias = $alias_repository->lookupBySystemPath('/' . $node->toUrl()->getInternalPath(), $node->language()->getId());
    $this->assertEquals('/foobar', $stored_alias['alias']);

    // Check that \Drupal\Core\Field\FieldItemList::equals() for the path field
    // type.
    $node = Node::create([
      'title' => $this->randomString(),
      'type' => 'foo',
      'path' => ['alias' => '/foo'],
    ]);
    $second_node = Node::create([
      'title' => $this->randomString(),
      'type' => 'foo',
      'path' => ['alias' => '/foo'],
    ]);
    $this->assertTrue($node->get('path')->equals($second_node->get('path')));

    // Change the alias for the second node to a different one and try again.
    $second_node->get('path')->alias = '/foobar';
    $this->assertFalse($node->get('path')->equals($second_node->get('path')));

    // Test the generateSampleValue() method.
    $node = Node::create([
      'title' => $this->randomString(),
      'type' => 'foo',
      'path' => ['alias' => '/foo'],
    ]);
    $node->save();
    $path_field = $node->get('path');
    $path_field->generateSampleItems();
    $node->save();
    $this->assertStringStartsWith('/', $node->get('path')->alias);
  }

}
