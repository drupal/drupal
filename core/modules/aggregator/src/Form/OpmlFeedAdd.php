<?php

namespace Drupal\aggregator\Form;

use Drupal\aggregator\FeedStorageInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface;

/**
 * Imports feeds from OPML.
 */
class OpmlFeedAdd extends FormBase {

  /**
   * The feed storage.
   *
   * @var \Drupal\aggregator\FeedStorageInterface
   */
  protected $feedStorage;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a database object.
   *
   * @param \Drupal\aggregator\FeedStorageInterface $feed_storage
   *   The feed storage.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   */
  public function __construct(FeedStorageInterface $feed_storage, ClientInterface $http_client) {
    $this->feedStorage = $feed_storage;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('aggregator_feed'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aggregator_opml_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $intervals = [900, 1800, 3600, 7200, 10800, 21600, 32400, 43200, 64800, 86400, 172800, 259200, 604800, 1209600, 2419200];
    $period = array_map([\Drupal::service('date.formatter'), 'formatInterval'], array_combine($intervals, $intervals));

    $form['upload'] = [
      '#type' => 'file',
      '#title' => $this->t('OPML File'),
      '#description' => $this->t('Upload an OPML file containing a list of feeds to be imported.'),
    ];
    $form['remote'] = [
      '#type' => 'url',
      '#title' => $this->t('OPML Remote URL'),
      '#maxlength' => 1024,
      '#description' => $this->t('Enter the URL of an OPML file. This file will be downloaded and processed only once on submission of the form.'),
    ];
    $form['refresh'] = [
      '#type' => 'select',
      '#title' => $this->t('Update interval'),
      '#default_value' => 3600,
      '#options' => $period,
      '#description' => $this->t('The length of time between feed updates. Requires a correctly configured <a href=":cron">cron maintenance task</a>.', [':cron' => $this->url('system.status')]),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // If both fields are empty or filled, cancel.
    $all_files = $this->getRequest()->files->get('files', []);
    if ($form_state->isValueEmpty('remote') == empty($all_files['upload'])) {
      $form_state->setErrorByName('remote', $this->t('<em>Either</em> upload a file or enter a URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $validators = ['file_validate_extensions' => ['opml xml']];
    if ($file = file_save_upload('upload', $validators, FALSE, 0)) {
      $data = file_get_contents($file->getFileUri());
    }
    else {
      // @todo Move this to a fetcher implementation.
      try {
        $response = $this->httpClient->get($form_state->getValue('remote'));
        $data = (string) $response->getBody();
      }
      catch (RequestException $e) {
        $this->logger('aggregator')->warning('Failed to download OPML file due to "%error".', ['%error' => $e->getMessage()]);
        drupal_set_message($this->t('Failed to download OPML file due to "%error".', ['%error' => $e->getMessage()]));
        return;
      }
    }

    $feeds = $this->parseOpml($data);
    if (empty($feeds)) {
      drupal_set_message($this->t('No new feed has been added.'));
      return;
    }

    // @todo Move this functionality to a processor.
    foreach ($feeds as $feed) {
      // Ensure URL is valid.
      if (!UrlHelper::isValid($feed['url'], TRUE)) {
        drupal_set_message($this->t('The URL %url is invalid.', ['%url' => $feed['url']]), 'warning');
        continue;
      }

      // Check for duplicate titles or URLs.
      $query = $this->feedStorage->getQuery();
      $condition = $query->orConditionGroup()
        ->condition('title', $feed['title'])
        ->condition('url', $feed['url']);
      $ids = $query
        ->condition($condition)
        ->execute();
      $result = $this->feedStorage->loadMultiple($ids);
      foreach ($result as $old) {
        if (strcasecmp($old->label(), $feed['title']) == 0) {
          drupal_set_message($this->t('A feed named %title already exists.', ['%title' => $old->label()]), 'warning');
          continue 2;
        }
        if (strcasecmp($old->getUrl(), $feed['url']) == 0) {
          drupal_set_message($this->t('A feed with the URL %url already exists.', ['%url' => $old->getUrl()]), 'warning');
          continue 2;
        }
      }

      $new_feed = $this->feedStorage->create([
        'title' => $feed['title'],
        'url' => $feed['url'],
        'refresh' => $form_state->getValue('refresh'),
      ]);
      $new_feed->save();
    }

    $form_state->setRedirect('aggregator.admin_overview');
  }

  /**
   * Parses an OPML file.
   *
   * Feeds are recognized as <outline> elements with the attributes "text" and
   * "xmlurl" set.
   *
   * @param string $opml
   *   The complete contents of an OPML document.
   *
   * @return array
   *   An array of feeds, each an associative array with a "title" and a "url"
   *   element, or NULL if the OPML document failed to be parsed. An empty array
   *   will be returned if the document is valid but contains no feeds, as some
   *   OPML documents do.
   *
   * @todo Move this to a parser in https://www.drupal.org/node/1963540.
   */
  protected function parseOpml($opml) {
    $feeds = [];
    $xml_parser = xml_parser_create();
    xml_parser_set_option($xml_parser, XML_OPTION_TARGET_ENCODING, 'utf-8');
    if (xml_parse_into_struct($xml_parser, $opml, $values)) {
      foreach ($values as $entry) {
        if ($entry['tag'] == 'OUTLINE' && isset($entry['attributes'])) {
          $item = $entry['attributes'];
          if (!empty($item['XMLURL']) && !empty($item['TEXT'])) {
            $feeds[] = ['title' => $item['TEXT'], 'url' => $item['XMLURL']];
          }
        }
      }
    }
    xml_parser_free($xml_parser);

    return $feeds;
  }

}
