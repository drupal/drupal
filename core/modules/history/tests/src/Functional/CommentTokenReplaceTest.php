<?php

declare(strict_types=1);

namespace Drupal\Tests\history\Functional;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\comment\Functional\CommentTestBase;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests comment token replacement.
 */
#[Group('history')]
#[RunTestsInSeparateProcesses]
class CommentTokenReplaceTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'history'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Creates a comment, then tests the tokens generated from it.
   */
  public function testCommentTokenReplacement(): void {
    $token_service = \Drupal::token();
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    // Setup vocabulary.
    Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
    ])->save();

    $this->drupalLogin($this->adminUser);

    // Create a node and a comment.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $parent_comment = $this->postComment($node, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // Post a reply to the comment.
    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment/' . $parent_comment->id());
    $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName());

    // Add comment field to user and term entities.
    $this->addDefaultCommentField('user', 'user', 'comment', CommentItemInterface::OPEN, 'comment_user');
    $this->addDefaultCommentField('taxonomy_term', 'tags', 'comment', CommentItemInterface::OPEN, 'comment_term');

    // Create a user and a comment.
    $user = User::create(['name' => 'alice']);
    $user->activate();
    $user->save();
    $this->postComment($user, 'user body', 'user subject', TRUE);

    // Create a term and a comment.
    $term = Term::create([
      'vid' => 'tags',
      'name' => 'term',
    ]);
    $term->save();
    $this->postComment($term, 'term body', 'term subject', TRUE);

    // Load node, user and term again so comment_count gets computed.
    $node = Node::load($node->id());
    $user = User::load($user->id());
    $term = Term::load($term->id());

    // Generate comment tokens for node (it has 2 comments, both new),
    // user and term.
    $tests = [];
    $tests['[entity:comment-count-new]'] = 2;
    $tests['[node:comment-count-new]'] = 2;
    $tests['[user:comment-count-new]'] = 1;
    $tests['[term:comment-count-new]'] = 1;

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, ['entity' => $node, 'node' => $node, 'user' => $user, 'term' => $term], ['langcode' => $language_interface->getId()]);
      $this->assertSame((string) $expected, (string) $output, "Failed test case: {$input}");
    }
  }

}
