<?php

namespace Drupal\workspaces;

use Drupal\workspaces\Event\WorkspaceSwitchEvent;
use Drupal\workspaces\Hook\WorkspacesHooks;
use Drupal\workspaces\Negotiator\WorkspaceNegotiatorInterface;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the workspace manager.
 *
 * @property iterable $negotiators
 * @property \Closure $entityTypeManager
 * @property \Closure $eventDispatcher
 */
class WorkspaceManager implements WorkspaceManagerInterface {

  /**
   * The current active workspace.
   *
   * The value is either a workspace object, FALSE if there is no active
   * workspace, or NULL if the active workspace hasn't been determined yet.
   */
  protected WorkspaceInterface|false|null $activeWorkspace = NULL;

  /**
   * An array of workspace negotiator services.
   *
   * @todo Remove in drupal:12.0.0.
   */
  private array $collectedNegotiators = [];

  public function __construct(
    protected RequestStack $requestStack,
    #[AutowireIterator(tag: 'workspace_negotiator')]
    protected $negotiators,
    #[AutowireServiceClosure('entity_type.manager')]
    protected $entityTypeManager,
    #[AutowireServiceClosure('event_dispatcher')]
    protected $eventDispatcher,
  ) {
    if (!$negotiators instanceof \IteratorAggregate) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $negotiators argument is deprecated in drupal:11.3.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/node/3532939', E_USER_DEPRECATED);
      $this->negotiators = $this->collectedNegotiators;
    }
    if (!$entityTypeManager instanceof \Closure) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $entityTypeManager argument is deprecated in drupal:11.3.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/node/3532939', E_USER_DEPRECATED);
      $this->eventDispatcher = new ServiceClosureArgument(new Reference('entity_type.manager'));
    }
    if (!$eventDispatcher instanceof \Closure) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $eventDispatcher argument is deprecated in drupal:11.3.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/node/3532939', E_USER_DEPRECATED);
      $this->eventDispatcher = new ServiceClosureArgument(new Reference('event_dispatcher'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasActiveWorkspace() {
    return $this->getActiveWorkspace() !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveWorkspace() {
    if (!isset($this->activeWorkspace)) {
      $request = $this->requestStack->getCurrentRequest();

      foreach ($this->negotiators as $negotiator) {
        if ($negotiator->applies($request)) {
          if ($workspace_id = $negotiator->getActiveWorkspaceId($request)) {
            /** @var \Drupal\workspaces\WorkspaceInterface $negotiated_workspace */
            $negotiated_workspace = ($this->entityTypeManager)()
              ->getStorage('workspace')
              ->load($workspace_id);
          }

          // By default, 'view' access is checked when a workspace is activated,
          // but it should also be checked when retrieving the currently active
          // workspace.
          if (isset($negotiated_workspace) && $negotiated_workspace->access('view')) {
            // Notify the negotiator that its workspace has been selected.
            $negotiator->setActiveWorkspace($negotiated_workspace);

            $active_workspace = $negotiated_workspace;
            break;
          }
        }
      }

      // If no negotiator was able to provide a valid workspace, default to the
      // live version of the site.
      $this->activeWorkspace = $active_workspace ?? FALSE;
    }

    return $this->activeWorkspace ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace, /* bool $persist = TRUE */) {
    $persist = func_num_args() < 2 || func_get_arg(1);

    $this->doSwitchWorkspace($workspace);

    // Set the workspace on the first applicable negotiator.
    if ($persist) {
      $request = $this->requestStack->getCurrentRequest();
      foreach ($this->negotiators as $negotiator) {
        if ($negotiator->applies($request)) {
          $negotiator->setActiveWorkspace($workspace);
          break;
        }
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function switchToLive() {
    $this->doSwitchWorkspace(NULL);

    // Unset the active workspace on all negotiators.
    foreach ($this->negotiators as $negotiator) {
      $negotiator->unsetActiveWorkspace();
    }

    return $this;
  }

  /**
   * Switches the current workspace.
   *
   * @param \Drupal\workspaces\WorkspaceInterface|null $workspace
   *   The workspace to set as active or NULL to switch out of the currently
   *   active workspace.
   *
   * @throws \Drupal\workspaces\WorkspaceAccessException
   *   Thrown when the current user doesn't have access to view the workspace.
   */
  protected function doSwitchWorkspace($workspace) {
    // If the current user doesn't have access to view the workspace, they
    // shouldn't be allowed to switch to it, except in CLI processes.
    if ($workspace && PHP_SAPI !== 'cli' && !$workspace->access('view')) {
      throw new WorkspaceAccessException('The user does not have permission to view that workspace.');
    }

    $previous_workspace = $this->activeWorkspace ?: NULL;
    $this->activeWorkspace = $workspace ?: FALSE;

    $event = new WorkspaceSwitchEvent($this->activeWorkspace ?: NULL, $previous_workspace);
    ($this->eventDispatcher)()->dispatch($event);
  }

  /**
   * {@inheritdoc}
   */
  public function executeInWorkspace($workspace_id, callable $function) {
    /** @var \Drupal\workspaces\WorkspaceInterface $workspace */
    $workspace = ($this->entityTypeManager)()->getStorage('workspace')->load($workspace_id);

    if (!$workspace) {
      throw new \InvalidArgumentException('The ' . $workspace_id . ' workspace does not exist.');
    }

    $previous_active_workspace = $this->getActiveWorkspace();

    // Switch to the requested workspace only if we're in Live or in another
    // workspace.
    $should_switch_workspace = !$previous_active_workspace || $previous_active_workspace->id() != $workspace_id;
    if ($should_switch_workspace) {
      $this->doSwitchWorkspace($workspace);
    }
    $result = $function();

    // Switch back if needed.
    if ($should_switch_workspace) {
      $this->doSwitchWorkspace($previous_active_workspace);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function executeOutsideWorkspace(callable $function) {
    $previous_active_workspace = $this->getActiveWorkspace();

    // Switch to Live if we're in a workspace.
    if ($previous_active_workspace) {
      $this->doSwitchWorkspace(NULL);
    }
    $result = $function();

    // Switch back if needed.
    if ($previous_active_workspace) {
      $this->doSwitchWorkspace($previous_active_workspace);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function purgeDeletedWorkspacesBatch() {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3553582', E_USER_DEPRECATED);
    \Drupal::service(WorkspacesHooks::class)->cron();
  }

  /**
   * Adds a workspace negotiator service.
   *
   * @param \Drupal\workspaces\Negotiator\WorkspaceNegotiatorInterface $negotiator
   *   The negotiator to be added.
   *
   * @todo Remove in drupal:12.0.0.
   *
   * @internal
   */
  public function addNegotiator(WorkspaceNegotiatorInterface $negotiator): void {
    $this->collectedNegotiators[] = $negotiator;
  }

}
