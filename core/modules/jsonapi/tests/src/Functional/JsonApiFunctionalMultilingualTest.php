<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use GuzzleHttp\RequestOptions;

/**
 * Tests JSON:API multilingual support.
 *
 * @group jsonapi
 *
 * @internal
 */
class JsonApiFunctionalMultilingualTest extends JsonApiFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $language = ConfigurableLanguage::createFromLangcode('ca');
    $language->save();
    ConfigurableLanguage::createFromLangcode('ca-fr')->save();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();

    \Drupal::configFactory()->getEditable('language.negotiation')
      ->set('url.prefixes.ca', 'ca')
      ->set('url.prefixes.ca-fr', 'ca-fr')
      ->save();

    ContentLanguageSettings::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'article',
    ])
      ->setThirdPartySetting('content_translation', 'enabled', TRUE)
      ->save();

    $this->createDefaultContent(5, 5, TRUE, TRUE, static::IS_MULTILINGUAL, FALSE);
  }

  /**
   * Tests reading multilingual content.
   */
  public function testReadMultilingual() {
    // Different databases have different sort orders, so a sort is required so
    // test expectations do not need to vary per database.
    $default_sort = ['sort' => 'drupal_internal__nid'];

    // Test reading an individual entity translation.
    $output = Json::decode($this->drupalGet('/ca/jsonapi/node/article/' . $this->nodes[0]->uuid(), ['query' => ['include' => 'field_tags,field_image'] + $default_sort]));
    $this->assertEquals($this->nodes[0]->getTranslation('ca')->getTitle(), $output['data']['attributes']['title']);
    $this->assertSame('ca', $output['data']['attributes']['langcode']);
    $included_tags = array_filter($output['included'], function ($entry) {
      return $entry['type'] === 'taxonomy_term--tags';
    });
    $tag_name = $this->nodes[0]->get('field_tags')->entity
      ->getTranslation('ca')->getName();
    $this->assertEquals($tag_name, reset($included_tags)['attributes']['name']);
    $alt = $this->nodes[0]->getTranslation('ca')->get('field_image')->alt;
    $this->assertSame($alt, $output['data']['relationships']['field_image']['data']['meta']['alt']);

    // Test reading an individual entity fallback.
    $output = Json::decode($this->drupalGet('/ca-fr/jsonapi/node/article/' . $this->nodes[0]->uuid()));
    $this->assertEquals($this->nodes[0]->getTranslation('ca')->getTitle(), $output['data']['attributes']['title']);

    $output = Json::decode($this->drupalGet('/ca/jsonapi/node/article/' . $this->nodes[0]->uuid(), ['query' => $default_sort]));
    $this->assertEquals($this->nodes[0]->getTranslation('ca')->getTitle(), $output['data']['attributes']['title']);

    // Test reading a collection of entities.
    $output = Json::decode($this->drupalGet('/ca/jsonapi/node/article', ['query' => $default_sort]));
    $this->assertEquals($this->nodes[0]->getTranslation('ca')->getTitle(), $output['data'][0]['attributes']['title']);
  }

  /**
   * Tests updating a translation.
   */
  public function testPatchTranslation() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);
    $node = $this->nodes[0];
    $uuid = $node->uuid();

    // Assert the precondition: the 'ca' translation has a different title.
    $document = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid));
    $document_ca = Json::decode($this->drupalGet('/ca/jsonapi/node/article/' . $uuid));
    $this->assertSame('en', $document['data']['attributes']['langcode']);
    $this->assertSame('ca', $document_ca['data']['attributes']['langcode']);
    $this->assertSame($node->getTitle(), $document['data']['attributes']['title']);
    $this->assertSame($node->getTitle() . ' (ca)', $document_ca['data']['attributes']['title']);

    // PATCH the 'ca' translation.
    $this->grantPermissions(Role::load(RoleInterface::ANONYMOUS_ID), [
      'bypass node access',
    ]);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options[RequestOptions::BODY] = Json::encode([
      'data' => [
        'type' => 'node--article',
        'id' => $uuid,
        'attributes' => [
          'title' => $document_ca['data']['attributes']['title'] . ' UPDATED',
        ],
      ],
    ]);
    $response = $this->request('PATCH', Url::fromUri('base:/ca/jsonapi/node/article/' . $this->nodes[0]->uuid()), $request_options);
    $this->assertSame(200, $response->getStatusCode());

    // Assert the postcondition: only the 'ca' translation has an updated title.
    $document_updated = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid));
    $document_ca_updated = Json::decode($this->drupalGet('/ca/jsonapi/node/article/' . $uuid));
    $this->assertSame('en', $document_updated['data']['attributes']['langcode']);
    $this->assertSame('ca', $document_ca_updated['data']['attributes']['langcode']);
    $this->assertSame($node->getTitle(), $document_updated['data']['attributes']['title']);
    $this->assertSame($node->getTitle() . ' (ca) UPDATED', $document_ca_updated['data']['attributes']['title']);

    // Specifying a langcode is not allowed by default.
    $request_options[RequestOptions::BODY] = Json::encode([
      'data' => [
        'type' => 'node--article',
        'id' => $uuid,
        'attributes' => [
          'langcode' => 'ca-fr',
        ],
      ],
    ]);
    $response = $this->request('PATCH', Url::fromUri('base:/ca/jsonapi/node/article/' . $this->nodes[0]->uuid()), $request_options);
    $this->assertSame(403, $response->getStatusCode());

    // Specifying a langcode is allowed once configured to be alterable. But
    // modifying the language of a non-default translation is still not allowed.
    ContentLanguageSettings::loadByEntityTypeBundle('node', 'article')
      ->setLanguageAlterable(TRUE)
      ->save();
    $response = $this->request('PATCH', Url::fromUri('base:/ca/jsonapi/node/article/' . $this->nodes[0]->uuid()), $request_options);
    $this->assertSame(500, $response->getStatusCode());
    $document = Json::decode((string) $response->getBody());
    $this->assertSame('The translation language cannot be changed (ca).', $document['errors'][0]['detail']);

    // Changing the langcode of the default ('en') translation is possible:
    // first verify that it currently is 'en', then change it to 'ca-fr', and
    // verify that the the title is unchanged, but the langcode is updated.
    $response = $this->request('GET', Url::fromUri('base:/jsonapi/node/article/' . $this->nodes[0]->uuid()), $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $document = Json::decode((string) $response->getBody());
    $this->assertSame($node->getTitle(), $document['data']['attributes']['title']);
    $this->assertSame('en', $document['data']['attributes']['langcode']);
    $response = $this->request('PATCH', Url::fromUri('base:/jsonapi/node/article/' . $this->nodes[0]->uuid()), $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $document = Json::decode((string) $response->getBody());
    $this->assertSame($node->getTitle(), $document['data']['attributes']['title']);
    $this->assertSame('ca-fr', $document['data']['attributes']['langcode']);

    // Finally: assert the postcondition of all installed languages.
    // - When GETting the 'en' translation, we get 'ca-fr', since the 'en'
    //   translation doesn't exist anymore.
    $response = $this->request('GET', Url::fromUri('base:/jsonapi/node/article/' . $this->nodes[0]->uuid()), $request_options);
    $document = Json::decode((string) $response->getBody());
    $this->assertSame('ca-fr', $document['data']['attributes']['langcode']);
    $this->assertSame($node->getTitle(), $document['data']['attributes']['title']);
    // - When GETting the 'ca' translation, we still get the 'ca' one.
    $response = $this->request('GET', Url::fromUri('base:/ca/jsonapi/node/article/' . $this->nodes[0]->uuid()), $request_options);
    $document = Json::decode((string) $response->getBody());
    $this->assertSame('ca', $document['data']['attributes']['langcode']);
    $this->assertSame($node->getTitle() . ' (ca) UPDATED', $document['data']['attributes']['title']);
    // - When GETting the 'ca-fr' translation, we now get the default
    //   translation.
    $response = $this->request('GET', Url::fromUri('base:/ca-fr/jsonapi/node/article/' . $this->nodes[0]->uuid()), $request_options);
    $document = Json::decode((string) $response->getBody());
    $this->assertSame('ca-fr', $document['data']['attributes']['langcode']);
    $this->assertSame($node->getTitle(), $document['data']['attributes']['title']);
  }

  /**
   * Tests updating a translation fallback.
   */
  public function testPatchTranslationFallback() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);
    $node = $this->nodes[0];
    $uuid = $node->uuid();

    // Assert the precondition: 'ca-fr' falls back to the 'ca' translation which
    // has a different title.
    $document = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid));
    $document_ca = Json::decode($this->drupalGet('/ca/jsonapi/node/article/' . $uuid));
    $document_cafr = Json::decode($this->drupalGet('/ca-fr/jsonapi/node/article/' . $uuid));
    $this->assertSame('en', $document['data']['attributes']['langcode']);
    $this->assertSame('ca', $document_ca['data']['attributes']['langcode']);
    $this->assertSame('ca', $document_cafr['data']['attributes']['langcode']);
    $this->assertSame($node->getTitle(), $document['data']['attributes']['title']);
    $this->assertSame($node->getTitle() . ' (ca)', $document_ca['data']['attributes']['title']);
    $this->assertSame($node->getTitle() . ' (ca)', $document_cafr['data']['attributes']['title']);

    // PATCH the 'ca-fr' translation.
    $this->grantPermissions(Role::load(RoleInterface::ANONYMOUS_ID), [
      'bypass node access',
    ]);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options[RequestOptions::BODY] = Json::encode([
      'data' => [
        'type' => 'node--article',
        'id' => $uuid,
        'attributes' => [
          'title' => $document_cafr['data']['attributes']['title'] . ' UPDATED',
        ],
      ],
    ]);
    $response = $this->request('PATCH', Url::fromUri('base:/ca-fr/jsonapi/node/article/' . $this->nodes[0]->uuid()), $request_options);
    $this->assertSame(405, $response->getStatusCode());
    $document = Json::decode((string) $response->getBody());
    $this->assertSame('The requested translation of the resource object does not exist, instead modify one of the translations that do exist: ca, en.', $document['errors'][0]['detail']);
  }

  /**
   * Tests creating a translation.
   */
  public function testPostTranslation() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);
    $this->grantPermissions(Role::load(RoleInterface::ANONYMOUS_ID), [
      'bypass node access',
    ]);

    $title = 'Llamas FTW (ca)';
    $request_document = [
      'data' => [
        'type' => 'node--article',
        'attributes' => [
          'title' => $title,
          'langcode' => 'ca',
        ],
      ],
    ];

    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';

    // Specifying a langcode is forbidden by language_entity_field_access().
    $request_options[RequestOptions::BODY] = Json::encode($request_document);
    $response = $this->request('POST', Url::fromUri('base:/ca/jsonapi/node/article/'), $request_options);
    $this->assertSame(403, $response->getStatusCode());
    $document = Json::decode((string) $response->getBody());
    $this->assertSame('The current user is not allowed to POST the selected field (langcode).', $document['errors'][0]['detail']);

    // Omitting a langcode results in an entity in 'en': the default language of
    // the site.
    unset($request_document['data']['attributes']['langcode']);
    $request_options[RequestOptions::BODY] = Json::encode($request_document);
    $response = $this->request('POST', Url::fromUri('base:/ca/jsonapi/node/article/'), $request_options);
    $this->assertSame(201, $response->getStatusCode());
    $document = Json::decode((string) $response->getBody());
    $this->assertSame($title, $document['data']['attributes']['title']);
    $this->assertSame('en', $document['data']['attributes']['langcode']);
    $this->assertSame(['en'], array_keys(Node::load($document['data']['attributes']['drupal_internal__nid'])->getTranslationLanguages()));

    // Specifying a langcode is allowed once configured to be alterable. Now an
    // entity can be created with the specified langcode.
    ContentLanguageSettings::loadByEntityTypeBundle('node', 'article')
      ->setLanguageAlterable(TRUE)
      ->save();
    $request_document['data']['attributes']['langcode'] = 'ca';
    $request_options[RequestOptions::BODY] = Json::encode($request_document);
    $response = $this->request('POST', Url::fromUri('base:/ca/jsonapi/node/article/'), $request_options);
    $this->assertSame(201, $response->getStatusCode());
    $document = Json::decode((string) $response->getBody());
    $this->assertSame($title, $document['data']['attributes']['title']);
    $this->assertSame('ca', $document['data']['attributes']['langcode']);
    $this->assertSame(['ca'], array_keys(Node::load($document['data']['attributes']['drupal_internal__nid'])->getTranslationLanguages()));

    // Same request, but sent to the URL without the language prefix.
    $response = $this->request('POST', Url::fromUri('base:/jsonapi/node/article/'), $request_options);
    $this->assertSame(201, $response->getStatusCode());
    $document = Json::decode((string) $response->getBody());
    $this->assertSame($title, $document['data']['attributes']['title']);
    $this->assertSame('ca', $document['data']['attributes']['langcode']);
    $this->assertSame(['ca'], array_keys(Node::load($document['data']['attributes']['drupal_internal__nid'])->getTranslationLanguages()));
  }

  /**
   * Tests deleting multilingual content.
   */
  public function testDeleteMultilingual() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);
    $this->grantPermissions(Role::load(RoleInterface::ANONYMOUS_ID), [
      'bypass node access',
    ]);

    $response = $this->request('DELETE', Url::fromUri('base:/ca/jsonapi/node/article/' . $this->nodes[0]->uuid()), []);
    $this->assertSame(405, $response->getStatusCode());
    $document = Json::decode((string) $response->getBody());
    $this->assertSame('Deleting a resource object translation is not yet supported. See https://www.drupal.org/docs/8/modules/jsonapi/translations.', $document['errors'][0]['detail']);

    $response = $this->request('DELETE', Url::fromUri('base:/ca-fr/jsonapi/node/article/' . $this->nodes[0]->uuid()), []);
    $this->assertSame(405, $response->getStatusCode());
    $document = Json::decode((string) $response->getBody());
    $this->assertSame('Deleting a resource object translation is not yet supported. See https://www.drupal.org/docs/8/modules/jsonapi/translations.', $document['errors'][0]['detail']);

    $response = $this->request('DELETE', Url::fromUri('base:/jsonapi/node/article/' . $this->nodes[0]->uuid()), []);
    $this->assertSame(204, $response->getStatusCode());
    $this->assertFalse(Node::load($this->nodes[0]->id()));
  }

}
