<?php

namespace Drupal\Tests\forum\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests forum validation constraints.
 *
 * @group forum
 */
class ForumValidationTest extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'options',
    'comment',
    'taxonomy',
    'forum',
  ];

  /**
   * Tests the forum validation constraints.
   */
  public function testValidation() {
    // Add a forum.
    $forum = Term::create([
      'name' => 'forum 1',
      'vid' => 'forums',
      'forum_container' => 0,
    ]);

    // Add a container.
    $container = Term::create([
      'name' => 'container 1',
      'vid' => 'forums',
      'forum_container' => 1,
    ]);

    // Add a forum post.
    $forum_post = Node::create([
      'type' => 'forum',
      'title' => 'Do these pants make my butt look big?',
    ]);

    $violations = $forum_post->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('This value should not be null.', $violations[0]->getMessage());

    // Add the forum term.
    $forum_post->set('taxonomy_forums', $forum);
    $violations = $forum_post->validate();
    $this->assertCount(0, $violations);

    // Try to use a container.
    $forum_post->set('taxonomy_forums', $container);
    $violations = $forum_post->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals(t('The item %forum is a forum container, not a forum. Select one of the forums below instead.', ['%forum' => $container->label()]), $violations[0]->getMessage());
  }

}
