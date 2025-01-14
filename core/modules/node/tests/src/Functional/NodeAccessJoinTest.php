<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeAccessTrait;
use Drupal\user\UserInterface;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests Node Access on join.
 *
 * @group views
 */
class NodeAccessJoinTest extends NodeTestBase {

  use NodeAccessTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node_access_test', 'node_test_views', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The user that will create the articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $authorUser;

  /**
   * Another user that will create articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $otherUser;

  /**
   * A user with just access content permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $regularUser;

  /**
   * A user with access to private articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $accessUser;

  /**
   * Articles.
   *
   * @var array
   */
  protected array $articles;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static array $testViews = ['test_node_access_join'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->addPrivateField(NodeType::load('article'));

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'related_article',
      'entity_type' => 'node',
      'translatable' => FALSE,
      'entity_types' => [],
      'settings' => [
        'target_type' => 'node',
      ],
      'type' => 'entity_reference',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_name' => 'related_article',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Related Article',
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          // Reference a single vocabulary.
          'target_bundles' => [
            'article',
          ],
        ],
      ],
    ]);
    $field->save();

    $entity_display = \Drupal::service('entity_display.repository');
    $entity_display->getViewDisplay('node', 'page', 'default')
      ->setComponent('related_article')
      ->save();
    $entity_display->getFormDisplay('node', 'page', 'default')
      ->setComponent('related_article', [
        'type' => 'entity_reference_autocomplete',
      ])
      ->save();

    $field = FieldConfig::create([
      'field_name' => 'related_article',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Related Article',
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          // Reference a single vocabulary.
          'target_bundles' => [
            'article',
          ],
        ],
      ],
    ]);
    $field->save();

    $entity_display->getViewDisplay('node', 'article', 'default')
      ->setComponent('related_article')
      ->save();
    $entity_display->getFormDisplay('node', 'article', 'default')
      ->setComponent('related_article', [
        'type' => 'entity_reference_autocomplete',
      ])
      ->save();

    node_access_rebuild();
    \Drupal::state()->set('node_access_test.private', TRUE);
  }

  /**
   * Tests the accessibility of joined nodes.
   *
   * - Create two users with "access content" and "create article" permissions
   *   who can each access their own private articles but not others'.
   * - Create article-type nodes with and without references to other articles.
   *   The articles and references represent all possible combinations of the
   *   tested access types.
   * - Create page-type nodes referencing each of the articles, as well as a
   *   page with no reference.
   * - Use a custom view that creates two joins between nodes and has a
   *   node_access tag. The view lists the page nodes, the article
   *   referenced by each page node, and the article referenced by each
   *   article.
   *
   * - Login with the author user and check that user does not have access to
   *   private nodes created by other users. Test access using total row
   *   count as well as checking for presence of individual page titles.
   * - Repeat tests using a user with only the "access content" permission,
   *   confirming this user does not have access to any private nodes.
   * - Repeat tests using a user with "access content" and "node test view"
   *   permissions, confirming this user sees the complete view.
   */
  public function testNodeAccessJoin(): void {

    $permissions = ['access content', 'create article content'];
    // User to add articles and test author access.
    $this->authorUser = $this->drupalCreateUser($permissions);
    // Another user to add articles whose private articles can not be accessed
    // by authorUser.
    $this->otherUser = $this->drupalCreateUser($permissions);

    // Create the articles. The articles are stored in an array keyed by
    // $article and $reference2, where $article is the access type of the
    // article itself, and $reference2 is the access type of the reference
    // linked to by the article. 'public' articles are created by otherUser with
    // private=0. 'private' articles are created by otherUser with private=1.
    // 'author_public' articles are created by authorUser with private=0.
    // 'author_private' articles are created by authorUser with private=1.
    // 'no_reference' is used for references when there is no related article.
    $access_type = ['public', 'private', 'author_public', 'author_private'];
    $reference_access_type = array_merge(['no_reference'], $access_type);

    foreach ($reference_access_type as $reference2) {
      foreach ($access_type as $article) {
        $is_author = (str_starts_with($article, 'author'));
        $is_private = (str_ends_with($article, 'private'));
        $edit = [
          'type' => 'article',
          'uid' => $is_author ? $this->authorUser->id() : $this->otherUser->id(),
        ];
        $edit['private'][0]['value'] = $is_private;
        // The article names provide the access status of the article and the
        // access status of the related article, if any. The naming system
        // ensures that the text 'Article $article' will only appear in the view
        // if an article with that access type is displayed in the view. The
        // text '$article' alone will appear in the titles of other nodes that
        // reference an article.
        $edit['title'] = "Article $article - $reference2";
        if ($reference2 !== 'no_reference') {
          $edit['related_article'][0]['target_id'] = $this->articles[$reference2]['no_reference'];
        }
        $node = $this->drupalCreateNode($edit);
        $this->articles[$article][$reference2] = $node->id();

        $this->assertEquals((int) $is_private, (int) $node->private->value, 'The private status of the article node was properly set in the node_access_test table.' . $node->uid->target_id);
        if ($reference2 !== 'no_reference') {
          $this->assertEquals((int) $this->articles[$reference2]['no_reference'], (int) $node->related_article->target_id, 'Proper article attached to article.');
        }
      }
    }

    // Add a blank 'no_reference' entry to the article list, so that a page with
    // no reference gets created.
    $this->articles['no_reference']['no_reference'] = NULL;

    $total = 0;
    $count_s_total = $count_s2_total = 0;
    $count_s_public = $count_s2_public = 0;
    $count_s_author = $count_s2_author = 0;
    $total_public = $total_author = 0;

    // Create page nodes referencing each article, as a page without reference.
    foreach ($this->articles as $reference => $list) {
      foreach ($list as $reference2 => $article_nid) {
        $title = "Page - $reference";
        if ($reference !== 'no_reference') {
          $title .= " - $reference2";
        }
        $edit = [
          'type' => 'page',
          'title' => $title,
        ];
        if ($article_nid) {
          $edit['related_article'][0]['target_id'] = $article_nid;
        }
        $node = $this->drupalCreateNode($edit);
        if ($article_nid) {
          $this->assertEquals((int) $article_nid, (int) $node->related_article->target_id, 'Proper article attached to page.');
        }

        // Calculate totals expected for each user type.
        $total++;
        // Total number of primary and secondary references.
        if ($reference !== 'no_reference') {
          $count_s_total++;
          if ($reference2 !== 'no_reference') {
            $count_s2_total++;
          }
        }
        // Public users only see 'public' and 'author_public' articles.
        if (str_ends_with($reference, 'public')) {
          $count_s_public++;
          if (str_ends_with($reference2, 'public')) {
            $count_s2_public++;
          }
        }
        // authorUser sees 'public','author_public', 'author_private' articles.
        if (str_ends_with($reference, 'public') || str_starts_with($reference, 'author')) {
          $count_s_author++;
          if (str_ends_with($reference2, 'public') || str_starts_with($reference2, 'author')) {
            $count_s2_author++;
          }
        }

        // $total_public and $total_author are not currently in use -- but
        // represent the totals when joins are handled by adding an is-null
        // check (i.e., if inaccessible references caused the entire row to be
        // hidden from view, instead of hiding just one cell of the table).
        // Count of pages where all related articles are accessible by
        // public users.
        if (!str_ends_with($reference, 'private') && !str_ends_with($reference2, 'private')) {
          $total_public++;
        }
        // Count of pages where all related articles are accessible by
        // authorUser.
        if ($reference !== 'private' && $reference2 !== 'private') {
          $total_author++;
        }
      }
    }

    // Generate a view listing all the pages, and check the view's content for
    // users with three different access levels.
    ViewTestData::createTestViews(get_class($this), ['node_test_views']);

    // Check the author of the 'author' articles.
    $this->drupalLogin($this->authorUser);
    $this->drupalGet('test-node-access-join');
    $chk_total = count($this->xpath("//td[@headers='view-title-table-column']"));
    $this->assertEquals($chk_total, $total, 'Author should see ' . $total . ' rows. Actual: ' . $chk_total);
    $chk_total = count($this->xpath("//td[@headers='view-title-1-table-column']/a"));
    $this->assertEquals($chk_total, $count_s_author, 'Author should see ' . $count_s_author . ' primary references. Actual: ' . $chk_total);
    $chk_total = count($this->xpath("//td[@headers='view-title-2-table-column']/a"));
    $this->assertEquals($chk_total, $count_s2_author, 'Author should see ' . $count_s2_author . ' secondary references. Actual: ' . $chk_total);

    $session = $this->assertSession();
    $session->pageTextContains('Page - no_reference');
    $session->pageTextContains('Page - public - no_reference');
    $session->pageTextContains('Page - public - public');
    $session->pageTextContains('Page - author_private - no_reference');
    $session->pageTextContains('Article public');
    $session->pageTextNotContains('Article private');
    $session->pageTextContains('Article author_public');
    $session->pageTextContains('Article author_private');

    // Check a regular user who did not author any articles.
    $this->regularUser = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($this->regularUser);
    $this->drupalGet('test-node-access-join');
    $chk_total = count($this->xpath("//td[@headers='view-title-table-column']"));
    $this->assertEquals($chk_total, $total, 'Public user should see ' . $total . ' rows. Actual: ' . $chk_total);
    $chk_total = count($this->xpath("//td[@headers='view-title-1-table-column']/a"));
    $this->assertEquals($chk_total, $count_s_public, 'Public user should see ' . $count_s_public . ' primary references. Actual: ' . $chk_total);
    $chk_total = count($this->xpath("//td[@headers='view-title-2-table-column']/a"));
    $this->assertEquals($chk_total, $count_s2_public, 'Public user should see ' . $count_s2_public . ' secondary references. Actual: ' . $chk_total);
    $session->pageTextContains('Page - no_reference');
    $session->pageTextContains('Page - public - no_reference');
    $session->pageTextContains('Page - public - public');
    $session->pageTextContains('Article public');
    $session->pageTextNotContains('Article private');
    $session->pageTextContains('Article author_public');
    $session->pageTextNotContains('Article author_private');

    // Check that a user with 'node test view' permission, can view all pages
    // and articles.
    $this->accessUser = $this->drupalCreateUser([
      'access content',
      'node test view',
    ]);
    $this->drupalLogin($this->accessUser);
    $this->drupalGet('test-node-access-join');
    $chk_total = count($this->xpath("//td[@headers='view-title-table-column']"));
    $this->assertEquals($chk_total, $total, 'Full-access user should see ' . $total . ' rows. Actual: ' . $chk_total);
    $chk_total = count($this->xpath("//td[@headers='view-title-1-table-column']/a"));
    $this->assertEquals($chk_total, $count_s_total, 'Full-access user should see ' . $count_s_total . ' primary references. Actual: ' . $chk_total);
    $chk_total = count($this->xpath("//td[@headers='view-title-2-table-column']/a"));
    $this->assertEquals($chk_total, $count_s2_total, 'Full-access user should see ' . $count_s2_total . ' secondary references. Actual: ' . $chk_total);
    $session->pageTextContains('Page - no_reference');
    $session->pageTextContains('Page - public - no_reference');
    $session->pageTextContains('Page - public - public');
    $session->pageTextContains('Page - author_private - no_reference');
    $session->pageTextContains('Article public');
    $session->pageTextContains('Article private');
    $session->pageTextContains('Article author_public');
    $session->pageTextContains('Article author_private');
  }

}
