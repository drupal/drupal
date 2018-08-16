<?php

namespace Drupal\Tests\language\Functional;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests that URL rewriting works as expected.
 *
 * @group language
 */
class LanguageUrlRewritingTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['language', 'language_test'];

  /**
   * An user with permissions to administer languages.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();

    // Create and log in user.
    $this->webUser = $this->drupalCreateUser(['administer languages', 'access administration pages']);
    $this->drupalLogin($this->webUser);

    // Install French language.
    $edit = [];
    $edit['predefined_langcode'] = 'fr';
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => 1];
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Check that drupalSettings contains path prefix.
    $this->drupalGet('fr/admin/config/regional/language/detection');
    $this->assertRaw('"pathPrefix":"fr\/"', 'drupalSettings path prefix contains language code.');
  }

  /**
   * Check that non-installed languages are not considered.
   */
  public function testUrlRewritingEdgeCases() {
    // Check URL rewriting with a non-installed language.
    $non_existing = new Language(['id' => $this->randomMachineName()]);
    $this->checkUrl($non_existing, 'Path language is ignored if language is not installed.', 'URL language negotiation does not work with non-installed languages');

    // Check that URL rewriting is not applied to subrequests.
    $this->drupalGet('language_test/subrequest');
    $this->assertText($this->webUser->getUsername(), 'Page correctly retrieved');
  }

  /**
   * Check URL rewriting for the given language.
   *
   * The test is performed with a fixed URL (the default front page) to simply
   * check that language prefixes are not added to it and that the prefixed URL
   * is actually not working.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   * @param string $message1
   *   Message to display in assertion that language prefixes are not added.
   * @param string $message2
   *   The message to display confirming prefixed URL is not working.
   */
  private function checkUrl(LanguageInterface $language, $message1, $message2) {
    $options = ['language' => $language, 'script' => ''];
    $base_path = trim(base_path(), '/');
    $rewritten_path = trim(str_replace($base_path, '', \Drupal::url('<front>', [], $options)), '/');
    $segments = explode('/', $rewritten_path, 2);
    $prefix = $segments[0];
    $path = isset($segments[1]) ? $segments[1] : $prefix;

    // If the rewritten URL has not a language prefix we pick a random prefix so
    // we can always check the prefixed URL.
    $prefixes = language_negotiation_url_prefixes();
    $stored_prefix = isset($prefixes[$language->getId()]) ? $prefixes[$language->getId()] : $this->randomMachineName();
    $this->assertNotEqual($stored_prefix, $prefix, $message1);
    $prefix = $stored_prefix;

    $this->drupalGet("$prefix/$path");
    $this->assertResponse(404, $message2);
  }

  /**
   * Check URL rewriting when using a domain name and a non-standard port.
   */
  public function testDomainNameNegotiationPort() {
    global $base_url;
    $language_domain = 'example.fr';
    // Get the current host URI we're running on.
    $base_url_host = parse_url($base_url, PHP_URL_HOST);
    $edit = [
      'language_negotiation_url_part' => LanguageNegotiationUrl::CONFIG_DOMAIN,
      'domain[en]' => $base_url_host,
      'domain[fr]' => $language_domain,
    ];
    $this->drupalPostForm('admin/config/regional/language/detection/url', $edit, t('Save configuration'));
    // Rebuild the container so that the new language gets picked up by services
    // that hold the list of languages.
    $this->rebuildContainer();

    // Enable domain configuration.
    $this->config('language.negotiation')
      ->set('url.source', LanguageNegotiationUrl::CONFIG_DOMAIN)
      ->save();

    // Reset static caching.
    $this->container->get('language_manager')->reset();

    // In case index.php is part of the URLs, we need to adapt the asserted
    // URLs as well.
    $index_php = strpos(\Drupal::url('<front>', [], ['absolute' => TRUE]), 'index.php') !== FALSE;

    $request = Request::createFromGlobals();
    $server = $request->server->all();
    $request = $this->prepareRequestForGenerator(TRUE, ['HTTP_HOST' => $server['HTTP_HOST'] . ':88']);

    // Create an absolute French link.
    $language = \Drupal::languageManager()->getLanguage('fr');
    $url = Url::fromRoute('<front>', [], [
      'absolute' => TRUE,
      'language' => $language,
    ])->toString();

    $expected = ($index_php ? 'http://example.fr:88/index.php' : 'http://example.fr:88') . rtrim(base_path(), '/') . '/';

    $this->assertEqual($url, $expected, 'The right port is used.');

    // If we set the port explicitly, it should not be overridden.
    $url = Url::fromRoute('<front>', [], [
      'absolute' => TRUE,
      'language' => $language,
      'base_url' => $request->getBaseUrl() . ':90',
    ])->toString();

    $expected = $index_php ? 'http://example.fr:90/index.php' : 'http://example.fr:90' . rtrim(base_path(), '/') . '/';

    $this->assertEqual($url, $expected, 'A given port is not overridden.');

  }

}
