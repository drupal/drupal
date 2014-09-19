<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\BlockContentTypeTest.
 */

namespace Drupal\block_content\Tests;

/**
 * Ensures that custom block type functions work correctly.
 *
 * @group block_content
 */
class BlockContentTypeTest extends BlockContentTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_ui');

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = array(
    'administer blocks',
    'administer block_content fields'
  );

  /**
   * Tests creating a block type programmatically and via a form.
   */
  public function testBlockContentTypeCreation() {
    // Create a block type programmaticaly.
    $type = $this->createBlockContentType('other');

    $block_type = entity_load('block_content_type', 'other');
    $this->assertTrue($block_type, 'The new block type has been created.');

    // Login a test user.
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('block/add/' . $type->id());
    $this->assertResponse(200, 'The new block type can be accessed at bloack/add.');

    // Create a block type via the user interface.
    $edit = array(
      'id' => 'foo',
      'label' => 'title for foo',
    );
    $this->drupalPostForm('admin/structure/block/block-content/types/add', $edit, t('Save'));
    $block_type = entity_load('block_content_type', 'foo');
    $this->assertTrue($block_type, 'The new block type has been created.');

    // Check that the block type was created in site default language.
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->id;
    $this->assertEqual($block_type->language()->getId(), $default_langcode);
  }

  /**
   * Tests editing a block type using the UI.
   */
  public function testBlockContentTypeEditing() {
    $this->drupalLogin($this->adminUser);
    // We need two block types to prevent /block/add redirecting.
    $this->createBlockContentType('other');

    $field_definition = \Drupal::entityManager()->getFieldDefinitions('block_content', 'other')['body'];
    $this->assertEqual($field_definition->getLabel(), 'Body', 'Body field was found.');

    // Verify that title and body fields are displayed.
    $this->drupalGet('block/add/basic');
    $this->assertRaw('Block description', 'Block info field was found.');
    $this->assertRaw('Body', 'Body field was found.');

    // Change the block type name.
    $edit = array(
      'label' => 'Bar',
    );
    $this->drupalPostForm('admin/structure/block/block-content/manage/basic', $edit, t('Save'));
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    $this->drupalGet('block/add');
    $this->assertRaw('Bar', 'New name was displayed.');
    $this->clickLink('Bar');
    $this->assertEqual(url('block/add/basic', array('absolute' => TRUE)), $this->getUrl(), 'Original machine name was used in URL.');

    // Remove the body field.
    $this->drupalPostForm('admin/structure/block/block-content/manage/basic/fields/block_content.basic.body/delete', array(), t('Delete'));
    // Resave the settings for this type.
    $this->drupalPostForm('admin/structure/block/block-content/manage/basic', array(), t('Save'));
    // Check that the body field doesn't exist.
    $this->drupalGet('block/add/basic');
    $this->assertNoRaw('Body', 'Body field was not found.');
  }

  /**
   * Tests deleting a block type that still has content.
   */
  public function testBlockContentTypeDeletion() {
    // Create a block type programmatically.
    $type = $this->createBlockContentType('foo');

    $this->drupalLogin($this->adminUser);

    // Add a new block of this type.
    $block = $this->createBlockContent(FALSE, 'foo');
    // Attempt to delete the block type, which should not be allowed.
    $this->drupalGet('admin/structure/block/block-content/manage/' . $type->id() . '/delete');
    $this->assertRaw(
      t('%label is used by 1 custom block on your site. You can not remove this block type until you have removed all of the %label blocks.', array('%label' => $type->label())),
      'The block type will not be deleted until all blocks of that type are removed.'
    );
    $this->assertNoText(t('This action cannot be undone.'), 'The node type deletion confirmation form is not available.');

    // Delete the block.
    $block->delete();
    // Attempt to delete the block type, which should now be allowed.
    $this->drupalGet('admin/structure/block/block-content/manage/' . $type->id() . '/delete');
    $this->assertRaw(
      t('Are you sure you want to delete %type?', array('%type' => $type->id())),
      'The block type is available for deletion.'
    );
    $this->assertText(t('This action cannot be undone.'), 'The custom block type deletion confirmation form is available.');
  }

  /**
   * Tests that redirects work as expected when multiple block types exist.
   */
  public function testsBlockContentAddTypes() {
    $this->drupalLogin($this->adminUser);
    // Create two block types programmatically.
    $type = $this->createBlockContentType('foo');
    $type = $this->createBlockContentType('bar');

    // Get the custom block storage.
    $storage = $this->container
      ->get('entity.manager')
      ->getStorage('block_content');

    // Install all themes.
    \Drupal::service('theme_handler')->install(array('bartik', 'seven'));
    $themes = array('bartik', 'seven', 'stark');
    $theme_settings = $this->container->get('config.factory')->get('system.theme');
    foreach ($themes as $default_theme) {
      // Change the default theme.
      $theme_settings->set('default', $default_theme)->save();
      \Drupal::service('router.builder')->rebuild();

      // For each installed theme, go to its block page and test the redirects.
      $themes = array('bartik', 'stark', 'seven');
      foreach ($themes as $theme) {
        // Test that adding a block from the 'place blocks' form sends you to the
        // block configure form.
        $path = $theme == $default_theme ? 'admin/structure/block' : "admin/structure/block/list/$theme";
        $this->drupalGet($path);
        $this->clickLink(t('Add custom block'));
        // The seven theme has markup inside the link, we cannot use clickLink().
        if ($default_theme == 'seven') {
          $options = $theme != $default_theme ? array('query' => array('theme' => $theme)) : array();
          $this->assertLinkByHref(url('block/add/foo', $options));
          $this->drupalGet('block/add/foo', $options);
        }
        else {
          $this->clickLink('foo');
        }
        // Create a new block.
        $edit = array('info[0][value]' => $this->randomMachineName(8));
        $this->drupalPostForm(NULL, $edit, t('Save'));
        $blocks = $storage->loadByProperties(array('info' => $edit['info[0][value]']));
        if (!empty($blocks)) {
          $block = reset($blocks);
          $destination = 'admin/structure/block/add/block_content:' . $block->uuid() . '/' . $theme;
          $this->assertUrl(url($destination, array('absolute' => TRUE)));
          $this->drupalPostForm(NULL, array(), t('Save block'));
          $this->assertUrl(url("admin/structure/block/list/$theme", array('absolute' => TRUE, 'query' => array('block-placement' => drupal_html_class($edit['info[0][value]'])))));
        }
        else {
          $this->fail('Could not load created block.');
        }
      }
    }

    // Test that adding a block from the 'custom blocks list' doesn't send you
    // to the block configure form.
    $this->drupalGet('admin/structure/block/block-content');
    $this->clickLink(t('Add custom block'));
    $this->clickLink('foo');
    $edit = array('info[0][value]' => $this->randomMachineName(8));
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $blocks = $storage->loadByProperties(array('info' => $edit['info[0][value]']));
    if (!empty($blocks)) {
      $destination = 'admin/structure/block/block-content';
      $this->assertUrl(url($destination, array('absolute' => TRUE)));
    }
    else {
      $this->fail('Could not load created block.');
    }
  }

}
