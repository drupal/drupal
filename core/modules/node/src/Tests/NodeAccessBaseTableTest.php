<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeAccessBaseTableTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests for Node Access with a non-node base table.
 */
class NodeAccessBaseTableTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node_access_test');

  /**
   * The installation profile to use with this test.
   *
   * This test class requires the "tags" taxonomy field.
   *
   * @var string
   */
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Node access on any table',
      'description' => 'Checks behavior of the node access subsystem if the base table is not node.',
      'group' => 'Node',
    );
  }

  public function setUp() {
    parent::setUp();

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
  function testNodeAccessBasic() {
    $num_simple_users = 2;
    $simple_users = array();

    // nodes keyed by uid and nid: $nodes[$uid][$nid] = $is_private;
    $this->nodesByUser = array();
    $titles = array(); // Titles keyed by nid
    $private_nodes = array(); // Array of nids marked private.
    for ($i = 0; $i < $num_simple_users; $i++) {
      $simple_users[$i] = $this->drupalCreateUser(array('access content', 'create article content'));
    }
    foreach ($simple_users as $this->webUser) {
      $this->drupalLogin($this->webUser);
      foreach (array(0 => 'Public', 1 => 'Private') as $is_private => $type) {
        $edit = array(
          'title[0][value]' => t('@private_public Article created by @user', array('@private_public' => $type, '@user' => $this->webUser->getUsername())),
        );
        if ($is_private) {
          $edit['private'] = TRUE;
          $edit['body[0][value]'] = 'private node';
          $edit['field_tags'] = 'private';
        }
        else {
          $edit['body[0][value]'] = 'public node';
          $edit['field_tags'] = 'public';
        }

        $this->drupalPostForm('node/add/article', $edit, t('Save'));
        $nid = db_query('SELECT nid FROM {node_field_data} WHERE title = :title', array(':title' => $edit['title[0][value]']))->fetchField();
        $private_status = db_query('SELECT private FROM {node_access_test} where nid = :nid', array(':nid' => $nid))->fetchField();
        $this->assertTrue($is_private == $private_status, 'The private status of the node was properly set in the node_access_test table.');
        if ($is_private) {
          $private_nodes[] = $nid;
        }
        $titles[$nid] = $edit['title[0][value]'];
        $this->nodesByUser[$this->webUser->id()][$nid] = $is_private;
      }
    }
    $this->publicTid = db_query('SELECT tid FROM {taxonomy_term_data} WHERE name = :name', array(':name' => 'public'))->fetchField();
    $this->privateTid = db_query('SELECT tid FROM {taxonomy_term_data} WHERE name = :name', array(':name' => 'private'))->fetchField();
    $this->assertTrue($this->publicTid, 'Public tid was found');
    $this->assertTrue($this->privateTid, 'Private tid was found');
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
          $this->assertResponse($should_be_visible ? 200 : 403, strtr('A %private node by user %uid is %visible for user %current_uid.', array(
            '%private' => $is_private ? 'private' : 'public',
            '%uid' => $uid,
            '%visible' => $should_be_visible ? 'visible' : 'not visible',
            '%current_uid' => $this->webUser->id(),
          )));
        }
      }

      // Check to see that the correct nodes are shown on taxonomy/private
      // and taxonomy/public.
      $this->assertTaxonomyPage(FALSE);
    }

    // Now test that a user with 'node test view' permissions can view content.
    $access_user = $this->drupalCreateUser(array('access content', 'create article content', 'node test view', 'search content'));
    $this->drupalLogin($access_user);

    foreach ($this->nodesByUser as $private_status) {
      foreach ($private_status as $nid => $is_private) {
        $this->drupalGet('node/' . $nid);
        $this->assertResponse(200);
      }
    }

    // This user should be able to see all of the nodes on the relevant
    // taxonomy pages.
    $this->assertTaxonomyPage(TRUE);
  }

  /**
   * Checks taxonomy/term listings to ensure only accessible nodes are listed.
   *
   * @param $is_admin
   *   A boolean indicating whether the current user is an administrator. If
   *   TRUE, all nodes should be listed. If FALSE, only public nodes and the
   *   user's own private nodes should be listed.
   */
  protected function assertTaxonomyPage($is_admin) {
    foreach (array($this->publicTid, $this->privateTid) as $tid_is_private => $tid) {
      $this->drupalGet("taxonomy/term/$tid");
      $this->nids_visible = array();
      foreach ($this->xpath("//a[text()='Read more']") as $link) {
        // See also testTranslationRendering() in NodeTranslationUITest.
        $this->assertTrue(preg_match('|node/(\d+)$|', (string) $link['href'], $matches), 'Read more points to a node');
        $this->nids_visible[$matches[1]] = TRUE;
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
          $this->assertIdentical(isset($this->nids_visible[$nid]), $should_be_visible, strtr('A %private node by user %uid is %visible for user %current_uid on the %tid_is_private page.', array(
            '%private' => $is_private ? 'private' : 'public',
            '%uid' => $uid,
            '%visible' => isset($this->nids_visible[$nid]) ? 'visible' : 'not visible',
            '%current_uid' => $this->webUser->id(),
            '%tid_is_private' => $tid_is_private ? 'private' : 'public',
          )));
        }
      }
    }
  }
}
