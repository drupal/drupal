<?php

namespace Drupal\Tests\editor\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests administration of text editors.
 *
 * @group editor
 */
class EditorAdminTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'editor'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with the 'administer filters' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    // Add text format.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => [],
    ]);
    $filtered_html_format->save();

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser(['administer filters']);
  }

  /**
   * Tests an existing format without any editors available.
   */
  public function testNoEditorAvailable() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');

    // Ensure the form field order is correct.
    $raw_content = $this->getSession()->getPage()->getContent();
    $roles_pos = strpos($raw_content, 'Roles');
    $editor_pos = strpos($raw_content, 'Text editor');
    $filters_pos = strpos($raw_content, 'Enabled filters');
    $this->assertTrue($roles_pos < $editor_pos && $editor_pos < $filters_pos, '"Text Editor" select appears in the correct location of the text format configuration UI.');

    // Verify the <select>.
    $select = $this->xpath('//select[@name="editor[editor]"]');
    $select_is_disabled = $this->xpath('//select[@name="editor[editor]" and @disabled="disabled"]');
    $options = $this->xpath('//select[@name="editor[editor]"]/option');
    $this->assertTrue(count($select) === 1, 'The Text Editor select exists.');
    $this->assertTrue(count($select_is_disabled) === 1, 'The Text Editor select is disabled.');
    $this->assertTrue(count($options) === 1, 'The Text Editor select has only one option.');
    $this->assertTrue(($options[0]->getText()) === 'None', 'Option 1 in the Text Editor select is "None".');
    $this->assertRaw('This option is disabled because no modules that provide a text editor are currently enabled.', 'Description for select present that tells users to install a text editor module.');
  }

  /**
   * Tests adding a text editor to an existing text format.
   */
  public function testAddEditorToExistingFormat() {
    $this->enableUnicornEditor();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $edit = $this->selectUnicornEditor();
    // Configure Unicorn Editor's setting to another value.
    $edit['editor[settings][ponies_too]'] = FALSE;
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->verifyUnicornEditorConfiguration('filtered_html', FALSE);

    // Switch back to 'None' and check the Unicorn Editor's settings are gone.
    $edit = [
      'editor[editor]' => '',
    ];
    $this->drupalPostForm(NULL, $edit, 'Configure');
    $unicorn_setting = $this->xpath('//input[@name="editor[settings][ponies_too]" and @type="checkbox" and @checked]');
    $this->assertTrue(count($unicorn_setting) === 0, "Unicorn Editor's settings form is no longer present.");
  }

  /**
   * Tests adding a text editor to a new text format.
   */
  public function testAddEditorToNewFormat() {
    $this->addEditorToNewFormat('monoceros', 'Monoceros');
    $this->verifyUnicornEditorConfiguration('monoceros');
  }

  /**
   * Tests format disabling.
   */
  public function testDisableFormatWithEditor() {
    $formats = ['monoceros' => 'Monoceros', 'tattoo' => 'Tattoo'];

    // Install the node module.
    $this->container->get('module_installer')->install(['node']);
    $this->resetAll();
    // Create a new node type and attach the 'body' field to it.
    $node_type = NodeType::create(['type' => mb_strtolower($this->randomMachineName())]);
    $node_type->save();
    node_add_body_field($node_type, $this->randomString());

    $permissions = ['administer filters', "edit any {$node_type->id()} content"];
    foreach ($formats as $format => $name) {
      // Create a format and add an editor to this format.
      $this->addEditorToNewFormat($format, $name);
      // Add permission for this format.
      $permissions[] = "use text format $format";
    }

    // Create a node having the body format value 'monoceros'.
    $node = Node::create([
      'type' => $node_type->id(),
      'title' => $this->randomString(),
    ]);
    $node->body->value = $this->randomString(100);
    $node->body->format = 'monoceros';
    $node->save();

    // Log in as an user able to use both formats and edit nodes of created type.
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // The node edit page header.
    $text = (string) new FormattableMarkup('<em>Edit @type</em> @title', ['@type' => $node_type->label(), '@title' => $node->label()]);

    // Go to node edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertRaw($text);

    // Disable the format assigned to the 'body' field of the node.
    FilterFormat::load('monoceros')->disable()->save();

    // Edit again the node.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertRaw($text);
  }

  /**
   * Adds an editor to a new format using the UI.
   *
   * @param string $format_id
   *   The format id.
   * @param string $format_name
   *   The format name.
   */
  protected function addEditorToNewFormat($format_id, $format_name) {
    $this->enableUnicornEditor();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/formats/add');
    // Configure the text format name.
    $edit = [
      'name' => $format_name,
      'format' => $format_id,
    ];
    $edit += $this->selectUnicornEditor();
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
  }

  /**
   * Enables the unicorn editor.
   */
  protected function enableUnicornEditor() {
    if (!$this->container->get('module_handler')->moduleExists('editor_test')) {
      $this->container->get('module_installer')->install(['editor_test']);
    }
  }

  /**
   * Tests and selects the unicorn editor.
   *
   * @return array
   *   Returns an edit array containing the values to be posted.
   */
  protected function selectUnicornEditor() {
    // Verify the <select> when a text editor is available.
    $select = $this->xpath('//select[@name="editor[editor]"]');
    $select_is_disabled = $this->xpath('//select[@name="editor[editor]" and @disabled="disabled"]');
    $options = $this->xpath('//select[@name="editor[editor]"]/option');
    $this->assertTrue(count($select) === 1, 'The Text Editor select exists.');
    $this->assertTrue(count($select_is_disabled) === 0, 'The Text Editor select is not disabled.');
    $this->assertTrue(count($options) === 2, 'The Text Editor select has two options.');
    $this->assertTrue(($options[0]->getText()) === 'None', 'Option 1 in the Text Editor select is "None".');
    $this->assertTrue(($options[1]->getText()) === 'Unicorn Editor', 'Option 2 in the Text Editor select is "Unicorn Editor".');
    $this->assertTrue($options[0]->hasAttribute('selected'), 'Option 1 ("None") is selected.');
    // Ensure the none option is selected.
    $this->assertNoRaw('This option is disabled because no modules that provide a text editor are currently enabled.', 'Description for select absent that tells users to install a text editor module.');

    // Select the "Unicorn Editor" editor and click the "Configure" button.
    $edit = [
      'editor[editor]' => 'unicorn',
    ];
    $this->drupalPostForm(NULL, $edit, 'Configure');
    $unicorn_setting = $this->xpath('//input[@name="editor[settings][ponies_too]" and @type="checkbox" and @checked]');
    $this->assertCount(1, $unicorn_setting, "Unicorn Editor's settings form is present.");

    return $edit;
  }

  /**
   * Verifies unicorn editor configuration.
   *
   * @param string $format_id
   *   The format machine name.
   * @param bool $ponies_too
   *   The expected value of the ponies_too setting.
   */
  protected function verifyUnicornEditorConfiguration($format_id, $ponies_too = TRUE) {
    $editor = editor_load($format_id);
    $settings = $editor->getSettings();
    $this->assertIdentical($editor->getEditor(), 'unicorn', 'The text editor is configured correctly.');
    $this->assertIdentical($settings['ponies_too'], $ponies_too, 'The text editor settings are stored correctly.');
    $this->drupalGet('admin/config/content/formats/manage/' . $format_id);
    $select = $this->xpath('//select[@name="editor[editor]"]');
    $select_is_disabled = $this->xpath('//select[@name="editor[editor]" and @disabled="disabled"]');
    $options = $this->xpath('//select[@name="editor[editor]"]/option');
    $this->assertTrue(count($select) === 1, 'The Text Editor select exists.');
    $this->assertTrue(count($select_is_disabled) === 0, 'The Text Editor select is not disabled.');
    $this->assertTrue(count($options) === 2, 'The Text Editor select has two options.');
    $this->assertTrue($options[1]->hasAttribute('selected'), 'Option 2 ("Unicorn Editor") is selected.');
  }

}
