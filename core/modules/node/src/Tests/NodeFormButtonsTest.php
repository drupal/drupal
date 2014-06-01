<?php

/**
 * @file
 * Contains Drupal\node\Tests\NodeLoadHooksTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests the node form buttons.
 */
class NodeFormButtonsTest extends NodeTestBase {

  protected $web_user;

  protected $admin_user;

  public static function getInfo() {
    return array(
      'name' => 'Node form buttons',
      'description' => 'Test all the different buttons on the node form.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp();

    // Create a user that has no access to change the state of the node.
    $this->web_user = $this->drupalCreateUser(array('create article content', 'edit own article content'));
    // Create a user that has access to change the state of the node.
    $this->admin_user = $this->drupalCreateUser(array('administer nodes', 'bypass node access'));
  }

  /**
   * Tests that the right buttons are displayed for saving nodes.
   */
  function testNodeFormButtons() {

    // Login as administrative user.
    $this->drupalLogin($this->admin_user);

    // Verify the buttons on a node add form.
    $this->drupalGet('node/add/article');
    $this->assertButtons(array(t('Save and publish'), t('Save as unpublished')));

    // Save the node and assert it's published after clicking
    // 'Save and publish'.
    $edit = array('title[0][value]' => $this->randomString());
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));

    // Get the node.
    $node_1 = node_load(1);
    $this->assertTrue($node_1->isPublished(), 'Node is published');

    // Verify the buttons on a node edit form.
    $this->drupalGet('node/' . $node_1->id() . '/edit');
    $this->assertButtons(array(t('Save and keep published'), t('Save and unpublish')));

    // Save the node and verify it's still published after clicking
    // 'Save and keep published'.
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $node_1 = node_load(1, TRUE);
    $this->assertTrue($node_1->isPublished(), 'Node is published');

    // Save the node and verify it's unpublished after clicking
    // 'Save and unpublish'.
    $this->drupalPostForm('node/' . $node_1->id() . '/edit', $edit, t('Save and unpublish'));
    $node_1 = node_load(1, TRUE);
    $this->assertFalse($node_1->isPublished(), 'Node is unpublished');

    // Verify the buttons on an unpublished node edit screen.
    $this->drupalGet('node/' . $node_1->id() . '/edit');
    $this->assertButtons(array(t('Save and keep unpublished'), t('Save and publish')));

    // Create a node as a normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->web_user);

    // Verify the buttons for a normal user.
    $this->drupalGet('node/add/article');
    $this->assertButtons(array(t('Save')), FALSE);

    // Create the node.
    $edit = array('title[0][value]' => $this->randomString());
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $node_2 = node_load(2);
    $this->assertTrue($node_2->isPublished(), 'Node is published');

    // Login as an administrator and unpublish the node that just
    // was created by the normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $this->drupalPostForm('node/' . $node_2->id() . '/edit', array(), t('Save and unpublish'));
    $node_2 = node_load(2, TRUE);
    $this->assertFalse($node_2->isPublished(), 'Node is unpublished');

    // Login again as the normal user, save the node and verify
    // it's still unpublished.
    $this->drupalLogout();
    $this->drupalLogin($this->web_user);
    $this->drupalPostForm('node/' . $node_2->id() . '/edit', array(), t('Save'));
    $node_2 = node_load(2, TRUE);
    $this->assertFalse($node_2->isPublished(), 'Node is still unpublished');
    $this->drupalLogout();

    // Set article content type default to unpublished. This will change the
    // the initial order of buttons and/or status of the node when creating
    // a node.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $this->container->get('entity.manager')->getStorage('node_type')->load('article');
    $node_type->settings['node']['options']['status'] = FALSE;
    $node_type->save();

    // Verify the buttons on a node add form for an administrator.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('node/add/article');
    $this->assertButtons(array(t('Save as unpublished'), t('Save and publish')));

    // Verify the node is unpublished by default for a normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->web_user);
    $edit = array('title[0][value]' => $this->randomString());
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $node_3 = node_load(3);
    $this->assertFalse($node_3->isPublished(), 'Node is unpublished');
  }

  /**
   * Assert method to verify the buttons in the dropdown element.
   *
   * @param array $buttons
   *   A collection of buttons to assert for on the page.
   * @param bool $dropbutton
   *   Whether to check if the buttons are in a dropbutton widget or not.
   */
  public function assertButtons($buttons, $dropbutton = TRUE) {

    // Try to find a Save button.
    $save_button = $this->xpath('//input[@type="submit"][@value="Save"]');

    // Verify that the number of buttons passed as parameters is
    // available in the dropbutton widget.
    if ($dropbutton) {
      $i = 0;
      $count = count($buttons);

      // Assert there is no save button.
      $this->assertTrue(empty($save_button));

      // Dropbutton elements.
      $elements = $this->xpath('//div[@class="dropbutton-wrapper"]//input[@type="submit"]');
      $this->assertEqual($count, count($elements));
      foreach ($elements as $element) {
        $value = isset($element['value']) ? (string) $element['value'] : '';
        $this->assertEqual($buttons[$i], $value);
        $i++;
      }
    }
    else {
      // Assert there is a save button.
      $this->assertTrue(!empty($save_button));
      $this->assertNoRaw('dropbutton-wrapper');
    }
  }
}
