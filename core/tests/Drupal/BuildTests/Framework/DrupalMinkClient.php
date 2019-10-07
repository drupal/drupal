<?php

namespace Drupal\BuildTests\Framework;

use Behat\Mink\Driver\Goutte\Client;
use Symfony\Component\BrowserKit\Client as SymfonyClient;

/**
 * Extend the Mink client for Drupal use-cases.
 *
 * This is adapted from https://github.com/symfony/symfony/pull/27118.
 *
 * @todo Update this client when Drupal starts using Symfony 4.2.0+.
 *       https://www.drupal.org/project/drupal/issues/3077785
 */
class DrupalMinkClient extends Client {

  /**
   * Whether to follow meta redirects or not.
   *
   * @var bool
   *
   * @see \Drupal\BuildTests\Framework\DrupalMinkClient::followMetaRefresh()
   */
  protected $followMetaRefresh;

  /**
   * Sets whether to automatically follow meta refresh redirects or not.
   *
   * @param bool $followMetaRefresh
   *   (optional) Whether to follow meta redirects. Defaults to TRUE.
   */
  public function followMetaRefresh(bool $followMetaRefresh = TRUE) {
    $this->followMetaRefresh = $followMetaRefresh;
  }

  /**
   * Glean the meta refresh URL from the current page content.
   *
   * @return string|null
   *   Either the redirect URL that was found, or NULL if none was found.
   */
  private function getMetaRefreshUrl() {
    $metaRefresh = $this->getCrawler()->filter('meta[http-equiv="Refresh"], meta[http-equiv="refresh"]');
    foreach ($metaRefresh->extract(['content']) as $content) {
      if (preg_match('/^\s*0\s*;\s*URL\s*=\s*(?|\'([^\']++)|"([^"]++)|([^\'"].*))/i', $content, $m)) {
        return str_replace("\t\r\n", '', rtrim($m[1]));
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function request($method, $uri, array $parameters = [], array $files = [], array $server = [], $content = NULL, $changeHistory = TRUE) {
    $this->crawler = parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    // Check for meta refresh redirect and follow it.
    if ($this->followMetaRefresh && NULL !== $redirect = $this->getMetaRefreshUrl()) {
      $this->redirect = $redirect;
      // $this->redirects is private on the BrowserKit client, so we have to use
      // reflection to manage the redirects stack.
      $ref_redirects = new \ReflectionProperty(SymfonyClient::class, 'redirects');
      $ref_redirects->setAccessible(TRUE);
      $redirects = $ref_redirects->getValue($this);
      $redirects[serialize($this->history->current())] = TRUE;
      $ref_redirects->setValue($this, $redirects);

      $this->crawler = $this->followRedirect();
    }
    return $this->crawler;
  }

}
