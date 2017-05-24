<?php

namespace Drupal\Tests\hal\Kernel;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Drupal\node\Entity\NodeType;

/**
 * Tests that translated nodes are correctly (de-)normalized.
 *
 * @group hal
 */
class EntityTranslationNormalizeTest extends NormalizerTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['node', 'content_translation']);
  }

  /**
   * Tests the normalization of node translations.
   */
  public function testNodeTranslation() {
    $node_type = NodeType::create(['type' => 'example_type']);
    $node_type->save();
    $this->container->get('content_translation.manager')->setEnabled('node', 'example_type', TRUE);

    $user = User::create(['name' => $this->randomMachineName()]);
    $user->save();

    $node = Node::create([
      'title' => $this->randomMachineName(),
      'uid' => (int) $user->id(),
      'type' => $node_type->id(),
      'status' => NodeInterface::PUBLISHED,
      'langcode' => 'en',
      'promote' => 1,
      'sticky' => 0,
      'body' => [
        'value' => $this->randomMachineName(),
        'format' => $this->randomMachineName()
      ],
      'revision_log' => $this->randomString(),
    ]);
    $node->addTranslation('de', [
      'title' => 'German title',
      'body' => [
        'value' => $this->randomMachineName(),
        'format' => $this->randomMachineName()
      ],
    ]);
    $node->save();

    $original_values = $node->toArray();
    $translation = $node->getTranslation('de');
    $original_translation_values = $node->getTranslation('en')->toArray();

    $normalized = $this->serializer->normalize($node, $this->format);

    $this->assertContains(['lang' => 'en', 'value' => $node->getTitle()], $normalized['title'], 'Original language title has been normalized.');
    $this->assertContains(['lang' => 'de', 'value' => $translation->getTitle()], $normalized['title'], 'Translation language title has been normalized.');

    /** @var \Drupal\node\NodeInterface $denormalized_node */
    $denormalized_node = $this->serializer->denormalize($normalized, 'Drupal\node\Entity\Node', $this->format);

    $this->assertSame($denormalized_node->language()->getId(), $denormalized_node->getUntranslated()->language()->getId(), 'Untranslated object is returned from serializer.');
    $this->assertSame('en', $denormalized_node->language()->getId());
    $this->assertTrue($denormalized_node->hasTranslation('de'));

    $this->assertSame($node->getTitle(), $denormalized_node->getTitle());
    $this->assertSame($translation->getTitle(), $denormalized_node->getTranslation('de')->getTitle());

    $this->assertEquals($original_values, $denormalized_node->toArray(), 'Node values are restored after normalizing and denormalizing.');
    $this->assertEquals($original_translation_values, $denormalized_node->getTranslation('en')->toArray(), 'Node values are restored after normalizing and denormalizing.');
  }

}
