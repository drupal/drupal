<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeTypeTranslationTest.
 */

namespace Drupal\node\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Ensures that node types translation work correctly.
 *
 * Note that the child site is installed in French; therefore, when making
 * assertions on translated text it is important to provide a langcode. This
 * ensures the asserts pass regardless of the Drupal version.
 *
 * @group node
 */
class NodeTypeTranslationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'config_translation',
    'node',
  );

  /**
   * The default language code to use in this test.
   *
   * @var array
   */
  protected $defaultLangcode = 'fr';

  /**
   * Languages to enable.
   *
   * @var array
   */
  protected $additionalLangcodes = ['es'];

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    $admin_permissions = array(
      'administer content types',
      'administer site configuration',
      'administer themes',
      'translate configuration',
    );

    // Create and login user.
    $this->adminUser = $this->drupalCreateUser($admin_permissions);

    // Add languages.
    foreach ($this->additionalLangcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
  }

  /**
   * {@inheritdoc}
   *
   * Install Drupal in a language other than English for this test. This is not
   * needed to test the node type translation itself but acts as a regression
   * test.
   *
   * @see https://www.drupal.org/node/2584603
   */
  protected function installParameters() {
    $parameters = parent::installParameters();
    $parameters['parameters']['langcode'] = $this->defaultLangcode;
    return $parameters;
  }

  /**
   * Tests the node type translation.
   */
  public function testNodeTypeTranslation() {
    $type = Unicode::strtolower($this->randomMachineName(16));
    $name = $this->randomString();
    $this->drupalLogin($this->adminUser);
    $this->drupalCreateContentType(array('type' => $type, 'name' => $name));

    // Translate the node type name.
    $langcode = $this->additionalLangcodes[0];
    $translated_name = $langcode . '-' . $name;
    $edit = array(
      "translation[config_names][node.type.$type][name]" => $translated_name,
    );

    // Edit the title label to avoid having an exception when we save the translation.
    $this->drupalPostForm("admin/structure/types/manage/$type/translate/$langcode/add", $edit, t('Save translation'));

    // Check the name is translated without admin theme for editing.
    $this->drupalPostForm('admin/appearance', array('use_admin_theme' => '0'), t('Save configuration'));
    $this->drupalGet("$langcode/node/add/$type");
    // This is a Spanish page, so ensure the text asserted is translated in
    // Spanish and not French by adding the langcode option.
    $this->assertRaw(t('Create @name', array('@name' => $translated_name), array('langcode' => $langcode)));

    // Check the name is translated with admin theme for editing.
    $this->drupalPostForm('admin/appearance', array('use_admin_theme' => '1'), t('Save configuration'));
    $this->drupalGet("$langcode/node/add/$type");
    // This is a Spanish page, so ensure the text asserted is translated in
    // Spanish and not French by adding the langcode option.
    $this->assertRaw(t('Create @name', array('@name' => $translated_name), array('langcode' => $langcode)));
  }

  /**
   * Tests the node type title label translation.
   */
  public function testNodeTypeTitleLabelTranslation() {
    $type = Unicode::strtolower($this->randomMachineName(16));
    $name = $this->randomString();
    $this->drupalLogin($this->adminUser);
    $this->drupalCreateContentType(array('type' => $type, 'name' => $name));
    $langcode = $this->additionalLangcodes[0];

    // Edit the title label for it to be displayed on the translation form.
    $this->drupalPostForm("admin/structure/types/manage/$type", array('title_label' => 'Edited title'), t('Save content type'));

    // Assert that the title label is displayed on the translation form with the right value.
    $this->drupalGet("admin/structure/types/manage/$type/translate/$langcode/add");
    $this->assertText('Edited title');

    // Translate the title label.
    $this->drupalPostForm(NULL, array("translation[config_names][core.base_field_override.node.$type.title][label]" => 'Translated title'), t('Save translation'));

    // Assert that the right title label is displayed on the node add form. The
    // translations are created in this test; therefore, the assertions do not
    // use t(). If t() were used then the correct langcodes would need to be
    // provided.
    $this->drupalGet("node/add/$type");
    $this->assertText('Edited title');
    $this->drupalGet("$langcode/node/add/$type");
    $this->assertText('Translated title');
  }

}
