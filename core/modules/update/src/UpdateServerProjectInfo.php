<?php

declare(strict_types=1);

namespace Drupal\update;

/**
 * Update server project information.
 *
 * The information from the server about available updates for an individual
 * project.
 *
 * @see \Drupal\update\UpdateCalculator
 */
class UpdateServerProjectInfo extends \ArrayObject {

  /**
   * A value object to track available updates.
   *
   * @param string|null $status
   *   The status of the available update.
   * @param array $supportedBranches
   *   The branches the update supports.
   * @param array $releases
   *   The releases available for the project.
   * @param string $title
   *   The title of the project.
   * @param string $link
   *   A link to the project.
   * @param int|null $fetchStatus
   *   One of:
   *    - UpdateFetcherInterface::NOT_CHECKED
   *    - UpdateFetcherInterface::UNKNOWN
   *    - UpdateFetcherInterface::NOT_FETCHED
   *    - UpdateFetcherInterface::FETCH_PENDING.
   * @param int|null $lastFetch
   *   When the last project was fetched.
   * @param string|null $shortName
   *   The shortname of the project.
   * @param string|null $terms
   *   Release type, one of:
   *    - Security update
   *    - Unsupported
   *    - Insecure.
   */
  public function __construct(
    public ?string $status = NULL,
    public array $supportedBranches = [],
    public array $releases = [],
    public string $title = '',
    public string $link = '',
    public ?int $fetchStatus = NULL,
    public ?int $lastFetch = NULL,
    public ?string $shortName = NULL,
    public ?string $terms = NULL,
  ) {}

  /**
   * Creates a UpdateServerProjectInfo object.
   *
   * @param array $data
   *   The project data from the Update XML.
   *
   * @return static
   */
  public static function createFromArray(array $data): static {
    $data['supported_branches'] ??= [];
    return new UpdateServerProjectInfo(
      $data['project_status'] ?? NULL,
      $data['supported_branches'] ? explode(',', $data['supported_branches']) : [],
      $data['releases'] ?? [],
      $data['title'] ?? '',
      $data['link'] ?? '',
      $data['fetch_status'] ?? NULL,
      $data['last_fetch'] ?? NULL,
      $data['short_name'] ?? NULL,
      $data['terms'] ?? NULL,
    );
  }

}
