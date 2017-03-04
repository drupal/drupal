<?php

namespace Drupal\content_translation\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests that contextual links are available for content translation.
 *
 * @group content_translation
 */
class ContentTranslationContextualLinksTest extends WebTestBase {

  /**
   * The bundle being tested.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The content type being tested.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * The 'translator' user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $translator;

  /**
   * The enabled languages.
   *
   * @var array
   */
  protected $langcodes;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['content_translation', 'contextual', 'node'];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  protected function setUp() {
    parent::setUp();
    // Set up an additional language.
    $this->langcodes = [\Drupal::languageManager()->getDefaultLanguage()->getId(), 'es'];
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Create a content type.
    $this->bundle = $this->randomMachineName();
    $this->contentType = $this->drupalCreateContentType(['type' => $this->bundle]);

    // Add a field to the content type. The field is not yet translatable.
    FieldStorageConfig::create([
      'field_name' => 'field_test_text',
      'entity_type' => 'node',
      'type' => 'text',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test_text',
      'bundle' => $this->bundle,
      'label' => 'Test text-field',
    ])->save();
    entity_get_form_display('node', $this->bundle, 'default')
      ->setComponent('field_test_text', [
        'type' => 'text_textfield',
        'weight' => 0,
      ])
      ->save();

    // Create a translator user.
    $permissions = [
      'access contextual links',
      'administer nodes',
      "edit any $this->bundle content",
      'translate any entity',
    ];
    $this->translator = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests that a contextual link is available for translating a node.
   */
  public function testContentTranslationContextualLinks() {
    // Create a node.
    $title = $this->randomString();
    $this->drupalCreateNode(['type' => $this->bundle, 'title' => $title, 'langcode' => 'en']);
    $node = $this->drupalGetNodeByTitle($title);

    // Use a UI form submission to make the node type and field translatable.
    // This tests that caches are properly invalidated.
    $this->drupalLogin($this->rootUser);
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][' . $this->bundle . '][settings][language][language_alterable]' => TRUE,
      'settings[node][' . $this->bundle . '][translatable]' => TRUE,
      'settings[node][' . $this->bundle . '][fields][field_test_text]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save configuration'));
    $this->drupalLogout();

    // Check that the translate link appears on the node page.
    $this->drupalLogin($this->translator);
    $translate_link = 'node/' . $node->id() . '/translations';

    $response = $this->renderContextualLinks(['node:node=1:'], 'node/' . $node->id());
    $this->assertResponse(200);
    $json = Json::decode($response);
    $this->setRawContent($json['node:node=1:']);
    $this->assertLinkByHref($translate_link, 0, 'The contextual link to translate the node is shown.');

    // Check that the link leads to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw(t('Translations of %label', ['%label' => $node->label()]), 'The contextual link leads to the translate page.');
  }

  /**
   * Get server-rendered contextual links for the given contextual link ids.
   *
   * Copied from \Drupal\contextual\Tests\ContextualDynamicContextTest::renderContextualLinks().
   *
   * @param array $ids
   *   An array of contextual link ids.
   * @param string $current_path
   *   The Drupal path for the page for which the contextual links are rendered.
   *
   * @return string
   *   The response body.
   */
  protected function renderContextualLinks($ids, $current_path) {
    // Build POST values.
    $post = [];
    for ($i = 0; $i < count($ids); $i++) {
      $post['ids[' . $i . ']'] = $ids[$i];
    }

    // Serialize POST values.
    foreach ($post as $key => $value) {
      // Encode according to application/x-www-form-urlencoded
      // Both names and values needs to be urlencoded, according to
      // http://www.w3.org/TR/html4/interact/forms.html#h-17.13.4.1
      $post[$key] = urlencode($key) . '=' . urlencode($value);
    }
    $post = implode('&', $post);

    // Perform HTTP request.
    return $this->curlExec([
      CURLOPT_URL => \Drupal::url('contextual.render', [], ['absolute' => TRUE, 'query' => ['destination' => $current_path]]),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $post,
      CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
      ],
    ]);
  }

}
