<?php

namespace Drupal\Tests\node\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;

/**
 * Create a node with revisions and test contextual links.
 *
 * @group node
 */
class ContextualLinksTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;

  /**
   * An array of node revisions.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;


  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'contextual'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);

    // Create initial node.
    $node = $this->drupalCreateNode();

    $nodes = [];

    // Get original node.
    $nodes[] = clone $node;

    // Create two revisions.
    $revision_count = 2;
    for ($i = 0; $i < $revision_count; $i++) {

      // Create revision with a random title and body and update variables.
      $node->title = $this->randomMachineName();
      $node->body = [
        'value' => $this->randomMachineName(32),
        'format' => filter_default_format(),
      ];
      $node->setNewRevision();

      $node->save();

      // Make sure we get revision information.
      $node = Node::load($node->id());
      $nodes[] = clone $node;
    }

    $this->nodes = $nodes;

    $this->drupalLogin($this->createUser(
      [
        'view page revisions',
        'revert page revisions',
        'delete page revisions',
        'edit any page content',
        'delete any page content',
        'access contextual links',
        'administer content types',
      ]
    ));
  }

  /**
   * Tests the contextual links on revisions.
   */
  public function testRevisionContextualLinks() {
    // Confirm that the "Edit" and "Delete" contextual links appear for the
    // default revision.
    $this->drupalGet('node/' . $this->nodes[0]->id());
    $page = $this->getSession()->getPage();
    $page->waitFor(10, function () use ($page) {
      return $page->find('css', "main .contextual");
    });

    $this->toggleContextualTriggerVisibility('main');
    $page->find('css', 'main .contextual button')->press();
    $links = $page->findAll('css', "main .contextual-links li a");

    $this->assertEquals('Edit', $links[0]->getText());
    $this->assertEquals('Delete', $links[1]->getText());

    // Confirm that "Edit" and "Delete" contextual links don't appear for
    // non-default revision.
    $this->drupalGet("node/" . $this->nodes[0]->id() . "/revisions/" . $this->nodes[1]->getRevisionId() . "/view");
    $this->assertSession()->pageTextContains($this->nodes[1]->getTitle());
    $page->waitFor(10, function () use ($page) {
      return $page->find('css', "main .contextual");
    });

    $this->toggleContextualTriggerVisibility('main');
    $contextual_button = $page->find('css', 'main .contextual button');
    $this->assertEmpty(0, $contextual_button ?: '');
  }

}
