<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

/**
 * Provides helper methods for the JSON:API module's functional tests.
 *
 * @internal
 */
abstract class JsonApiFunctionalTestBase extends BrowserTestBase {

  use EntityReferenceTestTrait;
  use ImageFieldCreationTrait;

  const IS_MULTILINGUAL = TRUE;
  const IS_NOT_MULTILINGUAL = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'jsonapi',
    'serialization',
    'node',
    'image',
    'taxonomy',
    'link',
  ];

  /**
   * Test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Test user with access to view profiles.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $userCanViewProfiles;

  /**
   * Test nodes.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected $nodes = [];

  /**
   * Test taxonomy terms.
   *
   * @var \Drupal\taxonomy\Entity\Term[]
   */
  protected $tags = [];

  /**
   * Test files.
   *
   * @var \Drupal\file\Entity\File[]
   */
  protected $files = [];

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set up a HTTP client that accepts relative URLs.
    $this->httpClient = $this->container->get('http_client_factory')
      ->fromOptions(['base_uri' => $this->baseUrl]);

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'article',
        'name' => 'Article',
      ]);

      // Setup vocabulary.
      Vocabulary::create([
        'vid' => 'tags',
        'name' => 'Tags',
      ])->save();

      // Add tags and field_image to the article.
      $this->createEntityReferenceField(
        'node',
        'article',
        'field_tags',
        'Tags',
        'taxonomy_term',
        'default',
        [
          'target_bundles' => [
            'tags' => 'tags',
          ],
          'auto_create' => TRUE,
        ],
        FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
      );
      $this->createImageField('field_image', 'article');
      $this->createImageField('field_heroless', 'article');
    }

    FieldStorageConfig::create([
      'field_name' => 'field_link',
      'entity_type' => 'node',
      'type' => 'link',
      'settings' => [],
      'cardinality' => 1,
    ])->save();

    $field_config = FieldConfig::create([
      'field_name' => 'field_link',
      'label' => 'Link',
      'entity_type' => 'node',
      'bundle' => 'article',
      'required' => FALSE,
      'settings' => [],
      'description' => '',
    ]);
    $field_config->save();

    // Field for testing sorting.
    FieldStorageConfig::create([
      'field_name' => 'field_sort1',
      'entity_type' => 'node',
      'type' => 'integer',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_sort1',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    // Another field for testing sorting.
    FieldStorageConfig::create([
      'field_name' => 'field_sort2',
      'entity_type' => 'node',
      'type' => 'integer',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_sort2',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    $this->user = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
      'delete any article content',
    ]);

    // Create a user that can.
    $this->userCanViewProfiles = $this->drupalCreateUser([
      'access user profiles',
    ]);

    $this->grantPermissions(Role::load(RoleInterface::ANONYMOUS_ID), [
      'access user profiles',
      'administer taxonomy',
    ]);

    drupal_flush_all_caches();
  }

  /**
   * Performs a HTTP request. Wraps the Guzzle HTTP client.
   *
   * Why wrap the Guzzle HTTP client? Because any error response is returned via
   * an exception, which would make the tests unnecessarily complex to read.
   *
   * @param string $method
   *   HTTP method.
   * @param \Drupal\Core\Url $url
   *   URL to request.
   * @param array $request_options
   *   Request options to apply.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The request response.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *
   * @see \GuzzleHttp\ClientInterface::request
   */
  protected function request($method, Url $url, array $request_options) {
    try {
      $response = $this->httpClient->request($method, $url->toString(), $request_options);
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
    }
    catch (ServerException $e) {
      $response = $e->getResponse();
    }

    return $response;
  }

  /**
   * Creates default content to test the API.
   *
   * @param int $num_articles
   *   Number of articles to create.
   * @param int $num_tags
   *   Number of tags to create.
   * @param bool $article_has_image
   *   Set to TRUE if you want to add an image to the generated articles.
   * @param bool $article_has_link
   *   Set to TRUE if you want to add a link to the generated articles.
   * @param bool $is_multilingual
   *   (optional) Set to TRUE if you want to enable multilingual content.
   * @param bool $referencing_twice
   *   (optional) Set to TRUE if you want articles to reference the same tag
   *   twice.
   */
  protected function createDefaultContent($num_articles, $num_tags, $article_has_image, $article_has_link, $is_multilingual, $referencing_twice = FALSE) {
    $random = $this->getRandomGenerator();
    for ($created_tags = 0; $created_tags < $num_tags; $created_tags++) {
      $term = Term::create([
        'vid' => 'tags',
        'name' => $random->name(),
      ]);

      if ($is_multilingual) {
        $term->addTranslation('ca', ['name' => $term->getName() . ' (ca)']);
      }

      $term->save();
      $this->tags[] = $term;
    }
    for ($created_nodes = 0; $created_nodes < $num_articles; $created_nodes++) {
      $values = [
        'uid' => ['target_id' => $this->user->id()],
        'type' => 'article',
      ];

      if ($referencing_twice) {
        $values['field_tags'] = [
          ['target_id' => 1],
          ['target_id' => 1],
        ];
      }
      else {
        // Get N random tags.
        $selected_tags = mt_rand(1, $num_tags);
        $tags = [];
        while (count($tags) < $selected_tags) {
          $tags[] = mt_rand(1, $num_tags);
          $tags = array_unique($tags);
        }
        $values['field_tags'] = array_map(function ($tag) {
          return ['target_id' => $tag];
        }, $tags);
      }
      if ($article_has_image) {
        $file = File::create([
          'uri' => 'vfs://' . $random->name() . '.png',
        ]);
        $file->setPermanent();
        $file->save();
        $this->files[] = $file;
        $values['field_image'] = ['target_id' => $file->id(), 'alt' => 'alt text'];
      }
      if ($article_has_link) {
        $values['field_link'] = [
          'title' => $this->getRandomGenerator()->name(),
          'uri' => sprintf(
            '%s://%s.%s',
            'http' . (mt_rand(0, 2) > 1 ? '' : 's'),
            $this->getRandomGenerator()->name(),
            'org'
          ),
        ];
      }

      // Create values for the sort fields, to allow for testing complex
      // sorting:
      // - field_sort1 increments every 5 articles, starting at zero
      // - field_sort2 decreases every article, ending at zero.
      $values['field_sort1'] = ['value' => floor($created_nodes / 5)];
      $values['field_sort2'] = ['value' => $num_articles - $created_nodes];

      $node = $this->createNode($values);

      if ($is_multilingual === static::IS_MULTILINGUAL) {
        $values['title'] = $node->getTitle() . ' (ca)';
        $values['field_image']['alt'] = 'alt text (ca)';
        $node->addTranslation('ca', $values);
      }
      $node->save();

      $this->nodes[] = $node;
    }
    if ($article_has_link) {
      // Make sure that there is at least 1 https link for ::testRead() #19.
      $this->nodes[0]->field_link = [
        'title' => 'Drupal',
        'uri' => 'https://drupal.org',
      ];
      $this->nodes[0]->save();
    }
  }

}
