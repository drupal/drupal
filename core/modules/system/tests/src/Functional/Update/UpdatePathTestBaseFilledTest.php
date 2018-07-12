<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBaseTest;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Runs UpdatePathTestBaseTest with a dump filled with content.
 *
 * @group Update
 * @group legacy
 */
class UpdatePathTestBaseFilledTest extends UpdatePathTestBaseTest {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    parent::setDatabaseDumpFiles();
    $this->databaseDumpFiles[0] = __DIR__ . '/../../../../tests/fixtures/update/drupal-8.filled.standard.php.gz';
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
      $this->assertEqual($node->language()->getId(), $langcode);
      $this->assertEqual($node->getType(), $type);
      $this->assertEqual($node->getTitle(), $title);
      // Assert that nodes are all published.
      $this->assertTrue($node->isPublished());
      $this->drupalGet('node/' . $id);
      $this->assertText($title);
    }

    // Make sure the translated node still exists.
    $translation = Node::load(8)->getTranslation('es');
    $this->assertEqual('Test title Spanish', $translation->getTitle());

    // Make sure our alias still works.
    $this->drupalGet('test-article');
    $this->assertText('Test Article - New title');
    $this->assertText('Body');
    $this->assertText('Tags');

    // Make sure a translated page exists.
    $this->drupalGet('node/8', ['language' => $spanish]);
    // Check for text of two comments.
    $this->assertText('Hola');
    $this->assertText('Hello');
    // The user entity reference field is access restricted.
    $this->assertNoText('Test 12');
    // Make sure all other field labels are there.
    for ($i = 1; $i <= 23; $i++) {
      if ($i != 12) {
        $this->assertText('Test ' . $i);
      }
    }

    // Make sure the translated slogan appears.
    $this->assertText('drupal Spanish');

    // Make sure the custom block appears.
    $this->drupalGet('<front>');
    // Block title.
    $this->assertText('Another block');
    // Block body.
    $this->assertText('Hello');

    // Log in as user 1.
    $account = User::load(1);
    $account->passRaw = 'drupal';
    $this->drupalLogin($account);

    // Make sure we can see the access-restricted entity reference field
    // now that we're logged in.
    $this->drupalGet('node/8', ['language' => $spanish]);
    $this->assertText('Test 12');
    $this->assertLink('drupal');

    // Make sure the content for node 8 is still in the edit form.
    $this->drupalGet('node/8/edit');
    $this->assertText('Test title');
    $this->assertText('Test body');
    $this->assertFieldChecked('edit-field-test-1-value');
    $this->assertRaw('2015-08-16');
    $this->assertRaw('test@example.com');
    $this->assertRaw('drupal.org');
    $this->assertText('0.1');
    $this->assertText('0.2');
    $this->assertRaw('+31612345678');
    $this->assertRaw('+31612345679');
    $this->assertText('Test Article - New title');
    $this->assertText('test.txt');
    $this->assertText('druplicon.small');
    $this->assertRaw('General discussion');
    $this->assertText('Test Article - New title');
    $this->assertText('Test 1');
    $this->assertRaw('0.01');
    $this->drupalPostForm('node/8/edit', [], 'Save (this translation)');
    $this->assertResponse(200);
    $this->drupalGet('node/8/edit', ['language' => $spanish]);
    $this->assertText('Test title Spanish');
    $this->assertText('Test body Spanish');

    // Make sure the user page is correct.
    $this->drupalGet('user/3');
    $this->assertText('usuario_test');
    $this->assertRaw('druplicon.small');
    $this->assertText('Test file field');
    $this->assertLink('test.txt');

    // Make sure the user is translated.
    $this->drupalGet('user/3/translations');
    $this->assertNoText('Not translated');

    // Make sure the custom field on the user is still there.
    $this->drupalGet('admin/config/people/accounts/fields');
    $this->assertText('Test file field');

    // Make sure the test view still exists.
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->assertText('Test view');

    // Make sure the book node exists.
    $this->drupalGet('admin/structure/book');
    $this->clickLink('Test Article - New title');
    $this->assertText('Body');
    $this->assertText('Tags');
    $this->assertRaw('Text format');

    // Make sure that users still exist.
    $this->drupalGet('admin/people');
    $this->assertText('usuario_test');
    $this->assertText('drupal');
    $this->drupalGet('user/1/edit');
    $this->assertRaw('drupal@example.com');

    // Make sure the content view works.
    $this->drupalGet('admin/content');
    $this->assertText('Test title');

    // Make sure our custom blocks show up.
    $this->drupalGet('admin/structure/block');
    $this->assertText('Another block');
    $this->assertText('Test block');
    $this->drupalGet('admin/structure/block/block-content');
    $this->assertText('Another block');
    $this->assertText('Test block');

    // Make sure our custom visibility conditions are correct.
    $this->drupalGet('admin/structure/block/manage/testblock');
    $this->assertNoFieldChecked('edit-visibility-language-langcodes-es');
    $this->assertFieldChecked('edit-visibility-language-langcodes-en');
    $this->assertNoFieldChecked('edit-visibility-node-type-bundles-book');
    $this->assertFieldChecked('edit-visibility-node-type-bundles-test-content-type');

    // Make sure our block is still translated.
    $this->drupalGet('admin/structure/block/manage/testblock/translate/es/edit');
    $this->assertRaw('Test block spanish');

    // Make sure our custom text format exists.
    $this->drupalGet('admin/config/content/formats');
    $this->assertText('Test text format');
    $this->drupalGet('admin/config/content/formats/manage/test_text_format');
    $this->assertResponse('200');

    // Make sure our feed still exists.
    $this->drupalGet('admin/config/services/aggregator');
    $this->assertText('Test feed');
    $this->drupalGet('admin/config/services/aggregator/fields');
    $this->assertText('field_test');

    // Make sure our view appears in the overview.
    $this->drupalGet('admin/structure/views');
    $this->assertText('test_view');
    $this->assertText('Test view');

    // Make sure our custom forum exists.
    $this->drupalGet('admin/structure/forum');
    $this->assertText('Test forum');

    // Make sure our custom menu exists.
    $this->drupalGet('admin/structure/menu');
    $this->assertText('Test menu');

    // Make sure our custom menu exists.
    $this->drupalGet('admin/structure/menu/manage/test-menu');
    $this->clickLink('Admin');
    // Make sure the translation for the menu is still correct.
    $this->drupalGet('admin/structure/menu/manage/test-menu/translate/es/edit');
    $this->assertRaw('Menu test');
    // Make sure our custom menu link exists.
    $this->drupalGet('admin/structure/menu/item/1/edit');
    $this->assertFieldChecked('edit-enabled-value');

    // Make sure our comment type exists.
    $this->drupalGet('admin/structure/comment');
    $this->assertText('Test comment type');
    $this->drupalGet('admin/structure/comment/manage/test_comment_type/fields');
    $this->assertText('comment_body');

    // Make sure our contact form exists.
    $this->drupalGet('admin/structure/contact');
    $this->assertText('Test contact form');
    $this->drupalGet('admin/structure/types');
    $this->assertText('Test content type description');
    $this->drupalGet('admin/structure/types/manage/test_content_type/fields');

    // Make sure fields are the right type.
    $this->assertLink('Text (formatted, long, with summary)');
    $this->assertLink('Boolean');
    $this->assertLink('Comments');
    $this->assertLink('Date');
    $this->assertLink('Email');
    $this->assertLink('Link');
    $this->assertLink('List (float)');
    $this->assertLink('Telephone number');
    $this->assertLink('Entity reference');
    $this->assertLink('File');
    $this->assertLink('Image');
    $this->assertLink('Text (plain, long)');
    $this->assertLink('List (text)');
    $this->assertLink('Text (formatted, long)');
    $this->assertLink('Text (plain)');
    $this->assertLink('List (integer)');
    $this->assertLink('Number (integer)');
    $this->assertLink('Number (float)');

    // Make sure our form mode exists.
    $this->drupalGet('admin/structure/display-modes/form');
    $this->assertText('New form mode');

    // Make sure our view mode exists.
    $this->drupalGet('admin/structure/display-modes/view');
    $this->assertText('New view mode');
    $this->drupalGet('admin/structure/display-modes/view/manage/node.new_view_mode');
    $this->assertResponse(200);

    // Make sure our other language is still there.
    $this->drupalGet('admin/config/regional/language');
    $this->assertText('Spanish');

    // Make sure our custom date format exists.
    $this->drupalGet('admin/config/regional/date-time');
    $this->assertText('Test date format');
    $this->drupalGet('admin/config/regional/date-time/formats/manage/test_date_format');
    $this->assertOptionSelected('edit-langcode', 'es');

    // Make sure our custom image style exists.
    $this->drupalGet('admin/config/media/image-styles/manage/test_image_style');
    $this->assertText('Test image style');
    $this->assertText('Desaturate');
    $this->assertText('Convert PNG');

    // Make sure our custom responsive image style exists.
    $this->drupalGet('admin/config/media/responsive-image-style/test');
    $this->assertResponse(200);
    $this->assertText('Test');

    // Make sure our custom shortcut exists.
    $this->drupalGet('admin/config/user-interface/shortcut');
    $this->assertText('Test shortcut');
    $this->drupalGet('admin/config/user-interface/shortcut/manage/test/customize');
    $this->assertText('All content');

    // Make sure our language detection settings are still correct.
    $this->drupalGet('admin/config/regional/language/detection');
    $this->assertFieldChecked('edit-language-interface-enabled-language-user-admin');
    $this->assertFieldChecked('edit-language-interface-enabled-language-url');
    $this->assertFieldChecked('edit-language-interface-enabled-language-session');
    $this->assertFieldChecked('edit-language-interface-enabled-language-user');
    $this->assertFieldChecked('edit-language-interface-enabled-language-browser');

    // Make sure strings are still translated.
    $this->drupalGet('admin/structure/views/view/content/translate/es/edit');
    $this->assertText('Contenido');
    $this->drupalPostForm('admin/config/regional/translate', ['string' => 'Full comment'], 'Filter');
    $this->assertText('Comentario completo');

    // Make sure our custom action is still there.
    $this->drupalGet('admin/config/system/actions');
    $this->assertText('Test action');
    $this->drupalGet('admin/config/system/actions/configure/test_action');
    $this->assertText('test_action');
    $this->assertRaw('drupal.org');

    // Make sure our ban still exists.
    $this->drupalGet('admin/config/people/ban');
    $this->assertText('8.8.8.8');

    // Make sure our vocabulary exists.
    $this->drupalGet('admin/structure/taxonomy/manage/test_vocabulary/overview');

    // Make sure our terms exist.
    $this->assertText('Test root term');
    $this->assertText('Test child term');
    $this->drupalGet('taxonomy/term/3');
    $this->assertResponse('200');

    // Make sure the terms are still translated.
    $this->drupalGet('taxonomy/term/2/translations');
    $this->assertLink('Test root term - Spanish');

    // Make sure our contact form exists.
    $this->drupalGet('admin/structure/contact');
    $this->assertText('Test contact form');
    $this->drupalGet('admin/structure/contact/manage/test_contact_form');
    $this->assertText('test@example.com');
    $this->assertText('Hello');
    $this->drupalGet('admin/structure/contact/manage/test_contact_form/translate/es/edit');
    $this->assertText('Hola');
    $this->assertRaw('Test contact form Spanish');

    // Make sure our modules are still enabled.
    $expected_enabled_modules = [
      'action',
      'aggregator',
      'ban',
      'basic_auth',
      'block',
      'block_content',
      'book',
      'breakpoint',
      'ckeditor',
      'color',
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
      'hal',
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
      'quickedit',
      'rdf',
      'responsive_image',
      'rest',
      'search',
      'serialization',
      'shortcut',
      'simpletest',
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
      'bartik',
      'classy',
      'seven',
      'stark',
    ];
    foreach ($expected_enabled_themes as $theme) {
      $this->assertTrue($this->container->get('theme_handler')->themeExists($theme), 'The "' . $theme . '" is still enabled.');
    }

    // Ensure that the Book module's node type does not have duplicated enforced
    // dependencies.
    // @see system_post_update_fix_enforced_dependencies()
    $book_node_type = NodeType::load('book');
    $this->assertEqual(['enforced' => ['module' => ['book']]], $book_node_type->get('dependencies'));
  }

  /**
   * {@inheritdoc}
   */
  protected function replaceUser1() {
    // Do not replace the user from our dump.
  }

}
