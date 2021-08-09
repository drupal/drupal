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
  protected static $modules = ['language', 'language_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permissions to administer languages.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp(): void {
    parent::setUp();

    // Create and log in user.
    $this->webUser = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
    ]);
    $this->drupalLogin($this->webUser);

    // Install French language.
    $edit = [];
    $edit['predefined_langcode'] = 'fr';
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => 1];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // Check that drupalSettings contains path prefix.
    $this->drupalGet('fr/admin/config/regional/language/detection');
    $this->assertSession()->responseContains('"pathPrefix":"fr\/"');
  }

  /**
   * Check that non-installed languages are not considered.
   */
  public function testUrlRewritingEdgeCases() {
    // Check URL rewriting with a non-installed language.
    $non_existing = new Language(['id' => $this->randomMachineName()]);
    $this->checkUrl($non_existing, 'Path language is ignored if language is not installed.');

    // Check that URL rewriting is not applied to subrequests.
    $this->drupalGet('language_test/subrequest');
    $this->assertSession()->pageTextContains($this->webUser->getAccountName());
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
   * @param string $message
   *   Message to display in assertion that language prefixes are not added.
   */
  private function checkUrl(LanguageInterface $language, $message) {
    $options = ['language' => $language, 'script' => ''];
    $base_path = trim(base_path(), '/');
    $rewritten_path = trim(str_replace($base_path, '', Url::fromRoute('<front>', [], $options)->toString()), '/');
    $segments = explode('/', $rewritten_path, 2);
    $prefix = $segments[0];
    $path = isset($segments[1]) ? $segments[1] : $prefix;

    // If the rewritten URL has not a language prefix we pick a random prefix so
    // we can always check the prefixed URL.
    $prefixes = $this->config('language.negotiation')->get('url.prefixes');
    $stored_prefix = isset($prefixes[$language->getId()]) ? $prefixes[$language->getId()] : $this->randomMachineName();
    $this->assertNotEquals($prefix, $stored_prefix, $message);
    $prefix = $stored_prefix;

    $this->drupalGet("$prefix/$path");
    $this->assertSession()->statusCodeEquals(404);
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
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->submitForm($edit, 'Save configuration');
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
    $index_php = strpos(Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), 'index.php') !== FALSE;

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

    $this->assertEquals($expected, $url, 'The right port is used.');

    // If we set the port explicitly, it should not be overridden.
    $url = Url::fromRoute('<front>', [], [
      'absolute' => TRUE,
      'language' => $language,
      'base_url' => $request->getBaseUrl() . ':90',
    ])->toString();

    $expected = $index_php ? 'http://example.fr:90/index.php' : 'http://example.fr:90' . rtrim(base_path(), '/') . '/';

    $this->assertEquals($expected, $url, 'A given port is not overridden.');

  }

}
