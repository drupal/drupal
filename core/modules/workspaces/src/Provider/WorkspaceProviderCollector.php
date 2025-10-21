<?php

declare(strict_types=1);

namespace Drupal\workspaces\Provider;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Defines the workspace provider collector.
 *
 * @internal
 */
class WorkspaceProviderCollector {

  public function __construct(
    #[AutowireIterator(tag: 'workspace_provider', defaultIndexMethod: 'getId')]
    protected iterable $providers,
  ) {
    $this->providers = iterator_to_array($this->providers);
  }

  /**
   * Gets the workspace provider for the given ID.
   *
   * @param string $id
   *   A workspace provider ID.
   *
   * @return \Drupal\workspaces\Provider\WorkspaceProviderInterface
   *   The workspace provider.
   */
  public function getProvider($id): WorkspaceProviderInterface {
    if (!isset($this->providers[$id])) {
      throw new \DomainException("Workspace provider '$id' not found.");
    }

    return $this->providers[$id];
  }

}
