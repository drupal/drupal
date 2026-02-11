<?php

namespace Drupal\path_alias;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

/**
 * Extends the default path matcher to check aliases.
 */
class AliasPathMatcher implements PathMatcherInterface {

  /**
   * Whether the current page is the front page.
   */
  protected ?bool $isCurrentFrontPage = NULL;

  public function __construct(
    #[AutowireDecorated]
    protected PathMatcherInterface $decorated,
    protected RouteMatchInterface $routeMatch,
    protected AliasManagerInterface $aliasManager,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function matchPath($path, $patterns) {
    return $this->decorated->matchPath($path, $patterns);
  }

  /**
   * {@inheritdoc}
   */
  public function isFrontPage() {
    // Cache the result as this is called often.
    $this->isCurrentFrontPage ??= $this->decorated->isFrontPage() || $this->isAliasFrontPage();
    return $this->isCurrentFrontPage;
  }

  /**
   * Checks if the current page is the front page by comparing aliases.
   */
  protected function isAliasFrontPage(): bool {
    // Ensure that the code can also be executed when there is no active
    // route match, like on exception responses.
    if (!$this->routeMatch->getRouteName()) {
      return FALSE;
    }

    $url = Url::fromRouteMatch($this->routeMatch);
    $path = '/' . $url->getInternalPath();
    $frontPagePath = $this->configFactory
      ->get('system.site')
      ->get('page.front');

    return $this->aliasManager->getAliasByPath($path) === $frontPagePath;
  }

}
