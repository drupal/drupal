<?php

namespace Drupal\Tests\forum\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Legacy forum code.
 *
 * @group forum
 * @group legacy
 */
class LegacyForumTest extends KernelTestBase {

  protected static $modules = [
    'comment',
    'forum',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorage
   */
  protected $termStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig('forum');
    $this->termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  }

  /**
   * Tests the getParents() method.
   *
   * @expectedDeprecation Drupal\forum\ForumManager::getParents() is deprecated in drupal:8.1.0 and is removed from drupal:9.0.0. Call loadAllParents() on taxonomy term storage directly. See https://www.drupal.org/node/3069599
   */
  public function testGetParents() {
    // Add a forum.
    $forum = $this->termStorage->create([
      'name' => 'Forum',
      'vid' => 'forums',
      'forum_container' => 1,
    ]);
    $forum->save();

    // Add a container.
    $subforum = $this->termStorage->create([
      'name' => 'Subforum',
      'vid' => 'forums',
      'forum_container' => 0,
      'parent' => $forum->id(),
    ]);
    $subforum->save();

    $legacy_parents = \Drupal::service('forum_manager')->getParents($subforum->id());
    $parents = $this->termStorage->loadAllParents($subforum->id());
    $this->assertSame($parents, $legacy_parents);
  }

}
