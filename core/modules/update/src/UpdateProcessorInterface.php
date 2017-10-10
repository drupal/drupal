<?php

namespace Drupal\update;

/**
 * Processor of project update information.
 */
interface UpdateProcessorInterface {

  /**
   * Claims an item in the update fetch queue for processing.
   *
   * @return bool|\stdClass
   *   On success we return an item object. If the queue is unable to claim an
   *   item it returns false.
   *
   * @see \Drupal\Core\Queue\QueueInterface::claimItem()
   */
  public function claimQueueItem();

  /**
   * Attempts to drain the queue of tasks for release history data to fetch.
   */
  public function fetchData();

  /**
   * Adds a task to the queue for fetching release history data for a project.
   *
   * We only create a new fetch task if there's no task already in the queue for
   * this particular project (based on 'update_fetch_task' key-value
   * collection).
   *
   * @param array $project
   *   Associative array of information about a project as created by
   *   \Drupal\Update\UpdateManager::getProjects(), including keys such as
   *   'name' (short name), and the 'info' array with data from a .info.yml
   *   file for the project.
   *
   * @see \Drupal\update\UpdateManager::getProjects()
   * @see update_get_available()
   * @see \Drupal\update\UpdateManager::refreshUpdateData()
   * @see \Drupal\update\UpdateProcessor::fetchData()
   * @see \Drupal\update\UpdateProcessor::processFetchTask()
   */
  public function createFetchTask($project);

  /**
   * Processes a task to fetch available update data for a single project.
   *
   * Once the release history XML data is downloaded, it is parsed and saved in
   * an entry just for that project.
   *
   * @param array $project
   *   Associative array of information about the project to fetch data for.
   *
   * @return bool
   *   TRUE if we fetched parsable XML, otherwise FALSE.
   */
  public function processFetchTask($project);

  /**
   * Retrieves the number of items in the update fetch queue.
   *
   * @return int
   *   An integer estimate of the number of items in the queue.
   *
   * @see \Drupal\Core\Queue\QueueInterface::numberOfItems()
   */
  public function numberOfQueueItems();

  /**
   * Deletes a finished item from the update fetch queue.
   *
   * @param \stdClass $item
   *   The item returned by \Drupal\Core\Queue\QueueInterface::claimItem().
   *
   * @see \Drupal\Core\Queue\QueueInterface::deleteItem()
   */
  public function deleteQueueItem($item);

}
