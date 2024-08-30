<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;

/**
 * Tests behavior of the node access subsystem if the base table is not node.
 *
 * @group node
 */
class NodeAccessBaseTableTest extends NodeTestBase {

  use EntityReferenceFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node_access_test',
    'views',
    'taxonomy',
    'search',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Nodes by user.
   *
   * @var array
   */
  protected $nodesByUser;

  /**
   * A public tid.
   *
   * @var \Drupal\Core\Database\StatementInterface
   */
  protected $publicTid;

  /**
   * A private tid.
   *
   * @var \Drupal\Core\Database\StatementInterface
   */
  protected $privateTid;

  /**
   * A web user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $webUser;

  /**
   * The nids visible.
   *
   * @var array
   */
  protected $nidsVisible;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create the vocabulary for the tag field.
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary->save();
    $field_name = 'field_' . $vocabulary->id();

    $handler_settings = [
      'target_bundles' => [
        $vocabulary->id() => $vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];

    $this->createEntityReferenceField('node', 'article', $field_name, 'Tags', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager
      ->getStorage('entity_form_display')
      ->load('node.article.default')
      ->setComponent($field_name, [
        'type' => 'entity_reference_autocomplete_tags',
        'weight' => -4,
      ])
      ->save();

    node_access_test_add_field(NodeType::load('article'));

    node_access_rebuild();
    \Drupal::state()->set('node_access_test.private', TRUE);
  }

  /**
   * Tests the "private" node access functionality.
   *
   * - Create 2 users with "access content" and "create article" permissions.
   * - Each user creates one private and one not private article.
   *
   * - Test that each user can view the other user's non-private article.
   * - Test that each user cannot view the other user's private article.
   * - Test that each user finds only appropriate (non-private + own private)
   *   in taxonomy listing.
   * - Create another user with 'view any private content'.
   * - Test that user 4 can view all content created above.
   * - Test that user 4 can view all content on taxonomy listing.
   */
  public function testNodeAccessBasic(): void {
    $num_simple_users = 2;
    $simple_users = [];

    // Nodes keyed by uid and nid: $nodes[$uid][$nid] = $is_private;
    $this->nodesByUser = [];
    // Titles keyed by nid.
    $titles = [];
    // Array of nids marked private.
    $private_nodes = [];
    for ($i = 0; $i < $num_simple_users; $i++) {
      $simple_users[$i] = $this->drupalCreateUser([
        'access content',
        'create article content',
      ]);
    }
    foreach ($simple_users as $this->webUser) {
      $this->drupalLogin($this->webUser);
      foreach ([0 => 'Public', 1 => 'Private'] as $is_private => $type) {
        $edit = [
          'title[0][value]' => "$type Article created by " . $this->webUser->getAccountName(),
        ];
        if ($is_private) {
          $edit['private[0][value]'] = TRUE;
          $edit['body[0][value]'] = 'private node';
          $edit['field_tags[target_id]'] = 'private';
        }
        else {
          $edit['body[0][value]'] = 'public node';
          $edit['field_tags[target_id]'] = 'public';
        }

        $this->drupalGet('node/add/article');
        $this->submitForm($edit, 'Save');
        $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
        $this->assertEquals($is_private, (int) $node->private->value, 'The private status of the node was properly set in the node_access_test table.');
        if ($is_private) {
          $private_nodes[] = $node->id();
        }
        $titles[$node->id()] = $edit['title[0][value]'];
        $this->nodesByUser[$this->webUser->id()][$node->id()] = $is_private;
      }
    }
    $public_tids = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(FALSE)
      ->condition('name', 'public')
      ->condition('default_langcode', 1)
      ->execute();
    $this->publicTid = reset($public_tids);
    $private_tids = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(FALSE)
      ->condition('name', 'private')
      ->condition('default_langcode', 1)
      ->execute();
    $this->privateTid = reset($private_tids);

    $this->assertNotEmpty($this->publicTid, 'Public tid was found');
    $this->assertNotEmpty($this->privateTid, 'Private tid was found');
    foreach ($simple_users as $this->webUser) {
      $this->drupalLogin($this->webUser);
      // Check own nodes to see that all are readable.
      foreach ($this->nodesByUser as $uid => $data) {
        foreach ($data as $nid => $is_private) {
          $this->drupalGet('node/' . $nid);
          if ($is_private) {
            $should_be_visible = $uid == $this->webUser->id();
          }
          else {
            $should_be_visible = TRUE;
          }
          $this->assertSession()->statusCodeEquals($should_be_visible ? 200 : 403);
        }
      }

      // Check to see that the correct nodes are shown on taxonomy/private
      // and taxonomy/public.
      $this->assertTaxonomyPage(FALSE);
    }

    // Now test that a user with 'node test view' permissions can view content.
    $access_user = $this->drupalCreateUser([
      'access content',
      'create article content',
      'node test view',
      'search content',
    ]);
    $this->drupalLogin($access_user);

    foreach ($this->nodesByUser as $private_status) {
      foreach ($private_status as $nid => $is_private) {
        $this->drupalGet('node/' . $nid);
        $this->assertSession()->statusCodeEquals(200);
      }
    }

    // This user should be able to see all of the nodes on the relevant
    // taxonomy pages.
    $this->assertTaxonomyPage(TRUE);

    // Rebuild the node access permissions, repeat the test. This is done to
    // ensure that node access is rebuilt correctly even if the current user
    // does not have the bypass node access permission.
    node_access_rebuild();

    foreach ($this->nodesByUser as $private_status) {
      foreach ($private_status as $nid => $is_private) {
        $this->drupalGet('node/' . $nid);
        $this->assertSession()->statusCodeEquals(200);
      }
    }

    // This user should be able to see all of the nodes on the relevant
    // taxonomy pages.
    $this->assertTaxonomyPage(TRUE);
  }

  /**
   * Checks taxonomy/term listings to ensure only accessible nodes are listed.
   *
   * @param bool $is_admin
   *   A boolean indicating whether the current user is an administrator. If
   *   TRUE, all nodes should be listed. If FALSE, only public nodes and the
   *   user's own private nodes should be listed.
   *
   * @internal
   */
  protected function assertTaxonomyPage(bool $is_admin): void {
    foreach ([$this->publicTid, $this->privateTid] as $tid_is_private => $tid) {
      $this->drupalGet("taxonomy/term/$tid");
      $this->nidsVisible = [];
      foreach ($this->xpath("//a[text()='Read more']") as $link) {
        // See also testTranslationRendering() in NodeTranslationUITest.
        $this->assertEquals(1, preg_match('|node/(\d+)$|', $link->getAttribute('href'), $matches), 'Read more points to a node');
        $this->nidsVisible[$matches[1]] = TRUE;
      }
      foreach ($this->nodesByUser as $uid => $data) {
        foreach ($data as $nid => $is_private) {
          // Private nodes should be visible on the private term page,
          // public nodes should be visible on the public term page.
          $should_be_visible = $tid_is_private == $is_private;
          // Non-administrators can only see their own nodes on the private
          // term page.
          if (!$is_admin && $tid_is_private) {
            $should_be_visible = $should_be_visible && $uid == $this->webUser->id();
          }
          $this->assertSame($should_be_visible, isset($this->nidsVisible[$nid]), strtr('A %private node by user %uid is %visible for user %current_uid on the %tid_is_private page.', ['%private' => $is_private ? 'private' : 'public', '%uid' => $uid, '%visible' => isset($this->nidsVisible[$nid]) ? 'visible' : 'not visible', '%current_uid' => $this->webUser->id(), '%tid_is_private' => $tid_is_private ? 'private' : 'public']));
        }
      }
    }
  }

}
