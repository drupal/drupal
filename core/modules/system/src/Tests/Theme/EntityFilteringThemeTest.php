<?php

/**
 * @file
 * Contains Drupal\system\Tests\Theme\EntityFilteringThemeTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\comment\CommentInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests themed output for each entity type in all available themes to ensure
 * entity labels are filtered for XSS.
 *
 * @group Theme
 */
class EntityFilteringThemeTest extends WebTestBase {

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
   * @string
   */
  protected $xss_label = "string with <em>HTML</em> and <script>alert('JS');</script>";

  protected function setUp() {
    parent::setUp();

    // Install all available non-testing themes.
    $listing = new ExtensionDiscovery(\Drupal::root());
    $this->themes = $listing->scan('theme', FALSE);
    \Drupal::service('theme_handler')->install(array_keys($this->themes));

    // Create a test user.
    $this->user = $this->drupalCreateUser(array('access content', 'access user profiles'));
    $this->user->name = $this->xss_label;
    $this->user->save();
    $this->drupalLogin($this->user);

    // Create a test term.
    $this->term = entity_create('taxonomy_term', array(
      'name' => $this->xss_label,
      'vid' => 1,
    ));
    $this->term->save();

    // Add a comment field.
    $this->addDefaultCommentField('node', 'article', 'comment', CommentItemInterface::OPEN);
    // Create a test node tagged with the test term.
    $this->node = $this->drupalCreateNode(array(
      'title' => $this->xss_label,
      'type' => 'article',
      'promote' => NODE_PROMOTED,
      'field_tags' => array(array('target_id' => $this->term->id())),
    ));

    // Create a test comment on the test node.
    $this->comment = entity_create('comment', array(
      'entity_id' => $this->node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'status' => CommentInterface::PUBLISHED,
      'subject' => $this->xss_label,
      'comment_body' => array($this->randomMachineName()),
    ));
    $this->comment->save();
  }

  /**
   * Checks each themed entity for XSS filtering in available themes.
   */
  function testThemedEntity() {
    // Check paths where various view modes of the entities are rendered.
    $paths = array(
      'user',
      'node',
      'node/' . $this->node->id(),
      'taxonomy/term/' . $this->term->id(),
    );

    // Check each path in all available themes.
    foreach ($this->themes as $name => $theme) {
      $this->config('system.theme')
        ->set('default', $name)
        ->save();
      foreach ($paths as $path) {
        $this->drupalGet($path);
        $this->assertResponse(200);
        $this->assertNoRaw($this->xss_label);
      }
    }
  }

}
