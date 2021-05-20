<?php

namespace Drupal\Tests\help_topics\Functional;

use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\Tests\BrowserTestBase;
use Drupal\help_topics\HelpTopicDiscovery;
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
    'locale',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

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
      return strpos($directory, 'core') === 0;
    });

    // Verify that a few key modules, themes, and profiles are listed, so that
    // we can be certain our directory list is complete and we will be testing
    // all existing help topics. If these lines in the test fail in the future,
    // it is probably because something we chose to list here is being removed.
    // Substitute another item of the same type that still exists, so that this
    // test can continue.
    $this->assertArrayHasKey('system', $directories, 'System module is being scanned');
    $this->assertArrayHasKey('help', $directories, 'Help module is being scanned');
    $this->assertArrayHasKey('seven', $directories, 'Seven theme is being scanned');
    $this->assertArrayHasKey('standard', $directories, 'Standard profile is being scanned');

    $definitions = (new HelpTopicDiscovery($directories))->getDefinitions();
    $this->assertGreaterThan(0, count($definitions), 'At least 1 topic was found');

    // Test each topic for compliance with standards, or for failing in the
    // right way.
    foreach (array_keys($definitions) as $id) {
      if (strpos($id, 'bad_help_topics.') === 0) {
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
        $this->assertArrayHasKey($related_id, $definitions, 'Topic ' . $id . ' is only related to topics that exist (' . $related_id . ')');
        $has_top_level_related = $has_top_level_related || !empty($definitions[$related_id]['top_level']);
      }
    }

    // Verify this is either top-level or related to a top-level topic.
    $this->assertTrue(!empty($definition['top_level']) || $has_top_level_related, 'Topic ' . $id . ' is either top-level or related to at least one other top-level topic');

    // Verify that the label is not empty.
    $this->assertNotEmpty($definition['label'], 'Topic ' . $id . ' has a non-empty label');

    // Read in the file so we can run some tests on that.
    $body = file_get_contents($definition[HelpTopicDiscovery::FILE_KEY]);
    $this->assertNotEmpty($body, 'Topic ' . $id . ' has a non-empty Twig file');

    // Remove the front matter data (already tested above), and Twig set and
    // variable printouts from the file.
    $body = preg_replace('|---.*---|sU', '', $body);
    $body = preg_replace('|\{\{.*\}\}|sU', '', $body);
    $body = preg_replace('|\{\% set.*\%\}|sU', '', $body);
    $body = preg_replace('|\{\% endset \%\}|sU', '', $body);
    $body = trim($body);
    $this->assertNotEmpty($body, 'Topic ' . $id . ' Twig file contains some text outside of front matter');

    // Verify that if we remove all the translated text, whitespace, and
    // HTML tags, there is nothing left (that is, all text is translated).
    $text = preg_replace('|\{\% trans \%\}.*\{\% endtrans \%\}|sU', '', $body);
    $text = strip_tags($text);
    $text = preg_replace('|\s+|', '', $text);
    $this->assertEmpty($text, 'Topic ' . $id . ' Twig file has all of its text translated');

    // Verify that all of the translated text is locale-safe and valid HTML.
    $matches = [];
    preg_match_all('|\{\% trans \%\}(.*)\{\% endtrans \%\}|sU', $body, $matches, PREG_PATTERN_ORDER);
    foreach ($matches[1] as $string) {
      $this->assertTrue(locale_string_is_safe($string), 'Topic ' . $id . ' Twig file translatable strings are all exportable');
      $this->validateHtml($string, $id);
    }

    // Validate the HTML in the body as a whole.
    $this->validateHtml($body, $id);

    // Validate the HTML in the body with the translated text replaced by a
    // dummy string, to verify that HTML syntax is not partly in and partly out
    // of the translated text.
    $text = preg_replace('|\{\% trans \%\}.*\{\% endtrans \%\}|sU', 'dummy', $body);
    $this->validateHtml($text, $id);
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
      // Skip obsolete modules.
      if (isset($info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER]) && $info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] === ExtensionLifecycle::OBSOLETE) {
        continue;
      }
      $path = $lister->getPath($name);
      // You can tell test modules because they are in package 'Testing', but
      // test themes are only known by being found in test directories. So...
      // exclude things in test directories.
      if ((strpos($path, '/tests') === FALSE) &&
        (strpos($path, '/testing') === FALSE)) {
        $directories[$name] = $path . '/help_topics';
      }
    }
    return $directories;
  }

}
