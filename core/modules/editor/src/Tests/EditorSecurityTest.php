<?php

/**
 * @file
 * Definition of \Drupal\editor\Tests\EditorSecurityTest.
 */

namespace Drupal\editor\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\String;

/**
 * Tests XSS protection for content creators when using text editors.
 */
class EditorSecurityTest extends WebTestBase {

  /**
   * The sample content to use in all tests.
   *
   * @var string
   */
  protected static $sampleContent = '<p style="color: red">Hello, Dumbo Octopus!</p><script>alert(0)</script><embed type="image/svg+xml" src="image.svg" />';

  /**
   * The secured sample content to use in most tests.
   *
   * @var string
   */
  protected static $sampleContentSecured = '<p>Hello, Dumbo Octopus!</p>alert(0)';

  /**
   * The secured sample content to use in tests when the <embed> tag is allowed.
   *
   * @var string
   */
  protected static $sampleContentSecuredEmbedAllowed = '<p>Hello, Dumbo Octopus!</p>alert(0)<embed type="image/svg+xml" src="image.svg" />';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'editor', 'editor_test', 'node');

  public static function getInfo() {
    return array(
      'name' => 'Text editor security',
      'description' => 'Tests XSS protection for content creators when using text editors.',
      'group' => 'Text Editor',
    );
  }

  function setUp() {
    parent::setUp();

    // Create 5 text formats, to cover all potential use cases:
    //  1. restricted_without_editor (untrusted: anonymous)
    //  2. restricted_with_editor (normal: authenticated)
    //  3. restricted_plus_dangerous_tag_with_editor (privileged: trusted)
    //  4. unrestricted_without_editor (privileged: admin)
    //  5. unrestricted_with_editor (privileged: admin)
    // With text formats 2, 3 and 5, we also associate a text editor that does
    // not guarantee XSS safety. "restricted" means the text format has XSS
    // filters on output, "unrestricted" means the opposite.
    $format = entity_create('filter_format', array(
      'format' => 'restricted_without_editor',
      'name' => 'Restricted HTML, without text editor',
      'weight' => 0,
      'filters' => array(
        // A filter of the FilterInterface::TYPE_HTML_RESTRICTOR type.
        'filter_html' => array(
          'status' => 1,
          'settings' => array(
            'allowed_html' => '<h4> <h5> <h6> <p> <br> <strong> <a>',
          )
        ),
      ),
    ));
    $format->save();
    $format = entity_create('filter_format', array(
      'format' => 'restricted_with_editor',
      'name' => 'Restricted HTML, with text editor',
      'weight' => 1,
      'filters' => array(
        // A filter of the FilterInterface::TYPE_HTML_RESTRICTOR type.
        'filter_html' => array(
          'status' => 1,
          'settings' => array(
            'allowed_html' => '<h4> <h5> <h6> <p> <br> <strong> <a>',
          )
        ),
      ),
    ));
    $format->save();
    $editor = entity_create('editor', array(
      'format' => 'restricted_with_editor',
      'editor' => 'unicorn',
    ));
    $editor->save();
    $format = entity_create('filter_format', array(
      'format' => 'restricted_plus_dangerous_tag_with_editor',
      'name' => 'Restricted HTML, dangerous tag allowed, with text editor',
      'weight' => 1,
      'filters' => array(
        // A filter of the FilterInterface::TYPE_HTML_RESTRICTOR type.
        'filter_html' => array(
          'status' => 1,
          'settings' => array(
            'allowed_html' => '<h4> <h5> <h6> <p> <br> <strong> <a> <embed>',
          )
        ),
      ),
    ));
    $format->save();
    $editor = entity_create('editor', array(
      'format' => 'restricted_plus_dangerous_tag_with_editor',
      'editor' => 'unicorn',
    ));
    $editor->save();
    $format = entity_create('filter_format', array(
      'format' => 'unrestricted_without_editor',
      'name' => 'Unrestricted HTML, without text editor',
      'weight' => 0,
      'filters' => array(),
    ));
    $format->save();
    $format = entity_create('filter_format', array(
      'format' => 'unrestricted_with_editor',
      'name' => 'Unrestricted HTML, with text editor',
      'weight' => 1,
      'filters' => array(),
    ));
    $format->save();
    $editor = entity_create('editor', array(
      'format' => 'unrestricted_with_editor',
      'editor' => 'unicorn',
    ));
    $editor->save();


    // Create node type.
    $this->drupalCreateContentType(array(
      'type' => 'article',
      'name' => 'Article',
    ));

    // Create 3 users, each with access to different text formats/editors:
    //   - "untrusted": restricted_without_editor
    //   - "normal": restricted_with_editor,
    //   - "trusted": restricted_plus_dangerous_tag_with_editor
    //   - "privileged": restricted_without_editor, restricted_with_editor,
    //     restricted_plus_dangerous_tag_with_editor,
    //     unrestricted_without_editor and unrestricted_with_editor
    $this->untrusted_user = $this->drupalCreateUser(array(
      'create article content',
      'edit any article content',
      'use text format restricted_without_editor',
    ));
    $this->normal_user = $this->drupalCreateUser(array(
      'create article content',
      'edit any article content',
      'use text format restricted_with_editor',
    ));
    $this->trusted_user = $this->drupalCreateUser(array(
      'create article content',
      'edit any article content',
      'use text format restricted_plus_dangerous_tag_with_editor',
    ));
    $this->privileged_user = $this->drupalCreateUser(array(
      'create article content',
      'edit any article content',
      'use text format restricted_without_editor',
      'use text format restricted_with_editor',
      'use text format restricted_plus_dangerous_tag_with_editor',
      'use text format unrestricted_without_editor',
      'use text format unrestricted_with_editor',
    ));

    // Create an "article" node for each possible text format, with the same
    // sample content, to do our tests on.
    $samples = array(
      array('author' => $this->untrusted_user->id(), 'format' => 'restricted_without_editor'),
      array('author' => $this->normal_user->id(), 'format' => 'restricted_with_editor'),
      array('author' => $this->trusted_user->id(), 'format' => 'restricted_plus_dangerous_tag_with_editor'),
      array('author' => $this->privileged_user->id(), 'format' => 'unrestricted_without_editor'),
      array('author' => $this->privileged_user->id(), 'format' => 'unrestricted_with_editor'),
    );
    foreach ($samples as $sample) {
      $this->drupalCreateNode(array(
        'type' => 'article',
        'body' => array(
          array('value' => self::$sampleContent, 'format' => $sample['format'])
        ),
        'uid' => $sample['author']
      ));
    }
  }

  /**
   * Tests initial security: is the user safe without switching text formats?
   *
   * Tests 8 scenarios. Tests only with a text editor that is not XSS-safe.
   */
  function testInitialSecurity() {
    $expected = array(
      array(
        'node_id' => 1,
        'format' => 'restricted_without_editor',
        // No text editor => no XSS filtering.
        'value' => self::$sampleContent,
        'users' => array(
          $this->untrusted_user,
          $this->privileged_user,
        ),
      ),
      array(
        'node_id' => 2,
        'format' => 'restricted_with_editor',
        // Text editor => XSS filtering.
        'value' => self::$sampleContentSecured,
        'users' => array(
          $this->normal_user,
          $this->privileged_user,
        ),
      ),
      array(
        'node_id' => 3,
        'format' => 'restricted_plus_dangerous_tag_with_editor',
        // Text editor => XSS filtering.
        'value' => self::$sampleContentSecuredEmbedAllowed,
        'users' => array(
          $this->trusted_user,
          $this->privileged_user,
        ),
      ),
      array(
        'node_id' => 4,
        'format' => 'unrestricted_without_editor',
        // No text editor => no XSS filtering.
        'value' => self::$sampleContent,
        'users' => array(
          $this->privileged_user,
        ),
      ),
      array(
        'node_id' => 5,
        'format' => 'unrestricted_with_editor',
        // Text editor, no security filter => no XSS filtering.
        'value' => self::$sampleContent,
        'users' => array(
          $this->privileged_user,
        ),
      ),
    );

    // Log in as each user that may edit the content, and assert the value.
    foreach ($expected as $case) {
      foreach ($case['users'] as $account) {
        $this->pass(format_string('Scenario: sample %sample_id, %format.', array(
          '%sample_id' => $case['node_id'],
          '%format' => $case['format'],
        )));
        $this->drupalLogin($account);
        $this->drupalGet('node/' . $case['node_id'] . '/edit');
        $dom_node = $this->xpath('//textarea[@id="edit-body-0-value"]');
        $this->assertIdentical($case['value'], (string) $dom_node[0], 'The value was correctly filtered for XSS attack vectors.');
      }
    }
  }

  /**
   * Tests administrator security: is the user safe when switching text formats?
   *
   * Tests 24 scenarios. Tests only with a text editor that is not XSS-safe.
   *
   * When changing from a more restrictive text format with a text editor (or a
   * text format without a text editor) to a less restrictive text format, it is
   * possible that a malicious user could trigger an XSS.
   *
   * E.g. when switching a piece of text that uses the Restricted HTML text
   * format and contains a <script> tag to the Full HTML text format, the
   * <script> tag would be executed. Unless we apply appropriate filtering.
   */
  function testSwitchingSecurity() {
    $expected = array(
      array(
        'node_id' => 1,
        'value' => self::$sampleContent, // No text editor => no XSS filtering.
        'format' => 'restricted_without_editor',
        'switch_to' => array(
          'restricted_with_editor' => self::$sampleContentSecured,
          // Intersection of restrictions => most strict XSS filtering.
          'restricted_plus_dangerous_tag_with_editor' => self::$sampleContentSecured,
          // No text editor => no XSS filtering.
          'unrestricted_without_editor' => FALSE,
          'unrestricted_with_editor' => self::$sampleContentSecured,
        ),
      ),
      array(
        'node_id' => 2,
        'value' => self::$sampleContentSecured, // Text editor => XSS filtering.
        'format' => 'restricted_with_editor',
        'switch_to' => array(
          // No text editor => no XSS filtering.
          'restricted_without_editor' => FALSE,
          // Intersection of restrictions => most strict XSS filtering.
          'restricted_plus_dangerous_tag_with_editor' => self::$sampleContentSecured,
          // No text editor => no XSS filtering.
          'unrestricted_without_editor' => FALSE,
          'unrestricted_with_editor' => self::$sampleContentSecured,
        ),
      ),
      array(
        'node_id' => 3,
        'value' => self::$sampleContentSecuredEmbedAllowed, // Text editor => XSS filtering.
        'format' => 'restricted_plus_dangerous_tag_with_editor',
        'switch_to' => array(
          // No text editor => no XSS filtering.
          'restricted_without_editor' => FALSE,
          // Intersection of restrictions => most strict XSS filtering.
          'restricted_with_editor' => self::$sampleContentSecured,
          // No text editor => no XSS filtering.
          'unrestricted_without_editor' => FALSE,
          // Intersection of restrictions => most strict XSS filtering.
          'unrestricted_with_editor' => self::$sampleContentSecured,
        ),
      ),
      array(
        'node_id' => 4,
        'value' => self::$sampleContent, // No text editor => no XSS filtering.
        'format' => 'unrestricted_without_editor',
        'switch_to' => array(
          // No text editor => no XSS filtering.
          'restricted_without_editor' => FALSE,
          'restricted_with_editor' => self::$sampleContentSecured,
          // Intersection of restrictions => most strict XSS filtering.
          'restricted_plus_dangerous_tag_with_editor' => self::$sampleContentSecured,
          // From no editor, no security filters, to editor, still no security
          // filters: resulting content when viewed was already vulnerable, so
          // it must be intentional.
          'unrestricted_with_editor' => FALSE,
        ),
      ),
      array(
        'node_id' => 5,
        'value' => self::$sampleContentSecured, // Text editor => XSS filtering.
        'format' => 'unrestricted_with_editor',
        'switch_to' => array(
          // From editor, no security filters to security filters, no editor: no
          // risk.
          'restricted_without_editor' => FALSE,
          'restricted_with_editor' => self::$sampleContentSecured,
          // Intersection of restrictions => most strict XSS filtering.
          'restricted_plus_dangerous_tag_with_editor' => self::$sampleContentSecured,
          // From no editor, no security filters, to editor, still no security
          // filters: resulting content when viewed was already vulnerable, so
          // it must be intentional.
          'unrestricted_without_editor' => FALSE,
        ),
      ),
    );

    // Log in as the privileged user, and for every sample, do the following:
    //  - switch to every other text format/editor
    //  - assert the XSS-filtered values that we get from the server
    $value_original_attribute = String::checkPlain(self::$sampleContent);
    $this->drupalLogin($this->privileged_user);
    foreach ($expected as $case) {
      $this->drupalGet('node/' . $case['node_id'] . '/edit');

      // Verify data- attributes.
      $dom_node = $this->xpath('//textarea[@id="edit-body-0-value"]');
      $this->assertIdentical(self::$sampleContent, (string) $dom_node[0]['data-editor-value-original'], 'The data-editor-value-original attribute is correctly set.');
      $this->assertIdentical('false', (string) $dom_node[0]['data-editor-value-is-changed'], 'The data-editor-value-is-changed attribute is correctly set.');

      // Switch to every other text format/editor and verify the results.
      foreach ($case['switch_to'] as $format => $expected_filtered_value) {
        $this->pass(format_string('Scenario: sample %sample_id, switch from %original_format to %format.', array(
          '%sample_id' => $case['node_id'],
          '%original_format' => $case['format'],
          '%format' => $format,
        )));
        $post = array(
          'value' => self::$sampleContent,
          'original_format_id' => $case['format'],
        );
        $response = $this->drupalPost('editor/filter_xss/' . $format, 'application/json', $post);
        $this->assertResponse(200);
        $json = Json::decode($response);
        $this->assertIdentical($json, $expected_filtered_value, 'The value was correctly filtered for XSS attack vectors.');
      }
    }
  }

  /**
   * Tests the standard text editor XSS filter being overridden.
   */
  function testEditorXssFilterOverride() {
    // First: the Standard text editor XSS filter.
    $this->drupalLogin($this->normal_user);
    $this->drupalGet('node/2/edit');
    $dom_node = $this->xpath('//textarea[@id="edit-body-0-value"]');
    $this->assertIdentical(self::$sampleContentSecured, (string) $dom_node[0], 'The value was filtered by the Standard text editor XSS filter.');

    // Enable editor_test.module's hook_editor_xss_filter_alter() implementation
    // to ater the text editor XSS filter class being used.
    \Drupal::state()->set('editor_test_editor_xss_filter_alter_enabled', TRUE);

    // First: the Insecure text editor XSS filter.
    $this->drupalGet('node/2/edit');
    $dom_node = $this->xpath('//textarea[@id="edit-body-0-value"]');
    $this->assertIdentical(self::$sampleContent, (string) $dom_node[0], 'The value was filtered by the Insecure text editor XSS filter.');
  }
}
