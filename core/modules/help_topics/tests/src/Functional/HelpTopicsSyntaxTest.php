<?php

namespace Drupal\Tests\help_topics\Functional;

use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\Component\FrontMatter\FrontMatter;
use Drupal\Tests\BrowserTestBase;
use Drupal\help_topics\HelpTopicDiscovery;
use Drupal\help_topics_twig_tester\HelpTestTwigNodeVisitor;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Verifies that all core Help topics can be rendered and comply with standards.
 *
 * @todo This test should eventually be folded into
 * Drupal\Tests\system\Functional\Module\InstallUninstallTest
 * when help_topics becomes stable, so that it will test with only one module
 * at a time installed and not duplicate the effort of installing. See issue
 * https://www.drupal.org/project/drupal/issues/3074040
 *
 * @group help_topics
 */
class HelpTopicsSyntaxTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'help',
    'help_topics',
    'help_topics_twig_tester',
    'locale',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that all Core help topics can be rendered and have good syntax.
   */
  public function testHelpTopics() {
    $this->drupalLogin($this->rootUser);

    // Enable all modules and themes, so that all routes mentioned in topics
    // will be defined.
    $module_directories = $this->listDirectories('module');
    $modules_to_install = array_keys($module_directories);
    \Drupal::service('module_installer')->install($modules_to_install);
    $theme_directories = $this->listDirectories('theme');
    \Drupal::service('theme_installer')->install(array_keys($theme_directories));

    $directories = $module_directories + $theme_directories +
      $this->listDirectories('profile');
    $directories['core'] = \Drupal::root() . '/core/help_topics';
    $directories['bad_help_topics'] = \Drupal::service('extension.list.module')->getPath('help_topics_test') . '/bad_help_topics/syntax/';

    // Filter out directories outside of core. If you want to run this test
    // on a contrib/custom module, remove the next line.
    $directories = array_filter($directories, function ($directory) {
      return str_starts_with($directory, 'core');
    });

    // Verify that a few key modules, themes, and profiles are listed, so that
    // we can be certain our directory list is complete and we will be testing
    // all existing help topics. If these lines in the test fail in the future,
    // it is probably because something we chose to list here is being removed.
    // Substitute another item of the same type that still exists, so that this
    // test can continue.
    $this->assertArrayHasKey('system', $directories, 'System module is being scanned');
    $this->assertArrayHasKey('help', $directories, 'Help module is being scanned');
    $this->assertArrayHasKey('claro', $directories, 'Claro theme is being scanned');
    $this->assertArrayHasKey('standard', $directories, 'Standard profile is being scanned');

    $definitions = (new HelpTopicDiscovery($directories))->getDefinitions();
    $this->assertGreaterThan(0, count($definitions), 'At least 1 topic was found');

    // Test each topic for compliance with standards, or for failing in the
    // right way.
    foreach (array_keys($definitions) as $id) {
      if (str_starts_with($id, 'bad_help_topics.')) {
        $this->verifyBadTopic($id, $definitions);
      }
      else {
        $this->verifyTopic($id, $definitions);
      }
    }
  }

  /**
   * Verifies rendering and standards compliance of one help topic.
   *
   * @param string $id
   *   ID of the topic to verify.
   * @param array $definitions
   *   Array of all topic definitions, keyed by ID.
   * @param int $response
   *   Expected response from visiting the page for the topic.
   */
  protected function verifyTopic($id, $definitions, $response = 200) {
    $definition = $definitions[$id];
    HelpTestTwigNodeVisitor::setStateValue('manner', 0);

    // Visit the URL for the topic.
    $this->drupalGet('admin/help/topic/' . $id);

    // Verify the title and response.
    $session = $this->assertSession();
    $session->statusCodeEquals($response);
    if ($response == 200) {
      $session->titleEquals($definition['label'] . ' | Drupal');
    }

    // Verify that all the related topics exist. Also check to see if any of
    // them are top-level (we will need that in the next section).
    $has_top_level_related = FALSE;
    if (isset($definition['related'])) {
      foreach ($definition['related'] as $related_id) {
        $this->assertArrayHasKey($related_id, $definitions, 'Topic ' . $id . ' is only related to topics that exist: ' . $related_id);
        $has_top_level_related = $has_top_level_related || !empty($definitions[$related_id]['top_level']);
      }
    }

    // Verify this is either top-level or related to a top-level topic.
    $this->assertTrue(!empty($definition['top_level']) || $has_top_level_related, 'Topic ' . $id . ' is either top-level or related to at least one other top-level topic');

    // Verify that the label is not empty.
    $this->assertNotEmpty($definition['label'], 'Topic ' . $id . ' has a non-empty label');

    // Test the syntax and contents of the Twig file (without the front
    // matter, which is tested in other ways above). We need to render the
    // template several times with variations, so read it in once.
    $template = file_get_contents($definition[HelpTopicDiscovery::FILE_KEY]);
    $template_text = FrontMatter::create($template)->getContent();

    // Verify that the body is not empty and is valid HTML.
    $text = $this->renderHelpTopic($template_text, 'bare_body');
    $this->assertNotEmpty($text, 'Topic ' . $id . ' contains some text outside of front matter');
    $this->validateHtml($text, $id);
    $max_chunk_num = HelpTestTwigNodeVisitor::getState()['max_chunk'];
    $this->assertTrue($max_chunk_num >= 0, 'Topic ' . $id . ' has at least one translated chunk');

    // Verify that each chunk of the translated text is locale-safe and
    // valid HTML.
    $chunk_num = 0;
    $number_checked = 0;
    while ($chunk_num <= $max_chunk_num) {
      $chunk_str = $id . ' section ' . $chunk_num;

      // Render the topic, asking for just one chunk, and extract the chunk.
      // Note that some chunks may not actually get rendered, if they are inside
      // set statements, because we skip rendering variable output.
      HelpTestTwigNodeVisitor::setStateValue('return_chunk', $chunk_num);
      $text = $this->renderHelpTopic($template_text, 'translated_chunk');
      $matches = [];
      $matched = preg_match('|' . HelpTestTwigNodeVisitor::DELIMITER . '(.*)' . HelpTestTwigNodeVisitor::DELIMITER . '|', $text, $matches);
      if ($matched) {
        $number_checked++;
        $text = $matches[1];
        $this->assertNotEmpty($text, 'Topic ' . $chunk_str . ' contains text');

        // Verify the chunk is OK.
        $this->assertTrue(locale_string_is_safe($text), 'Topic ' . $chunk_str . ' translatable string is locale-safe');
        $this->validateHtml($text, $chunk_str);
      }
      $chunk_num++;
    }
    $this->assertTrue($number_checked > 0, 'Tested at least one translated chunk in ' . $id);

    // Validate the HTML in the body with the translated text replaced by a
    // dummy string, to verify that HTML syntax is not partly in and partly out
    // of the translated text.
    $text = $this->renderHelpTopic($template_text, 'replace_translated');
    $this->validateHtml($text, $id);

    // Verify that if we remove all the translated text, whitespace, and
    // HTML tags, there is nothing left (that is, all text is translated).
    $text = preg_replace('|\s+|', '', $this->renderHelpTopic($template_text, 'remove_translated'));
    $this->assertEmpty($text, 'Topic ' . $id . ' Twig file has all of its text translated');
  }

  /**
   * Validates the HTML and header hierarchy for topic text.
   *
   * @param string $body
   *   Body text to validate.
   * @param string $id
   *   ID of help topic (for error messages).
   */
  protected function validateHtml(string $body, string $id) {
    $doc = new \DOMDocument();
    $doc->strictErrorChecking = TRUE;
    $doc->validateOnParse = FALSE;
    libxml_use_internal_errors(TRUE);
    if (!$doc->loadXML('<html><body>' . $body . '</body></html>')) {
      foreach (libxml_get_errors() as $error) {
        $this->fail('Topic ' . $id . ' fails HTML validation: ' . $error->message);
      }

      libxml_clear_errors();
    }

    // Check for headings hierarchy.
    $levels = [1, 2, 3, 4, 5, 6];
    foreach ($levels as $level) {
      $num_headings[$level] = $doc->getElementsByTagName('h' . $level)->length;
      if ($level == 1) {
        $this->assertSame(0, $num_headings[1], 'Topic ' . $id . ' has no H1 tag');
        // Set num_headings to 1 for this level, so the rest of the hierarchy
        // can be tested using simpler code.
        $num_headings[1] = 1;
      }
      else {
        // We should either not have this heading, or if we do have one at this
        // level, we should also have the next-smaller level. That is, if we
        // have an h3, we should have also had an h2.
        $this->assertTrue($num_headings[$level - 1] > 0 || $num_headings[$level] == 0,
          'Topic ' . $id . ' has the correct H2-H6 heading hierarchy');
      }
    }
  }

  /**
   * Verifies that a bad topic fails in the expected way.
   *
   * @param string $id
   *   ID of the topic to verify. It should start with "bad_help_topics.".
   * @param array $definitions
   *   Array of all topic definitions, keyed by ID.
   */
  protected function verifyBadTopic($id, $definitions) {
    $bad_topic_type = substr($id, 16);
    // Topics should fail verifyTopic() in specific ways.
    $found_error = FALSE;
    try {
      $this->verifyTopic($id, $definitions, 404);
    }
    catch (ExpectationFailedException | AssertionFailedError $e) {
      $found_error = TRUE;
      $message = $e->getMessage();
      switch ($bad_topic_type) {
        case 'related':
          $this->assertStringContainsString('only related to topics that exist', $message);
          break;

        case 'bad_html':
        case 'bad_html2':
        case 'bad_html3':
          $this->assertStringContainsString('Opening and ending tag mismatch', $message);
          break;

        case 'top_level':
          $this->assertStringContainsString('is either top-level or related to at least one other top-level topic', $message);
          break;

        case 'empty':
          $this->assertStringContainsString('contains some text outside of front matter', $message);
          break;

        case 'translated':
          $this->assertStringContainsString('Twig file has all of its text translated', $message);
          break;

        case 'locale':
          $this->assertStringContainsString('translatable string is locale-safe', $message);
          break;

        case 'h1':
          $this->assertStringContainsString('has no H1 tag', $message);
          break;

        case 'hierarchy':
          $this->assertStringContainsString('has the correct H2-H6 heading hierarchy', $message);
          break;

        default:
          // This was an unexpected error.
          throw $e;
      }
    }

    if (!$found_error) {
      $this->fail('Bad help topic ' . $bad_topic_type . ' did not fail as expected');
    }
  }

  /**
   * Lists the extension help topic directories of a certain type.
   *
   * @param string $type
   *   The type of extension to list: module, theme, or profile.
   *
   * @return string[]
   *   An array of all of the help topic directories for this type of
   *   extension, keyed by extension short name.
   */
  protected function listDirectories($type) {
    $directories = [];

    // Find the extensions of this type, even if they are not installed, but
    // excluding test ones.
    $lister = \Drupal::service('extension.list.' . $type);
    foreach ($lister->getAllAvailableInfo() as $name => $info) {
      // Skip obsolete and deprecated modules.
      if ($info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] === ExtensionLifecycle::OBSOLETE || $info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] === ExtensionLifecycle::DEPRECATED) {
        continue;
      }
      $path = $lister->getPath($name);
      // You can tell test modules because they are in package 'Testing', but
      // test themes are only known by being found in test directories. So...
      // exclude things in test directories.
      if (!str_contains($path, '/tests') && !str_contains($path, '/testing')) {
        $directories[$name] = $path . '/help_topics';
      }
    }
    return $directories;
  }

  /**
   * Renders a help topic in a special manner.
   *
   * @param string $content
   *   Template text, without the front matter.
   * @param string $manner
   *   The special processing choice for topic rendering.
   *
   * @return string
   *   The rendered topic.
   */
  protected function renderHelpTopic(string $content, string $manner) {
    // Set up the special state variables for rendering.
    HelpTestTwigNodeVisitor::setStateValue('manner', $manner);
    HelpTestTwigNodeVisitor::setStateValue('max_chunk', -1);
    HelpTestTwigNodeVisitor::setStateValue('chunk_count', -1);

    // Add a random comment to the end, to thwart caching, and render. We need
    // the HelpTestTwigNodeVisitor class to hit it each time we render.
    $build = [
      '#type' => 'inline_template',
      '#template' => $content . "\n{# " . rand() . " #}",
    ];
    return (string) \Drupal::service('renderer')->renderPlain($build);
  }

}
