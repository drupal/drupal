<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\comment\CommentInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\node\NodeInterface;
use Drupal\comment\Entity\Comment;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests themed output for each entity type in all available themes to ensure
 * entity labels are filtered for XSS.
 *
 * @group Theme
 */
class EntityFilteringThemeTest extends BrowserTestBase {

  use CommentTestTrait;

  /**
   * Use the standard profile.
   *
   * We test entity theming with the default node, user, comment, and taxonomy
   * configurations at several paths in the standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * A list of all available themes.
   *
   * @var \Drupal\Core\Extension\Extension[]
   */
  protected $themes;

  /**
   * A test user.
   *
   * @var \Drupal\user\User
   */
  protected $user;


  /**
   * A test node.
   *
   * @var \Drupal\node\Node
   */
  protected $node;


  /**
   * A test taxonomy term.
   *
   * @var \Drupal\taxonomy\Term
   */
  protected $term;


  /**
   * A test comment.
   *
   * @var \Drupal\comment\Comment
   */
  protected $comment;

  /**
   * A string containing markup and JS.
   *
   * @var string
   */
  protected $xssLabel = "string with <em>HTML</em> and <script>alert('JS');</script>";

  protected function setUp() {
    parent::setUp();

    // Install all available non-testing themes.
    $listing = new ExtensionDiscovery(\Drupal::root());
    $this->themes = $listing->scan('theme', FALSE);
    \Drupal::service('theme_installer')->install(array_keys($this->themes));

    // Create a test user.
    $this->user = $this->drupalCreateUser(['access content', 'access user profiles']);
    $this->user->name = $this->xssLabel;
    $this->user->save();
    $this->drupalLogin($this->user);

    // Create a test term.
    $this->term = Term::create([
      'name' => $this->xssLabel,
      'vid' => 1,
    ]);
    $this->term->save();

    // Add a comment field.
    $this->addDefaultCommentField('node', 'article', 'comment', CommentItemInterface::OPEN);
    // Create a test node tagged with the test term.
    $this->node = $this->drupalCreateNode([
      'title' => $this->xssLabel,
      'type' => 'article',
      'promote' => NodeInterface::PROMOTED,
      'field_tags' => [['target_id' => $this->term->id()]],
    ]);

    // Create a test comment on the test node.
    $this->comment = Comment::create([
      'entity_id' => $this->node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'status' => CommentInterface::PUBLISHED,
      'subject' => $this->xssLabel,
      'comment_body' => [$this->randomMachineName()],
    ]);
    $this->comment->save();
  }

  /**
   * Checks each themed entity for XSS filtering in available themes.
   */
  public function testThemedEntity() {
    // Check paths where various view modes of the entities are rendered.
    $paths = [
      'user',
      'node',
      'node/' . $this->node->id(),
      'taxonomy/term/' . $this->term->id(),
    ];

    // Check each path in all available themes.
    foreach ($this->themes as $name => $theme) {
      $this->config('system.theme')
        ->set('default', $name)
        ->save();
      foreach ($paths as $path) {
        $this->drupalGet($path);
        $this->assertResponse(200);
        $this->assertNoRaw($this->xssLabel);
      }
    }
  }

}
