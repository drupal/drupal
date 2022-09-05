<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\FunctionalTests\Update\UpdatePathTestBaseTest;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Runs UpdatePathTestBaseTest with a dump filled with content.
 *
 * @group #slow
 * @group Update
 */
class UpdatePathTestBaseFilledTest extends UpdatePathTestBaseTest {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    parent::setDatabaseDumpFiles();
    $this->databaseDumpFiles[0] = __DIR__ . '/../../../../tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz';
  }

  /**
   * Tests that the content and configuration were properly updated.
   */
  public function testUpdatedSite() {
    $this->runUpdates();

    $spanish = \Drupal::languageManager()->getLanguage('es');

    $expected_node_data = [
      [1, 'article', 'en', 'Test Article - New title'],
      [2, 'book', 'en', 'Book page'],
      [3, 'forum', 'en', 'Forum topic'],
      [4, 'page', 'en', 'Test page'],
      [8, 'test_content_type', 'en', 'Test title'],
    ];
    foreach ($expected_node_data as $node_data) {
      $id = $node_data[0];
      $type = $node_data[1];
      $langcode = $node_data[2];
      $title = $node_data[3];

      // Make sure our English nodes still exist.
      $node = Node::load($id);
      $this->assertEquals($langcode, $node->language()->getId());
      $this->assertEquals($type, $node->getType());
      $this->assertEquals($title, $node->getTitle());
      // Assert that nodes are all published.
      $this->assertTrue($node->isPublished());
      $this->drupalGet('node/' . $id);
      $this->assertSession()->pageTextContains($title);
    }

    // Make sure the translated node still exists.
    $translation = Node::load(8)->getTranslation('es');
    $this->assertEquals('Test title Spanish', $translation->getTitle());

    // Make sure our alias still works.
    $this->drupalGet('test-article');
    $this->assertSession()->pageTextContains('Test Article - New title');
    $this->assertSession()->pageTextContains('Body');
    $this->assertSession()->pageTextContains('Tags');

    // Make sure a translated page exists.
    $this->drupalGet('node/8', ['language' => $spanish]);
    // Check for text of two comments.
    $this->assertSession()->pageTextContains('Hola');
    $this->assertSession()->pageTextContains('Hello');
    // The user entity reference field is access restricted.
    $this->assertSession()->pageTextNotContains('Test 12');
    // Make sure all other field labels are there.
    for ($i = 1; $i <= 23; $i++) {
      if ($i != 12) {
        $this->assertSession()->pageTextContains('Test ' . $i);
      }
    }

    // Make sure the custom block appears.
    $this->drupalGet('<front>');
    // Block title.
    $this->assertSession()->pageTextContains('Another block');
    // Block body.
    $this->assertSession()->pageTextContains('Hello');

    // Log in as user 1.
    $account = User::load(1);
    $account->passRaw = 'drupal';
    $this->drupalLogin($account);

    // Make sure we can see the access-restricted entity reference field
    // now that we're logged in.
    $this->drupalGet('node/8', ['language' => $spanish]);
    $this->assertSession()->pageTextContains('Test 12');
    $this->assertSession()->linkExists('drupal');

    // Make sure the content for node 8 is still in the edit form.
    $this->drupalGet('node/8/edit');
    $this->assertSession()->pageTextContains('Test title');
    $this->assertSession()->pageTextContains('Test body');
    $this->assertSession()->checkboxChecked('edit-field-test-1-value');
    $this->assertSession()->responseContains('2015-08-16');
    $this->assertSession()->responseContains('test@example.com');
    $this->assertSession()->responseContains('drupal.org');
    $this->assertSession()->pageTextContains('0.1');
    $this->assertSession()->pageTextContains('0.2');
    $this->assertSession()->responseContains('+31612345678');
    $this->assertSession()->responseContains('+31612345679');
    $this->assertSession()->pageTextContains('Test Article - New title');
    $this->assertSession()->pageTextContains('test.txt');
    $this->assertSession()->pageTextContains('druplicon.small');
    $this->assertSession()->responseContains('General discussion');
    $this->assertSession()->pageTextContains('Test Article - New title');
    $this->assertSession()->pageTextContains('Test 1');
    $this->assertSession()->responseContains('0.01');
    $this->drupalGet('node/8/edit');
    $this->submitForm([], 'Save (this translation)');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('node/8/edit', ['language' => $spanish]);
    $this->assertSession()->pageTextContains('Test title Spanish');
    $this->assertSession()->pageTextContains('Test body Spanish');

    // Make sure the user page is correct.
    $this->drupalGet('user/3');
    $this->assertSession()->pageTextContains('usuario_test');
    $this->assertSession()->responseContains('druplicon.small');
    $this->assertSession()->pageTextContains('Test file field');
    $this->assertSession()->linkExists('test.txt');

    // Make sure the user is translated.
    $this->drupalGet('user/3/translations');
    $this->assertSession()->pageTextNotContains('Not translated');

    // Make sure the custom field on the user is still there.
    $this->drupalGet('admin/config/people/accounts/fields');
    $this->assertSession()->pageTextContains('Test file field');

    // Make sure the test view still exists.
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->assertSession()->pageTextContains('Test view');

    // Make sure the book node exists.
    $this->drupalGet('admin/structure/book');
    $this->clickLink('Test Article - New title');
    $this->assertSession()->pageTextContains('Body');
    $this->assertSession()->pageTextContains('Tags');
    $this->assertSession()->responseContains('Text format');

    // Make sure that users still exist.
    $this->drupalGet('admin/people');
    $this->assertSession()->pageTextContains('usuario_test');
    $this->assertSession()->pageTextContains('drupal');
    $this->drupalGet('user/1/edit');
    $this->assertSession()->responseContains('drupal@example.com');

    // Make sure the content view works.
    $this->drupalGet('admin/content');
    $this->assertSession()->pageTextContains('Test title');

    // Make sure our custom blocks show up.
    $this->drupalGet('admin/structure/block');
    $this->assertSession()->pageTextContains('Another block');
    $this->assertSession()->pageTextContains('Test block');
    $this->drupalGet('admin/structure/block/block-content');
    $this->assertSession()->pageTextContains('Another block');
    $this->assertSession()->pageTextContains('Test block');

    // Make sure our custom visibility conditions are correct.
    $this->drupalGet('admin/structure/block/manage/testblock');
    $this->assertSession()->checkboxNotChecked('edit-visibility-language-langcodes-es');
    $this->assertSession()->checkboxChecked('edit-visibility-language-langcodes-en');
    $this->assertSession()->checkboxNotChecked('edit-visibility-entity-bundlenode-bundles-book');
    $this->assertSession()->checkboxChecked('edit-visibility-entity-bundlenode-bundles-test-content-type');

    // Make sure our block is still translated.
    $this->drupalGet('admin/structure/block/manage/testblock/translate/es/edit');
    $this->assertSession()->responseContains('Test block spanish');

    // Make sure our custom text format exists.
    $this->drupalGet('admin/config/content/formats');
    $this->assertSession()->pageTextContains('Test text format');
    $this->drupalGet('admin/config/content/formats/manage/test_text_format');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure our view appears in the overview.
    $this->drupalGet('admin/structure/views');
    $this->assertSession()->pageTextContains('test_view');
    $this->assertSession()->pageTextContains('Test view');

    // Make sure our custom forum exists.
    $this->drupalGet('admin/structure/forum');
    $this->assertSession()->pageTextContains('Test forum');

    // Make sure our custom menu exists.
    $this->drupalGet('admin/structure/menu');
    $this->assertSession()->pageTextContains('Test menu');

    // Make sure our custom menu exists.
    $this->drupalGet('admin/structure/menu/manage/test-menu');
    $this->clickLink('Admin');
    // Make sure the translation for the menu is still correct.
    $this->drupalGet('admin/structure/menu/manage/test-menu/translate/es/edit');
    $this->assertSession()->responseContains('Menu test');
    // Make sure our custom menu link exists.
    $this->drupalGet('admin/structure/menu/item/1/edit');
    $this->assertSession()->checkboxChecked('edit-enabled-value');

    // Make sure our comment type exists.
    $this->drupalGet('admin/structure/comment');
    $this->assertSession()->pageTextContains('Test comment type');
    $this->drupalGet('admin/structure/comment/manage/test_comment_type/fields');
    $this->assertSession()->pageTextContains('comment_body');

    // Make sure our contact form exists.
    $this->drupalGet('admin/structure/contact');
    $this->assertSession()->pageTextContains('Test contact form');
    $this->drupalGet('admin/structure/types');
    $this->assertSession()->pageTextContains('Test content type description');
    $this->drupalGet('admin/structure/types/manage/test_content_type/fields');

    // Make sure fields are the right type.
    $this->assertSession()->linkExists('Text (formatted, long, with summary)');
    $this->assertSession()->linkExists('Boolean');
    $this->assertSession()->linkExists('Comments');
    $this->assertSession()->linkExists('Date');
    $this->assertSession()->linkExists('Email');
    $this->assertSession()->linkExists('Link');
    $this->assertSession()->linkExists('List (float)');
    $this->assertSession()->linkExists('Telephone number');
    $this->assertSession()->linkExists('Entity reference');
    $this->assertSession()->linkExists('File');
    $this->assertSession()->linkExists('Image');
    $this->assertSession()->linkExists('Text (plain, long)');
    $this->assertSession()->linkExists('List (text)');
    $this->assertSession()->linkExists('Text (formatted, long)');
    $this->assertSession()->linkExists('Text (plain)');
    $this->assertSession()->linkExists('List (integer)');
    $this->assertSession()->linkExists('Number (integer)');
    $this->assertSession()->linkExists('Number (float)');

    // Make sure our form mode exists.
    $this->drupalGet('admin/structure/display-modes/form');
    $this->assertSession()->pageTextContains('New form mode');

    // Make sure our view mode exists.
    $this->drupalGet('admin/structure/display-modes/view');
    $this->assertSession()->pageTextContains('New view mode');
    $this->drupalGet('admin/structure/display-modes/view/manage/node.new_view_mode');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure our other language is still there.
    $this->drupalGet('admin/config/regional/language');
    $this->assertSession()->pageTextContains('Spanish');

    // Make sure our custom date format exists.
    $this->drupalGet('admin/config/regional/date-time');
    $this->assertSession()->pageTextContains('Test date format');
    $this->drupalGet('admin/config/regional/date-time/formats/manage/test_date_format');
    $this->assertTrue($this->assertSession()->optionExists('edit-langcode', 'es')->isSelected());

    // Make sure our custom image style exists.
    $this->drupalGet('admin/config/media/image-styles/manage/test_image_style');
    $this->assertSession()->pageTextContains('Test image style');
    $this->assertSession()->pageTextContains('Desaturate');
    $this->assertSession()->pageTextContains('Convert PNG');

    // Make sure our custom responsive image style exists.
    $this->drupalGet('admin/config/media/responsive-image-style/test');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Test');

    // Make sure our custom shortcut exists.
    $this->drupalGet('admin/config/user-interface/shortcut');
    $this->assertSession()->pageTextContains('Test shortcut');
    $this->drupalGet('admin/config/user-interface/shortcut/manage/test/customize');
    $this->assertSession()->pageTextContains('All content');

    // Make sure our language detection settings are still correct.
    $this->drupalGet('admin/config/regional/language/detection');
    $this->assertSession()->checkboxChecked('edit-language-interface-enabled-language-user-admin');
    $this->assertSession()->checkboxChecked('edit-language-interface-enabled-language-url');
    $this->assertSession()->checkboxChecked('edit-language-interface-enabled-language-session');
    $this->assertSession()->checkboxChecked('edit-language-interface-enabled-language-user');
    $this->assertSession()->checkboxChecked('edit-language-interface-enabled-language-browser');

    // Make sure strings are still translated.
    $this->drupalGet('admin/structure/views/view/content/translate/es/edit');
    // cSpell:disable-next-line
    $this->assertSession()->pageTextContains('Contenido');
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm(['string' => 'Full comment'], 'Filter');
    // cSpell:disable-next-line
    $this->assertSession()->pageTextContains('Comentario completo');

    // Make sure our custom action is still there.
    $this->drupalGet('admin/config/system/actions');
    $this->assertSession()->pageTextContains('Test action');
    $this->drupalGet('admin/config/system/actions/configure/test_action');
    $this->assertSession()->fieldValueEquals('id', 'test_action');
    $this->assertSession()->responseContains('drupal.org');

    // Make sure our ban still exists.
    $this->drupalGet('admin/config/people/ban');
    $this->assertSession()->pageTextContains('8.8.8.8');

    // Make sure our vocabulary exists.
    $this->drupalGet('admin/structure/taxonomy/manage/test_vocabulary/overview');

    // Make sure our terms exist.
    $this->assertSession()->pageTextContains('Test root term');
    $this->assertSession()->pageTextContains('Test child term');
    $this->drupalGet('taxonomy/term/3');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure the terms are still translated.
    $this->drupalGet('taxonomy/term/2/translations');
    $this->assertSession()->linkExists('Test root term - Spanish');

    // Make sure our contact form exists.
    $this->drupalGet('admin/structure/contact');
    $this->assertSession()->pageTextContains('Test contact form');
    $this->drupalGet('admin/structure/contact/manage/test_contact_form');
    $this->assertSession()->pageTextContains('test@example.com');
    $this->assertSession()->pageTextContains('Hello');
    $this->drupalGet('admin/structure/contact/manage/test_contact_form/translate/es/edit');
    $this->assertSession()->pageTextContains('Hola');
    $this->assertSession()->responseContains('Test contact form Spanish');

    // Make sure our modules are still enabled.
    $expected_enabled_modules = [
      'action',
      'ban',
      'basic_auth',
      'block',
      'block_content',
      'book',
      'breakpoint',
      'ckeditor5',
      'comment',
      'config',
      'config_translation',
      'contact',
      'content_translation',
      'contextual',
      'datetime',
      'dblog',
      'editor',
      'field',
      'field_ui',
      'file',
      'filter',
      'help',
      'history',
      'image',
      'language',
      'link',
      'locale',
      'menu_ui',
      'migrate',
      'migrate_drupal',
      'node',
      'options',
      'page_cache',
      'path',
      'responsive_image',
      'rest',
      'search',
      'serialization',
      'shortcut',
      'statistics',
      'syslog',
      'system',
      'taxonomy',
      'telephone',
      'text',
      'toolbar',
      'tour',
      'tracker',
      'update',
      'user',
      'views_ui',
      'forum',
      'menu_link_content',
      'views',
      'standard',
    ];
    foreach ($expected_enabled_modules as $module) {
      $this->assertTrue($this->container->get('module_handler')->moduleExists($module), 'The "' . $module . '" module is still enabled.');
    }

    // Make sure our themes are still enabled.
    $expected_enabled_themes = [
      'olivero',
      'claro',
      'stark',
    ];
    foreach ($expected_enabled_themes as $theme) {
      $this->assertTrue($this->container->get('theme_handler')->themeExists($theme), 'The "' . $theme . '" is still enabled.');
    }

    // Ensure that the Book module's node type does not have duplicated enforced
    // dependencies.
    // @see system_post_update_fix_enforced_dependencies()
    $book_node_type = NodeType::load('book');
    $this->assertEquals(['enforced' => ['module' => ['book']]], $book_node_type->get('dependencies'));
  }

  /**
   * {@inheritdoc}
   */
  protected function replaceUser1() {
    // Do not replace the user from our dump.
  }

}
