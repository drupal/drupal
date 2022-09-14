<?php

namespace Drupal\update;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Queue\QueueFactory;

/**
 * Process project update information.
 */
class UpdateProcessor implements UpdateProcessorInterface {

  /**
   * The update settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $updateSettings;

  /**
   * The UpdateFetcher service.
   *
   * @var \Drupal\update\UpdateFetcherInterface
   */
  protected $updateFetcher;

  /**
   * The update fetch queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $fetchQueue;

  /**
   * Update key/value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $tempStore;

  /**
   * Update Fetch Task Store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $fetchTaskStore;

  /**
   * Update available releases store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $availableReleasesTempStore;

  /**
   * Array of release history URLs that we have failed to fetch.
   *
   * @var array
   */
  protected $failed;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateStore;

  /**
   * The private key.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The queue for fetching release history data.
   */
  protected $fetchTasks;

  /**
   * Constructs an UpdateProcessor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory
   * @param \Drupal\update\UpdateFetcherInterface $update_fetcher
   *   The update fetcher service
   * @param \Drupal\Core\State\StateInterface $state_store
   *   The state service.
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key factory service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key/value factory.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_expirable_factory
   *   The expirable key/value factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, QueueFactory $queue_factory, UpdateFetcherInterface $update_fetcher, StateInterface $state_store, PrivateKey $private_key, KeyValueFactoryInterface $key_value_factory, KeyValueExpirableFactoryInterface $key_value_expirable_factory) {
    $this->updateFetcher = $update_fetcher;
    $this->updateSettings = $config_factory->get('update.settings');
    $this->fetchQueue = $queue_factory->get('update_fetch_tasks');
    $this->tempStore = $key_value_expirable_factory->get('update');
    $this->fetchTaskStore = $key_value_factory->get('update_fetch_task');
    $this->availableReleasesTempStore = $key_value_expirable_factory->get('update_available_releases');
    $this->stateStore = $state_store;
    $this->privateKey = $private_key;
    $this->fetchTasks = [];
    $this->failed = [];
  }

  /**
   * {@inheritdoc}
   */
  public function createFetchTask($project) {
    if (empty($this->fetchTasks)) {
      $this->fetchTasks = $this->fetchTaskStore->getAll();
    }
    if (empty($this->fetchTasks[$project['name']])) {
      $this->fetchQueue->createItem($project);
      $this->fetchTaskStore->set($project['name'], $project);
      $this->fetchTasks[$project['name']] = REQUEST_TIME;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetchData() {
    $end = time() + $this->updateSettings->get('fetch.timeout');
    if ($this->fetchQueue->numberOfItems()) {
      // Delete any stored project data as that needs refreshing when
      // update_calculate_project_data() is called.
      $this->tempStore->delete('update_project_data');
    }
    while (time() < $end && ($item = $this->fetchQueue->claimItem())) {
      $this->processFetchTask($item->data);
      $this->fetchQueue->deleteItem($item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processFetchTask($project) {
    global $base_url;

    // This can be in the middle of a long-running batch, so REQUEST_TIME won't
    // necessarily be valid.
    $request_time_difference = time() - REQUEST_TIME;
    if (empty($this->failed)) {
      // If we have valid data about release history XML servers that we have
      // failed to fetch from on previous attempts, load that.
      $this->failed = $this->tempStore->get('fetch_failures');
    }

    $max_fetch_attempts = $this->updateSettings->get('fetch.max_attempts');

    $success = FALSE;
    $available = [];
    $site_key = Crypt::hmacBase64($base_url, $this->privateKey->get());
    $fetch_url_base = $this->updateFetcher->getFetchBaseUrl($project);
    $project_name = $project['name'];

    if (empty($this->failed[$fetch_url_base]) || $this->failed[$fetch_url_base] < $max_fetch_attempts) {
      $data = $this->updateFetcher->fetchProjectData($project, $site_key);
    }
    if (!empty($data)) {
      $available = $this->parseXml($data);
      // @todo Purge release data we don't need. See
      //   https://www.drupal.org/node/238950.
      if (!empty($available)) {
        // Only if we fetched and parsed something sane do we return success.
        $success = TRUE;
      }
    }
    else {
      $available['project_status'] = 'not-fetched';
      if (empty($this->failed[$fetch_url_base])) {
        $this->failed[$fetch_url_base] = 1;
      }
      else {
        $this->failed[$fetch_url_base]++;
      }
    }

    $frequency = $this->updateSettings->get('check.interval_days');
    $available['last_fetch'] = REQUEST_TIME + $request_time_difference;
    $this->availableReleasesTempStore->setWithExpire($project_name, $available, $request_time_difference + (60 * 60 * 24 * $frequency));

    // Stash the $this->failed data back in the DB for the next 5 minutes.
    $this->tempStore->setWithExpire('fetch_failures', $this->failed, $request_time_difference + (60 * 5));

    // Whether this worked or not, we did just (try to) check for updates.
    $this->stateStore->set('update.last_check', REQUEST_TIME + $request_time_difference);

    // Now that we processed the fetch task for this project, clear out the
    // record for this task so we're willing to fetch again.
    $this->fetchTaskStore->delete($project_name);

    return $success;
  }

  /**
   * Parses the XML of the Drupal release history info files.
   *
   * @param string $raw_xml
   *   A raw XML string of available release data for a given project.
   *
   * @return array
   *   Array of parsed data about releases for a given project, or NULL if there
   *   was an error parsing the string.
   */
  protected function parseXml($raw_xml) {
    try {
      $xml = new \SimpleXMLElement($raw_xml);
    }
    catch (\Exception $e) {
      // SimpleXMLElement::__construct produces an E_WARNING error message for
      // each error found in the XML data and throws an exception if errors
      // were detected. Catch any exception and return failure (NULL).
      return NULL;
    }
    // If there is no valid project data, the XML is invalid, so return failure.
    if (!isset($xml->short_name)) {
      return NULL;
    }
    $data = [];
    foreach ($xml as $k => $v) {
      $data[$k] = (string) $v;
    }
    $data['releases'] = [];
    if (isset($xml->releases)) {
      foreach ($xml->releases->children() as $release) {
        $version = (string) $release->version;
        $data['releases'][$version] = [];
        foreach ($release->children() as $k => $v) {
          $data['releases'][$version][$k] = (string) $v;
        }
        $data['releases'][$version]['terms'] = [];
        if ($release->terms) {
          foreach ($release->terms->children() as $term) {
            if (!isset($data['releases'][$version]['terms'][(string) $term->name])) {
              $data['releases'][$version]['terms'][(string) $term->name] = [];
            }
            $data['releases'][$version]['terms'][(string) $term->name][] = (string) $term->value;
          }
        }
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function numberOfQueueItems() {
    return $this->fetchQueue->numberOfItems();
  }

  /**
   * {@inheritdoc}
   */
  public function claimQueueItem() {
    return $this->fetchQueue->claimItem();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueueItem($item) {
    return $this->fetchQueue->deleteItem($item);
  }

}
