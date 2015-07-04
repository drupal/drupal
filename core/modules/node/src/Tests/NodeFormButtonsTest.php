<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeFormButtonsTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests all the different buttons on the node form.
 *
 * @group node
 */
class NodeFormButtonsTest extends NodeTestBase {

  use AssertButtonsTrait;

  /**
   * A normal logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    // Create a user that has no access to change the state of the node.
    $this->webUser = $this->drupalCreateUser(array('create article content', 'edit own article content'));
    // Create a user that has access to change the state of the node.
    $this->adminUser = $this->drupalCreateUser(array('administer nodes', 'bypass node access'));
  }

  /**
   * Tests that the right buttons are displayed for saving nodes.
   */
  function testNodeFormButtons() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    // Login as administrative user.
    $this->drupalLogin($this->adminUser);

    // Verify the buttons on a node add form.
    $this->drupalGet('node/add/article');
    $this->assertButtons(array(t('Save and publish'), t('Save as unpublished')));

    // Save the node and assert it's published after clicking
    // 'Save and publish'.
    $edit = array('title[0][value]' => $this->randomString());
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));

    // Get the node.
    $node_1 = $node_storage->load(1);
    $this->assertTrue($node_1->isPublished(), 'Node is published');

    // Verify the buttons on a node edit form.
    $this->drupalGet('node/' . $node_1->id() . '/edit');
    $this->assertButtons(array(t('Save and keep published'), t('Save and unpublish')));

    // Save the node and verify it's still published after clicking
    // 'Save and keep published'.
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $node_storage->resetCache(array(1));
    $node_1 = $node_storage->load(1);
    $this->assertTrue($node_1->isPublished(), 'Node is published');

    // Save the node and verify it's unpublished after clicking
    // 'Save and unpublish'.
    $this->drupalPostForm('node/' . $node_1->id() . '/edit', $edit, t('Save and unpublish'));
    $node_storage->resetCache(array(1));
    $node_1 = $node_storage->load(1);
    $this->assertFalse($node_1->isPublished(), 'Node is unpublished');

    // Verify the buttons on an unpublished node edit screen.
    $this->drupalGet('node/' . $node_1->id() . '/edit');
    $this->assertButtons(array(t('Save and keep unpublished'), t('Save and publish')));

    // Create a node as a normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->webUser);

    // Verify the buttons for a normal user.
    $this->drupalGet('node/add/article');
    $this->assertButtons(array(t('Save')), FALSE);

    // Create the node.
    $edit = array('title[0][value]' => $this->randomString());
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $node_2 = $node_storage->load(2);
    $this->assertTrue($node_2->isPublished(), 'Node is published');

    // Login as an administrator and unpublish the node that just
    // was created by the normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('node/' . $node_2->id() . '/edit', array(), t('Save and unpublish'));
    $node_storage->resetCache(array(2));
    $node_2 = $node_storage->load(2);
    $this->assertFalse($node_2->isPublished(), 'Node is unpublished');

    // Login again as the normal user, save the node and verify
    // it's still unpublished.
    $this->drupalLogout();
    $this->drupalLogin($this->webUser);
    $this->drupalPostForm('node/' . $node_2->id() . '/edit', array(), t('Save'));
    $node_storage->resetCache(array(2));
    $node_2 = $node_storage->load(2);
    $this->assertFalse($node_2->isPublished(), 'Node is still unpublished');
    $this->drupalLogout();

    // Set article content type default to unpublished. This will change the
    // the initial order of buttons and/or status of the node when creating
    // a node.
    $fields = \Drupal::entityManager()->getFieldDefinitions('node', 'article');
    $fields['status']->getConfig('article')
      ->setDefaultValue(FALSE)
      ->save();

    // Verify the buttons on a node add form for an administrator.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/add/article');
    $this->assertButtons(array(t('Save as unpublished'), t('Save and publish')));

    // Verify the node is unpublished by default for a normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->webUser);
    $edit = array('title[0][value]' => $this->randomString());
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $node_3 = $node_storage->load(3);
    $this->assertFalse($node_3->isPublished(), 'Node is unpublished');
  }
}
