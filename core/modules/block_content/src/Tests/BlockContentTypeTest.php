<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\BlockContentTypeTest.
 */

namespace Drupal\block_content\Tests;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

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
   * Whether or not to create an initial block type.
   *
   * @var bool
   */
  protected $autoCreateBasicBlockType = FALSE;

  /**
   * Tests creating a block type programmatically and via a form.
   */
  public function testBlockContentTypeCreation() {
    // Login a test user.
    $this->drupalLogin($this->adminUser);

    // Test the page with no block-types.
    $this->drupalGet('block/add');
    $this->assertResponse(200);
    $this->assertText('You have not created any block types yet');
    $this->clickLink('block type creation page');

    // Create a block type via the user interface.
    $edit = array(
      'id' => 'foo',
      'label' => 'title for foo',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $block_type = BlockContentType::load('foo');
    $this->assertTrue($block_type, 'The new block type has been created.');

    $field_definitions = \Drupal::entityManager()->getFieldDefinitions('block_content', 'foo');
    $this->assertTrue(isset($field_definitions['body']), 'Body field created when using the UI to create block content types.');

    // Check that the block type was created in site default language.
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $this->assertEqual($block_type->language()->getId(), $default_langcode);

    // Create block types programmatically.
    $this->createBlockContentType('basic', TRUE);
    $field_definitions = \Drupal::entityManager()->getFieldDefinitions('block_content', 'basic');
    $this->assertTrue(isset($field_definitions['body']), "Body field for 'basic' block type created when using the testing API to create block content types.");

    $this->createBlockContentType('other');
    $field_definitions = \Drupal::entityManager()->getFieldDefinitions('block_content', 'other');
    $this->assertFalse(isset($field_definitions['body']), "Body field for 'other' block type not created when using the testing API to create block content types.");

    $block_type = BlockContentType::load('other');
    $this->assertTrue($block_type, 'The new block type has been created.');

    $this->drupalGet('block/add/' . $block_type->id());
    $this->assertResponse(200);
  }

  /**
   * Tests editing a block type using the UI.
   */
  public function testBlockContentTypeEditing() {
    // Now create an initial block-type.
    $this->createBlockContentType('basic', TRUE);

    $this->drupalLogin($this->adminUser);
    // We need two block types to prevent /block/add redirecting.
    $this->createBlockContentType('other');

    $field_definitions = \Drupal::entityManager()->getFieldDefinitions('block_content', 'other');
    $this->assertFalse(isset($field_definitions['body']), 'Body field was not created when using the API to create block content types.');

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
    $this->assertUrl(\Drupal::url('block_content.add_form', ['block_content_type' => 'basic'], ['absolute' => TRUE]), [], 'Original machine name was used in URL.');

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
    // Now create an initial block-type.
    $this->createBlockContentType('basic', TRUE);

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
    $this->assertNoText(t('This action cannot be undone.'), 'The block type deletion confirmation form is not available.');

    // Delete the block.
    $block->delete();
    // Attempt to delete the block type, which should now be allowed.
    $this->drupalGet('admin/structure/block/block-content/manage/' . $type->id() . '/delete');
    $this->assertRaw(
      t('Are you sure you want to delete the custom block type %type?', array('%type' => $type->id())),
      'The block type is available for deletion.'
    );
    $this->assertText(t('This action cannot be undone.'), 'The custom block type deletion confirmation form is available.');
  }

  /**
   * Tests that redirects work as expected when multiple block types exist.
   */
  public function testsBlockContentAddTypes() {
    // Now create an initial block-type.
    $this->createBlockContentType('basic', TRUE);

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
    $themes = array('bartik', 'seven', 'classy');
    $theme_settings = $this->config('system.theme');
    foreach ($themes as $default_theme) {
      // Change the default theme.
      $theme_settings->set('default', $default_theme)->save();
      \Drupal::service('router.builder')->rebuild();

      // For each installed theme, go to its block page and test the redirects.
      $themes = array('bartik', 'classy', 'seven');
      foreach ($themes as $theme) {
        // Test that adding a block from the 'place blocks' form sends you to the
        // block configure form.
        $path = $theme == $default_theme ? 'admin/structure/block' : "admin/structure/block/list/$theme";
        $this->drupalGet($path);
        $this->clickLinkPartialName('Place block');
        $this->clickLink(t('Add custom block'));
        // The seven theme has markup inside the link, we cannot use clickLink().
        if ($default_theme == 'seven') {
          $options = $theme != $default_theme ? array('query' => array('theme' => $theme)) : array();
          $this->assertLinkByHref(\Drupal::url('block_content.add_form', array('block_content_type' => 'foo'), $options));
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
          $this->assertUrl(\Drupal::url('block.admin_add', array('plugin_id' => 'block_content:' . $block->uuid(), 'theme' => $theme), array('absolute' => TRUE)));
          $this->drupalPostForm(NULL, array(), t('Save block'));
          $this->assertUrl(\Drupal::url('block.admin_display_theme', array('theme' => $theme), array('absolute' => TRUE, 'query' => array('block-placement' => Html::getClass($edit['info[0][value]'])))));
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
      $this->assertUrl(\Drupal::url('entity.block_content.collection', array(), array('absolute' => TRUE)));
    }
    else {
      $this->fail('Could not load created block.');
    }
  }

}
