<?php

namespace Drupal\Tests\node\Functional;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Utility\Html;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;

/**
 * Tests node title.
 *
 * @group node
 */
class NodeTitleTest extends NodeTestBase {

  use CommentTestTrait;
  use AssertBreadcrumbTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['comment', 'views', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->adminUser = $this->drupalCreateUser([
      'administer nodes',
      'create article content',
      'create page content',
      'post comments',
    ]);
    $this->drupalLogin($this->adminUser);
    $this->addDefaultCommentField('node', 'page');
  }

  /**
   * Creates one node and tests if the node title has the correct value.
   */
  public function testNodeTitle() {
    // Create "Basic page" content with title.
    // Add the node to the frontpage so we can test if teaser links are
    // clickable.
    $settings = [
      'title' => $this->randomMachineName(8),
      'promote' => 1,
    ];
    $node = $this->drupalCreateNode($settings);

    // Test <title> tag.
    $this->drupalGet('node/' . $node->id());
    $xpath = '//title';
    $this->assertEquals($this->xpath($xpath)[0]->getText(), $node->label() . ' | Drupal', 'Page title is equal to node title.');

    // Test breadcrumb in comment preview.
    $this->assertBreadcrumb('comment/reply/node/' . $node->id() . '/comment', [
      '' => 'Home',
      'node/' . $node->id() => $node->label(),
    ]);

    // Verify that node preview title is equal to node title.
    $this->assertSession()->elementTextEquals('xpath', "//article/h2/a/span", $node->label());

    // Test node title is clickable on teaser list (/node).
    $this->drupalGet('node');
    $this->clickLink($node->label());

    // Test edge case where node title is set to 0.
    $settings = [
      'title' => 0,
    ];
    $node = $this->drupalCreateNode($settings);
    // Test that 0 appears as <title>.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->titleEquals('0 | Drupal');
    // Test that 0 appears in the template <h1>.
    $xpath = '//h1';
    $this->assertSame('0', $this->xpath($xpath)[0]->getText(), 'Node title is displayed as 0.');

    // Test edge case where node title contains special characters.
    $edge_case_title = 'article\'s "title".';
    $settings = [
      'title' => $edge_case_title,
    ];
    $node = $this->drupalCreateNode($settings);
    // Test that the title appears as <title>. The title will be escaped on the
    // the page.
    $edge_case_title_escaped = Html::escape($edge_case_title);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('<title>' . $edge_case_title_escaped . ' | Drupal</title>');

    // Test that the title appears as <title> when reloading the node page.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('<title>' . $edge_case_title_escaped . ' | Drupal</title>');

  }

}
