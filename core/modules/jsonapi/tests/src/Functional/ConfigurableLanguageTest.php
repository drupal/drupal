<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use GuzzleHttp\RequestOptions;

/**
 * JSON:API integration test for the "ConfigurableLanguage" config entity type.
 *
 * @group jsonapi
 */
class ConfigurableLanguageTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'configurable_language';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'configurable_language--configurable_language';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\Core\Field\Entity\BaseFieldOverride
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer languages']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $configurable_language = ConfigurableLanguage::create([
      'id' => 'll',
      'label' => 'Llama Language',
    ]);
    $configurable_language->save();

    return $configurable_language;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/configurable_language/configurable_language/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'configurable_language--configurable_language',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'dependencies' => [],
          'direction' => 'ltr',
          'label' => 'Llama Language',
          'langcode' => 'en',
          'locked' => FALSE,
          'status' => TRUE,
          'weight' => 0,
          'drupal_internal__id' => 'll',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts(array $sparse_fieldset = NULL) {
    return Cache::mergeContexts(parent::getExpectedCacheContexts(), ['languages:language_interface']);
  }

  /**
   * Test a GET request for a default config entity, which has a _core key.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/2915539
   */
  public function testGetIndividualDefaultConfig() {
    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $url = Url::fromRoute('jsonapi.configurable_language--configurable_language.individual', ['entity' => ConfigurableLanguage::load('en')->uuid()]);
    /* $url = ConfigurableLanguage::load('en')->toUrl('jsonapi'); */

    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());
    $this->setUpAuthorization('GET');
    $response = $this->request('GET', $url, $request_options);

    $normalization = Json::decode((string) $response->getBody());
    $this->assertArrayNotHasKey('_core', $normalization['data']['attributes']);
  }

}
