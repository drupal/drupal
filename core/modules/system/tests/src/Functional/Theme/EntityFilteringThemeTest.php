<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\comment\CommentInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\node\NodeInterface;
use Drupal\comment\Entity\Comment;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests XSS filtering for themed output for each entity type in all themes.
 *
 * @group Theme
 */
class EntityFilteringThemeTest extends BrowserTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'taxonomy', 'comment', 'node', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A list of all available themes.
   *
   * @var \Drupal\Core\Extension\Extension[]
   */
  protected $themes;

  /**
   * A test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;


  /**
   * A test node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;


  /**
   * A test taxonomy term.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $term;


  /**
   * A test comment.
   *
   * @var \Drupal\comment\Entity\Comment
   */
  protected $comment;

  /**
   * A string containing markup and JS.
   *
   * @var string
   */
  protected $xssLabel = "string with <em>HTML</em> and <script>alert('JS');</script>";

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install all available non-testing themes.
    $listing = new ExtensionDiscovery(\Drupal::root());
    $this->themes = $listing->scan('theme', FALSE);
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_data = \Drupal::service('theme_handler')->rebuildThemeData();
    foreach (array_keys($this->themes) as $theme) {
      // Skip obsolete and deprecated themes.
      $info = $theme_data[$theme]->info;
      if ($info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] === ExtensionLifecycle::OBSOLETE || $info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] === ExtensionLifecycle::DEPRECATED) {
        unset($this->themes[$theme]);
      }
    }
    \Drupal::service('theme_installer')->install(array_keys($this->themes));

    // Create a test user.
    $this->user = $this->drupalCreateUser([
      'access content',
      'access user profiles',
    ]);
    $this->user->name = $this->xssLabel;
    $this->user->save();
    $this->drupalLogin($this->user);

    // Create a test term.
    $this->term = Term::create([
      'name' => $this->xssLabel,
      'vid' => 1,
    ]);
    $this->term->save();

    $this->createContentType(['type' => 'article']);
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
        $this->assertSession()->statusCodeEquals(200);
        $this->assertSession()->responseNotContains($this->xssLabel);
      }
    }
  }

}
