<?php

namespace Drupal\Tests\editor\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests XSS protection for content creators when using text editors.
 *
 * @group editor
 */
class EditorSecurityTest extends BrowserTestBase {

  /**
   * The sample content to use in all tests.
   *
   * @var string
   */
  protected static $sampleContent = '<p style="color: red">Hello, Dumbo Octopus!</p><script>alert(0)</script><embed type="image/svg+xml" src="image.svg" />';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected static $modules = ['filter', 'editor', 'editor_test', 'node'];

  /**
   * User with access to Restricted HTML text format without text editor.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $untrustedUser;

  /**
   * User with access to Restricted HTML text format with text editor.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * User with access to Restricted HTML text format, dangerous tags allowed
   * with text editor.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $trustedUser;

  /**
   * User with access to all text formats and text editors.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $privilegedUser;

  protected function setUp(): void {
    parent::setUp();

    // Create 5 text formats, to cover all potential use cases:
    // 1. restricted_without_editor (untrusted: anonymous)
    // 2. restricted_with_editor (normal: authenticated)
    // 3. restricted_plus_dangerous_tag_with_editor (privileged: trusted)
    // 4. unrestricted_without_editor (privileged: admin)
    // 5. unrestricted_with_editor (privileged: admin)
    // With text formats 2, 3 and 5, we also associate a text editor that does
    // not guarantee XSS safety. "restricted" means the text format has XSS
    // filters on output, "unrestricted" means the opposite.
    $format = FilterFormat::create([
      'format' => 'restricted_without_editor',
      'name' => 'Restricted HTML, without text editor',
      'weight' => 0,
      'filters' => [
        // A filter of the FilterInterface::TYPE_HTML_RESTRICTOR type.
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<h2> <h3> <h4> <h5> <h6> <p> <br> <strong> <a>',
          ],
        ],
      ],
    ]);
    $format->save();
    $format = FilterFormat::create([
      'format' => 'restricted_with_editor',
      'name' => 'Restricted HTML, with text editor',
      'weight' => 1,
      'filters' => [
        // A filter of the FilterInterface::TYPE_HTML_RESTRICTOR type.
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<h2> <h3> <h4> <h5> <h6> <p> <br> <strong> <a>',
          ],
        ],
      ],
    ]);
    $format->save();
    $editor = Editor::create([
      'format' => 'restricted_with_editor',
      'editor' => 'unicorn',
    ]);
    $editor->save();
    $format = FilterFormat::create([
      'format' => 'restricted_plus_dangerous_tag_with_editor',
      'name' => 'Restricted HTML, dangerous tag allowed, with text editor',
      'weight' => 1,
      'filters' => [
        // A filter of the FilterInterface::TYPE_HTML_RESTRICTOR type.
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<h2> <h3> <h4> <h5> <h6> <p> <br> <strong> <a> <embed>',
          ],
        ],
      ],
    ]);
    $format->save();
    $editor = Editor::create([
      'format' => 'restricted_plus_dangerous_tag_with_editor',
      'editor' => 'unicorn',
    ]);
    $editor->save();
    $format = FilterFormat::create([
      'format' => 'unrestricted_without_editor',
      'name' => 'Unrestricted HTML, without text editor',
      'weight' => 0,
      'filters' => [],
    ]);
    $format->save();
    $format = FilterFormat::create([
      'format' => 'unrestricted_with_editor',
      'name' => 'Unrestricted HTML, with text editor',
      'weight' => 1,
      'filters' => [],
    ]);
    $format->save();
    $editor = Editor::create([
      'format' => 'unrestricted_with_editor',
      'editor' => 'unicorn',
    ]);
    $editor->save();

    // Create node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    // Create 4 users, each with access to different text formats/editors:
    // - "untrusted": restricted_without_editor
    // - "normal": restricted_with_editor,
    // - "trusted": restricted_plus_dangerous_tag_with_editor
    // - "privileged": restricted_without_editor, restricted_with_editor,
    //   restricted_plus_dangerous_tag_with_editor,
    //   unrestricted_without_editor and unrestricted_with_editor
    $this->untrustedUser = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
      'use text format restricted_without_editor',
    ]);
    $this->normalUser = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
      'use text format restricted_with_editor',
    ]);
    $this->trustedUser = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
      'use text format restricted_plus_dangerous_tag_with_editor',
    ]);
    $this->privilegedUser = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
      'use text format restricted_without_editor',
      'use text format restricted_with_editor',
      'use text format restricted_plus_dangerous_tag_with_editor',
      'use text format unrestricted_without_editor',
      'use text format unrestricted_with_editor',
    ]);

    // Create an "article" node for each possible text format, with the same
    // sample content, to do our tests on.
    $samples = [
      ['author' => $this->untrustedUser->id(), 'format' => 'restricted_without_editor'],
      ['author' => $this->normalUser->id(), 'format' => 'restricted_with_editor'],
      ['author' => $this->trustedUser->id(), 'format' => 'restricted_plus_dangerous_tag_with_editor'],
      ['author' => $this->privilegedUser->id(), 'format' => 'unrestricted_without_editor'],
      ['author' => $this->privilegedUser->id(), 'format' => 'unrestricted_with_editor'],
    ];
    foreach ($samples as $sample) {
      $this->drupalCreateNode([
        'type' => 'article',
        'body' => [
          ['value' => self::$sampleContent, 'format' => $sample['format']],
        ],
        'uid' => $sample['author'],
      ]);
    }
  }

  /**
   * Tests initial security: is the user safe without switching text formats?
   *
   * Tests 8 scenarios. Tests only with a text editor that is not XSS-safe.
   */
  public function testInitialSecurity() {
    $expected = [
      [
        'node_id' => 1,
        'format' => 'restricted_without_editor',
        // No text editor => no XSS filtering.
        'value' => self::$sampleContent,
        'users' => [
          $this->untrustedUser,
          $this->privilegedUser,
        ],
      ],
      [
        'node_id' => 2,
        'format' => 'restricted_with_editor',
        // Text editor => XSS filtering.
        'value' => self::$sampleContentSecured,
        'users' => [
          $this->normalUser,
          $this->privilegedUser,
        ],
      ],
      [
        'node_id' => 3,
        'format' => 'restricted_plus_dangerous_tag_with_editor',
        // Text editor => XSS filtering.
        'value' => self::$sampleContentSecuredEmbedAllowed,
        'users' => [
          $this->trustedUser,
          $this->privilegedUser,
        ],
      ],
      [
        'node_id' => 4,
        'format' => 'unrestricted_without_editor',
        // No text editor => no XSS filtering.
        'value' => self::$sampleContent,
        'users' => [
          $this->privilegedUser,
        ],
      ],
      [
        'node_id' => 5,
        'format' => 'unrestricted_with_editor',
        // Text editor, no security filter => no XSS filtering.
        'value' => self::$sampleContent,
        'users' => [
          $this->privilegedUser,
        ],
      ],
    ];

    // Log in as each user that may edit the content, and assert the value.
    foreach ($expected as $case) {
      foreach ($case['users'] as $account) {
        $this->drupalLogin($account);
        $this->drupalGet('node/' . $case['node_id'] . '/edit');
        // Verify that the value is correctly filtered for XSS attack vectors.
        $this->assertSession()->fieldValueEquals('edit-body-0-value', $case['value']);
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
  public function testSwitchingSecurity() {
    $expected = [
      [
        'node_id' => 1,
        // No text editor => no XSS filtering.
        'value' => self::$sampleContent,
        'format' => 'restricted_without_editor',
        'switch_to' => [
          'restricted_with_editor' => self::$sampleContentSecured,
          // Intersection of restrictions => most strict XSS filtering.
          'restricted_plus_dangerous_tag_with_editor' => self::$sampleContentSecured,
          // No text editor => no XSS filtering.
          'unrestricted_without_editor' => FALSE,
          'unrestricted_with_editor' => self::$sampleContentSecured,
        ],
      ],
      [
        'node_id' => 2,
        // Text editor => XSS filtering.
        'value' => self::$sampleContentSecured,
        'format' => 'restricted_with_editor',
        'switch_to' => [
          // No text editor => no XSS filtering.
          'restricted_without_editor' => FALSE,
          // Intersection of restrictions => most strict XSS filtering.
          'restricted_plus_dangerous_tag_with_editor' => self::$sampleContentSecured,
          // No text editor => no XSS filtering.
          'unrestricted_without_editor' => FALSE,
          'unrestricted_with_editor' => self::$sampleContentSecured,
        ],
      ],
      [
        'node_id' => 3,
        // Text editor => XSS filtering.
        'value' => self::$sampleContentSecuredEmbedAllowed,
        'format' => 'restricted_plus_dangerous_tag_with_editor',
        'switch_to' => [
          // No text editor => no XSS filtering.
          'restricted_without_editor' => FALSE,
          // Intersection of restrictions => most strict XSS filtering.
          'restricted_with_editor' => self::$sampleContentSecured,
          // No text editor => no XSS filtering.
          'unrestricted_without_editor' => FALSE,
          // Intersection of restrictions => most strict XSS filtering.
          'unrestricted_with_editor' => self::$sampleContentSecured,
        ],
      ],
      [
        'node_id' => 4,
        // No text editor => no XSS filtering.
        'value' => self::$sampleContent,
        'format' => 'unrestricted_without_editor',
        'switch_to' => [
          // No text editor => no XSS filtering.
          'restricted_without_editor' => FALSE,
          'restricted_with_editor' => self::$sampleContentSecured,
          // Intersection of restrictions => most strict XSS filtering.
          'restricted_plus_dangerous_tag_with_editor' => self::$sampleContentSecured,
          // From no editor, no security filters, to editor, still no security
          // filters: resulting content when viewed was already vulnerable, so
          // it must be intentional.
          'unrestricted_with_editor' => FALSE,
        ],
      ],
      [
        'node_id' => 5,
        // Text editor => XSS filtering.
        'value' => self::$sampleContentSecured,
        'format' => 'unrestricted_with_editor',
        'switch_to' => [
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
        ],
      ],
    ];

    // Log in as the privileged user, and for every sample, do the following:
    // - switch to every other text format/editor
    // - assert the XSS-filtered values that we get from the server
    $this->drupalLogin($this->privilegedUser);
    $cookies = $this->getSessionCookies();

    foreach ($expected as $case) {
      $this->drupalGet('node/' . $case['node_id'] . '/edit');

      // Verify data- attributes.
      $body = $this->assertSession()->fieldExists('edit-body-0-value');
      $this->assertSame(self::$sampleContent, $body->getAttribute('data-editor-value-original'), 'The data-editor-value-original attribute is correctly set.');
      $this->assertSame('false', (string) $body->getAttribute('data-editor-value-is-changed'), 'The data-editor-value-is-changed attribute is correctly set.');

      // Switch to every other text format/editor and verify the results.
      foreach ($case['switch_to'] as $format => $expected_filtered_value) {
        $post = [
          'value' => self::$sampleContent,
          'original_format_id' => $case['format'],
        ];
        $client = $this->getHttpClient();
        $response = $client->post($this->buildUrl('/editor/filter_xss/' . $format), [
          'body' => http_build_query($post),
          'cookies' => $cookies,
          'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
          ],
          'http_errors' => FALSE,
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $json = Json::decode($response->getBody());
        $this->assertSame($expected_filtered_value, $json, 'The value was correctly filtered for XSS attack vectors.');
      }
    }
  }

  /**
   * Tests the standard text editor XSS filter being overridden.
   */
  public function testEditorXssFilterOverride() {
    // First: the Standard text editor XSS filter.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet('node/2/edit');
    $this->assertSession()->fieldValueEquals('edit-body-0-value', self::$sampleContentSecured);

    // Enable editor_test.module's hook_editor_xss_filter_alter() implementation
    // to alter the text editor XSS filter class being used.
    \Drupal::state()->set('editor_test_editor_xss_filter_alter_enabled', TRUE);

    // First: the Insecure text editor XSS filter.
    $this->drupalGet('node/2/edit');
    $this->assertSession()->fieldValueEquals('edit-body-0-value', self::$sampleContent);
  }

}
