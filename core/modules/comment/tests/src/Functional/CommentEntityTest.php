<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\Entity\CommentType;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests comments with other entities.
 *
 * @group comment
 */
class CommentEntityTest extends CommentTestBase {

  use TaxonomyTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'comment',
    'node',
    'history',
    'field_ui',
    'datetime',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected $vocab;
  protected $commentType;

  protected function setUp() {
    parent::setUp();

    $this->vocab = $this->createVocabulary();
    $this->commentType = CommentType::create([
      'id' => 'taxonomy_comment',
      'label' => 'Taxonomy comment',
      'description' => '',
      'target_entity_type_id' => 'taxonomy_term',
    ]);
    $this->commentType->save();
    $this->addDefaultCommentField(
      'taxonomy_term',
      $this->vocab->id(),
      'field_comment',
      CommentItemInterface::OPEN,
      $this->commentType->id()
    );
  }

  /**
   * Tests CSS classes on comments.
   */
  public function testEntityChanges() {
    $this->drupalLogin($this->webUser);
    // Create a new node.
    $term = $this->createTerm($this->vocab, ['uid' => $this->webUser->id()]);

    // Add a comment.
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = Comment::create([
      'entity_id' => $term->id(),
      'entity_type' => 'taxonomy_term',
      'field_name' => 'field_comment',
      'uid' => $this->webUser->id(),
      'status' => CommentInterface::PUBLISHED,
      'subject' => $this->randomMachineName(),
      'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'comment_body' => [LanguageInterface::LANGCODE_NOT_SPECIFIED => [$this->randomMachineName()]],
    ]);
    $comment->save();

    // Request the node with the comment.
    $this->drupalGet('taxonomy/term/' . $term->id());
    $settings = $this->getDrupalSettings();
    $this->assertFalse(isset($settings['ajaxPageState']['libraries']) && in_array('comment/drupal.comment-new-indicator', explode(',', $settings['ajaxPageState']['libraries'])), 'drupal.comment-new-indicator library is present.');
    $this->assertFalse(isset($settings['history']['lastReadTimestamps']) && in_array($term->id(), array_keys($settings['history']['lastReadTimestamps'])), 'history.lastReadTimestamps is present.');
  }

}
