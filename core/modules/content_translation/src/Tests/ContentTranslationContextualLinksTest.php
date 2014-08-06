<?php

/**
 * @file
 * Contains \Drupal\content_translation\Tests\ContentTranslationContextualLinksTest.
 */

namespace Drupal\content_translation\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\WebTestBase;

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
   * @var NodeType
   */
  protected $contentType;

  /**
   * The 'translator' user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $translator;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('content_translation', 'contextual', 'node');

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  function setUp() {
    parent::setUp();

    // Create a content type.
    $this->bundle = $this->randomMachineName();
    $this->contentType = $this->drupalCreateContentType(array('type' => $this->bundle));

    // Create a translator user.
    $permissions = array(
      'access contextual links',
      'administer nodes',
      "edit any $this->bundle content",
      'translate any entity',
    );
    $this->translator = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests that a contextual link is available for translating a node.
   */
  public function testContentTranslationContextualLinks() {
    // Create a node.
    $title = $this->randomString();
    $this->drupalCreateNode(array('type' => $this->bundle, 'title' => $title));
    $node = $this->drupalGetNodeByTitle($title);

    // Check that the translate link appears on the node page.
    $this->drupalLogin($this->translator);
    $translate_link = 'node/' . $node->id() . '/translations';

    $response = $this->renderContextualLinks(array('node:node=1:'), 'node/' . $node->id());
    $this->assertResponse(200);
    $json = Json::decode($response);
    $this->drupalSetContent($json['node:node=1:']);
    $this->assertLinkByHref($translate_link, 0, 'The contextual link to translate the node is shown.');

    // Check that the link leads to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw(t('Translations of %label', array('%label' => $node->label())), 'The contextual link leads to the translate page.');
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
    $post = array();
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
    return $this->curlExec(array(
      CURLOPT_URL => url('contextual/render', array('absolute' => TRUE, 'query' => array('destination' => $current_path))),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $post,
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
      ),
    ));
  }

}
