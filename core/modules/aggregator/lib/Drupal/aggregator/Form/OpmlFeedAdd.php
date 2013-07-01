<?php

/**
 * @file
 * Contains \Drupal\aggregator\Form\OpmlFeedAdd.
 */

namespace Drupal\aggregator\Form;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Client;

/**
 * Imports feeds from OPML.
 */
class OpmlFeedAdd implements ControllerInterface, FormInterface {

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity query factory object.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \Guzzle\Http\Client
   */
  protected $httpClient;

  /**
   * Constructs a database object.
   *
   * @param \Drupal\Core\Database\Connection; $database
   *   The database object.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query object.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param \Guzzle\Http\Client
   *   The Guzzle HTTP client.
   */
  public function __construct(Connection $database, QueryFactory $query_factory, EntityManager $entity_manager, Client $http_client) {
    $this->database = $database;
    $this->queryFactory = $query_factory;
    $this->entityManager = $entity_manager;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity.query'),
      $container->get('plugin.manager.entity'),
      $container->get('http_default_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'aggregator_opml_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $period = drupal_map_assoc(array(900, 1800, 3600, 7200, 10800, 21600, 32400, 43200,
      64800, 86400, 172800, 259200, 604800, 1209600, 2419200), 'format_interval');

    $form['upload'] = array(
      '#type' => 'file',
      '#title' => t('OPML File'),
      '#description' => t('Upload an OPML file containing a list of feeds to be imported.'),
    );
    $form['remote'] = array(
      '#type' => 'url',
      '#title' => t('OPML Remote URL'),
      '#maxlength' => 1024,
      '#description' => t('Enter the URL of an OPML file. This file will be downloaded and processed only once on submission of the form.'),
    );
    $form['refresh'] = array(
      '#type' => 'select',
      '#title' => t('Update interval'),
      '#default_value' => 3600,
      '#options' => $period,
      '#description' => t('The length of time between feed updates. Requires a correctly configured <a href="@cron">cron maintenance task</a>.', array('@cron' => url('admin/reports/status'))),
    );
    $form['block'] = array(
      '#type' => 'select',
      '#title' => t('News items in block'),
      '#default_value' => 5,
      '#options' => drupal_map_assoc(array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20)),
      '#description' => t("Drupal can make a block with the most recent news items of a feed. You can <a href=\"@block-admin\">configure blocks</a> to be displayed in the sidebar of your page. This setting lets you configure the number of news items to show in a feed's block. If you choose '0' these feeds' blocks will be disabled.", array('@block-admin' => url('admin/structure/block'))),
    );

    // Handling of categories.
    $options = array_map('check_plain', $this->database->query("SELECT cid, title FROM {aggregator_category} ORDER BY title")->fetchAllKeyed());
    if ($options) {
      $form['category'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Categorize news items'),
        '#options' => $options,
        '#description' => t('New feed items are automatically filed in the checked categories.'),
      );
    }
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Import'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // If both fields are empty or filled, cancel.
    if (empty($form_state['values']['remote']) == empty($_FILES['files']['name']['upload'])) {
      form_set_error('remote', t('You must <em>either</em> upload a file or enter a URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $data = '';
    $validators = array('file_validate_extensions' => array('opml xml'));
    if ($file = file_save_upload('upload', $validators, FALSE, 0)) {
      $data = file_get_contents($file->getFileUri());
    }
    else {
      // @todo Move this to a fetcher implementation.
      try {
        $response = $this->httpClient->get($form_state['values']['remote'])->send();
        $data = $response->getBody(TRUE);
      }
      catch (BadResponseException $e) {
        $response = $e->getResponse();
        watchdog('aggregator', 'Failed to download OPML file due to "%error".', array('%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase()), WATCHDOG_WARNING);
        drupal_set_message(t('Failed to download OPML file due to "%error".', array('%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase())));
        return;
      }
      catch (RequestException $e) {
        watchdog('aggregator', 'Failed to download OPML file due to "%error".', array('%error' => $e->getMessage()), WATCHDOG_WARNING);
        drupal_set_message(t('Failed to download OPML file due to "%error".', array('%error' => $e->getMessage())));
        return;
      }
    }

    $feeds = $this->parseOpml($data);
    if (empty($feeds)) {
      drupal_set_message(t('No new feed has been added.'));
      return;
    }

    // @todo Move this functionality to a processor.
    foreach ($feeds as $feed) {
      // Ensure URL is valid.
      if (!valid_url($feed['url'], TRUE)) {
        drupal_set_message(t('The URL %url is invalid.', array('%url' => $feed['url'])), 'warning');
        continue;
      }

      // Check for duplicate titles or URLs.
      $query = $this->queryFactory->get('aggregator_feed');
      $condition = $query->orConditionGroup()
        ->condition('title', $feed['title'])
        ->condition('url', $feed['url']);
      $ids = $query
        ->condition($condition)
        ->execute();
      $result = $this->entityManager
        ->getStorageController('aggregator_feed')
        ->loadMultiple($ids);
      foreach ($result as $old) {
        if (strcasecmp($old->label(), $feed['title']) == 0) {
          drupal_set_message(t('A feed named %title already exists.', array('%title' => $old->label())), 'warning');
          continue 2;
        }
        if (strcasecmp($old->url->value, $feed['url']) == 0) {
          drupal_set_message(t('A feed with the URL %url already exists.', array('%url' => $old->url->value)), 'warning');
          continue 2;
        }
      }

      $new_feed = $this->entityManager
        ->getStorageController('aggregator_feed')
        ->create(array(
          'title' => $feed['title'],
          'url' => $feed['url'],
          'refresh' => $form_state['values']['refresh'],
          'block' => $form_state['values']['block'],
        ));
      $new_feed->categories = $form_state['values']['category'];
      $new_feed->save();
    }

    $form_state['redirect'] = 'admin/config/services/aggregator';
  }

  /**
   * Parses an OPML file.
   *
   * Feeds are recognized as <outline> elements with the attributes "text" and
   * "xmlurl" set.
   *
   * @todo Move this functionality to a parser.
   *
   * @param $opml
   *   The complete contents of an OPML document.
   *
   * @return
   *   An array of feeds, each an associative array with a "title" and a "url"
   *   element, or NULL if the OPML document failed to be parsed. An empty array
   *   will be returned if the document is valid but contains no feeds, as some
   *   OPML documents do.
   */
  protected function parseOpml($opml) {
    $feeds = array();
    $xml_parser = drupal_xml_parser_create($opml);
    if (xml_parse_into_struct($xml_parser, $opml, $values)) {
      foreach ($values as $entry) {
        if ($entry['tag'] == 'OUTLINE' && isset($entry['attributes'])) {
          $item = $entry['attributes'];
          if (!empty($item['XMLURL']) && !empty($item['TEXT'])) {
            $feeds[] = array('title' => $item['TEXT'], 'url' => $item['XMLURL']);
          }
        }
      }
    }
    xml_parser_free($xml_parser);

    return $feeds;
  }

}
