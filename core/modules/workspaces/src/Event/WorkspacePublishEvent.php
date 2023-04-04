<?php

namespace Drupal\workspaces\Event;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\workspaces\WorkspaceInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Defines the workspace publish event.
 */
abstract class WorkspacePublishEvent extends Event {

  /**
   * The IDs of the entities that are being published.
   */
  protected readonly array $publishedRevisionIds;

  /**
   * Whether an event subscriber requested the publishing to be stopped.
   */
  protected bool $publishingStopped = FALSE;

  /**
   * The reason why publishing stopped. For use in messages.
   */
  protected string $publishingStoppedReason = '';

  /**
   * Constructs a new WorkspacePublishEvent.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   The workspace.
   * @param array $published_revision_ids
   *   The IDs of the entities that are being published.
   */
  public function __construct(
    protected readonly WorkspaceInterface $workspace,
    array $published_revision_ids
  ) {
    $this->publishedRevisionIds = $published_revision_ids;
  }

  /**
   * Gets the workspace.
   *
   * @return \Drupal\workspaces\WorkspaceInterface
   *   The workspace.
   */
  public function getWorkspace(): WorkspaceInterface {
    return $this->workspace;
  }

  /**
   * Gets the entity IDs that are being published as part of the workspace.
   *
   * @return array
   *   Returns a multidimensional array where the first level keys are entity
   *   type IDs and the values are an array of entity IDs keyed by revision IDs.
   */
  public function getPublishedRevisionIds(): array {
    return $this->publishedRevisionIds;
  }

  /**
   * Determines whether a subscriber requested the publishing to be stopped.
   *
   * @return bool
   *   TRUE if the publishing of the workspace should be stopped, FALSE
   *   otherwise.
   */
  public function isPublishingStopped(): bool {
    return $this->publishingStopped;
  }

  /**
   * Signals that the workspace publishing should be aborted.
   *
   * @return $this
   */
  public function stopPublishing(): static {
    $this->publishingStopped = TRUE;

    return $this;
  }

  /**
   * Gets the reason for stopping the workspace publication.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The reason for stopping the workspace publication or an empty string if
   *   no reason is provided.
   */
  public function getPublishingStoppedReason(): string|TranslatableMarkup {
    return $this->publishingStoppedReason;
  }

  /**
   * Sets the reason for stopping the workspace publication.
   *
   * @param string|\Stringable $reason
   *   The reason for stopping the workspace publication.
   *
   * @return $this
   */
  public function setPublishingStoppedReason(string|\Stringable $reason): static {
    $this->publishingStoppedReason = $reason;

    return $this;
  }

}
