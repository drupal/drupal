<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\FormCacheTest.
 */

namespace Drupal\Tests\Core\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormCache;
use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Form\FormCache
 * @group Form
 */
class FormCacheTest extends UnitTestCase {

  /**
   * The form cache object under test.
   *
   * @var \Drupal\Core\Form\FormCache
   */
  protected $formCache;

  /**
   * The expirable key value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $keyValueExpirableFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $csrfToken;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The expirable key value store used by form cache.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formCacheStore;

  /**
   * The expirable key value store used by form state cache.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formStateCacheStore;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $logger;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestStack;

  /**
   * A policy rule determining the cacheability of a request.
   *
   * @var \Drupal\Core\PageCache\RequestPolicyInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestPolicy;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->resetSafeMarkup();

    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $this->formCacheStore = $this->getMock('Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface');
    $this->formStateCacheStore = $this->getMock('Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface');
    $this->keyValueExpirableFactory = $this->getMock('Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface');
    $this->keyValueExpirableFactory->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap([
        ['form', $this->formCacheStore],
        ['form_state', $this->formStateCacheStore],
      ]));

    $this->csrfToken = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');

    $this->logger = $this->getMock('Psr\Log\LoggerInterface');
    $this->requestStack = $this->getMock('\Symfony\Component\HttpFoundation\RequestStack');
    $this->requestPolicy = $this->getMock('\Drupal\Core\PageCache\RequestPolicyInterface');

    $this->formCache = new FormCache($this->root, $this->keyValueExpirableFactory, $this->moduleHandler, $this->account, $this->csrfToken, $this->logger, $this->requestStack, $this->requestPolicy);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();
    $this->resetSafeMarkup();
  }

  /**
   * @covers ::getCache
   */
  public function testGetCacheValidToken() {
    $form_build_id = 'the_form_build_id';
    $form_state = new FormState();
    $cache_token = 'the_cache_token';
    $cached_form = ['#cache_token' => $cache_token];

    $this->formCacheStore->expects($this->once())
      ->method('get')
      ->with($form_build_id)
      ->willReturn($cached_form);
    $this->csrfToken->expects($this->once())
      ->method('validate')
      ->with($cache_token)
      ->willReturn(TRUE);
    $this->account->expects($this->never())
      ->method('isAnonymous');

    $form = $this->formCache->getCache($form_build_id, $form_state);
    $this->assertSame($cached_form, $form);
  }

  /**
   * @covers ::getCache
   */
  public function testGetCacheInvalidToken() {
    $form_build_id = 'the_form_build_id';
    $form_state = new FormState();
    $cache_token = 'the_cache_token';
    $cached_form = ['#cache_token' => $cache_token];

    $this->formCacheStore->expects($this->once())
      ->method('get')
      ->with($form_build_id)
      ->willReturn($cached_form);
    $this->csrfToken->expects($this->once())
      ->method('validate')
      ->with($cache_token)
      ->willReturn(FALSE);
    $this->account->expects($this->never())
      ->method('isAnonymous');

    $form = $this->formCache->getCache($form_build_id, $form_state);
    $this->assertNull($form);
  }

  /**
   * @covers ::getCache
   */
  public function testGetCacheAnonUser() {
    $form_build_id = 'the_form_build_id';
    $form_state = new FormState();
    $cached_form = ['#cache_token' => NULL];

    $this->formCacheStore->expects($this->once())
      ->method('get')
      ->with($form_build_id)
      ->willReturn($cached_form);
    $this->account->expects($this->once())
      ->method('isAnonymous')
      ->willReturn(TRUE);
    $this->csrfToken->expects($this->never())
      ->method('validate');

    $form = $this->formCache->getCache($form_build_id, $form_state);
    $this->assertSame($cached_form, $form);
  }

  /**
   * @covers ::getCache
   */
  public function testGetCacheAuthUser() {
    $form_build_id = 'the_form_build_id';
    $form_state = new FormState();
    $cached_form = ['#cache_token' => NULL];

    $this->formCacheStore->expects($this->once())
      ->method('get')
      ->with($form_build_id)
      ->willReturn($cached_form);
    $this->account->expects($this->once())
      ->method('isAnonymous')
      ->willReturn(FALSE);

    $form = $this->formCache->getCache($form_build_id, $form_state);
    $this->assertNull($form);
  }

  /**
   * @covers ::getCache
   */
  public function testGetCacheNoForm() {
    $form_build_id = 'the_form_build_id';
    $form_state = new FormState();
    $cached_form = NULL;

    $this->formCacheStore->expects($this->once())
      ->method('get')
      ->with($form_build_id)
      ->willReturn($cached_form);
    $this->account->expects($this->never())
      ->method('isAnonymous');

    $form = $this->formCache->getCache($form_build_id, $form_state);
    $this->assertNull($form);
  }

  /**
   * @covers ::getCache
   */
  public function testGetCacheImmutableForm() {
    $form_build_id = 'the_form_build_id';
    $form_state = (new FormState())
      ->addBuildInfo('immutable', TRUE);
    $cached_form = [
      '#build_id' => 'the_old_build_form_id',
    ];

    $this->account->expects($this->once())
      ->method('isAnonymous')
      ->willReturn(TRUE);
    $this->formCacheStore->expects($this->once())
      ->method('get')
      ->with($form_build_id)
      ->willReturn($cached_form);

    $form = $this->formCache->getCache($form_build_id, $form_state);
    $this->assertSame($cached_form['#build_id'], $form['#build_id_old']);
    $this->assertNotSame($cached_form['#build_id'], $form['#build_id']);
    $this->assertSame($form['#build_id'], $form['form_build_id']['#value']);
    $this->assertSame($form['#build_id'], $form['form_build_id']['#id']);
  }

  /**
   * @covers ::loadCachedFormState
   */
  public function testLoadCachedFormState() {
    $form_build_id = 'the_form_build_id';
    $form_state = new FormState();
    $cached_form = ['#cache_token' => NULL];

    $this->formCacheStore->expects($this->once())
      ->method('get')
      ->with($form_build_id)
      ->willReturn($cached_form);
    $this->account->expects($this->once())
      ->method('isAnonymous')
      ->willReturn(TRUE);

    $cached_form_state = ['storage' => ['foo' => 'bar']];
    $this->formStateCacheStore->expects($this->once())
      ->method('get')
      ->with($form_build_id)
      ->willReturn($cached_form_state);

    $this->formCache->getCache($form_build_id, $form_state);
    $this->assertSame($cached_form_state['storage'], $form_state->getStorage());
  }

  /**
   * @covers ::loadCachedFormState
   */
  public function testLoadCachedFormStateWithFiles() {
    $form_build_id = 'the_form_build_id';
    $form_state = new FormState();
    $cached_form = ['#cache_token' => NULL];

    $this->formCacheStore->expects($this->once())
      ->method('get')
      ->with($form_build_id)
      ->willReturn($cached_form);
    $this->account->expects($this->once())
      ->method('isAnonymous')
      ->willReturn(TRUE);

    $cached_form_state = ['build_info' => ['files' => [
      [
        'module' => 'a_module',
        'type' => 'the_type',
        'name' => 'some_name',
      ],
      [
        'module' => 'another_module',
      ],
    ]]];
    $this->moduleHandler->expects($this->at(0))
      ->method('loadInclude')
      ->with('a_module', 'the_type', 'some_name');
    $this->moduleHandler->expects($this->at(1))
      ->method('loadInclude')
      ->with('another_module', 'inc', 'another_module');
    $this->formStateCacheStore->expects($this->once())
      ->method('get')
      ->with($form_build_id)
      ->willReturn($cached_form_state);

    $this->formCache->getCache($form_build_id, $form_state);
  }

  /**
   * @covers ::loadCachedFormState
   */
  public function testLoadCachedFormStateWithSafeStrings() {
    $this->assertEmpty(SafeMarkup::getAll());
    $form_build_id = 'the_form_build_id';
    $form_state = new FormState();
    $cached_form = ['#cache_token' => NULL];

    $this->formCacheStore->expects($this->once())
      ->method('get')
      ->with($form_build_id)
      ->willReturn($cached_form);
    $this->account->expects($this->once())
      ->method('isAnonymous')
      ->willReturn(TRUE);

    $cached_form_state = ['build_info' => ['safe_strings' => [
      'a_safe_string' => ['html' => TRUE],
    ]]];
    $this->formStateCacheStore->expects($this->once())
      ->method('get')
      ->with($form_build_id)
      ->willReturn($cached_form_state);

    $this->formCache->getCache($form_build_id, $form_state);
  }

  /**
   * @covers ::setCache
   */
  public function testSetCacheWithForm() {
    $form_build_id = 'the_form_build_id';
    $form = [
      '#form_id' => 'the_form_id'
    ];
    $form_state = new FormState();

    $this->formCacheStore->expects($this->once())
      ->method('setWithExpire')
      ->with($form_build_id, $form, $this->isType('int'));

    $form_state_data = $form_state->getCacheableArray();
    $form_state_data['build_info']['safe_strings'] = [];
    $this->formStateCacheStore->expects($this->once())
      ->method('setWithExpire')
      ->with($form_build_id, $form_state_data, $this->isType('int'));

    $this->formCache->setCache($form_build_id, $form, $form_state);
  }

  /**
   * @covers ::setCache
   */
  public function testSetCacheWithoutForm() {
    $form_build_id = 'the_form_build_id';
    $form = NULL;
    $form_state = new FormState();

    $this->formCacheStore->expects($this->never())
      ->method('setWithExpire');

    $form_state_data = $form_state->getCacheableArray();
    $form_state_data['build_info']['safe_strings'] = [];
    $this->formStateCacheStore->expects($this->once())
      ->method('setWithExpire')
      ->with($form_build_id, $form_state_data, $this->isType('int'));

    $this->formCache->setCache($form_build_id, $form, $form_state);
  }

  /**
   * @covers ::setCache
   */
  public function testSetCacheAuthUser() {
    $form_build_id = 'the_form_build_id';
    $form = [];
    $form_state = new FormState();

    $cache_token = 'the_cache_token';
    $form_data = $form;
    $form_data['#cache_token'] = $cache_token;
    $this->formCacheStore->expects($this->once())
      ->method('setWithExpire')
      ->with($form_build_id, $form_data, $this->isType('int'));

    $form_state_data = $form_state->getCacheableArray();
    $form_state_data['build_info']['safe_strings'] = [];
    $this->formStateCacheStore->expects($this->once())
      ->method('setWithExpire')
      ->with($form_build_id, $form_state_data, $this->isType('int'));

    $this->csrfToken->expects($this->once())
      ->method('get')
      ->willReturn($cache_token);
    $this->account->expects($this->once())
      ->method('isAuthenticated')
      ->willReturn(TRUE);

    $this->formCache->setCache($form_build_id, $form, $form_state);
  }

  /**
   * @covers ::setCache
   */
  public function testSetCacheWithSafeStrings() {
    SafeMarkup::set('a_safe_string');
    $form_build_id = 'the_form_build_id';
    $form = [
      '#form_id' => 'the_form_id'
    ];
    $form_state = new FormState();

    $this->formCacheStore->expects($this->once())
      ->method('setWithExpire')
      ->with($form_build_id, $form, $this->isType('int'));

    $form_state_data = $form_state->getCacheableArray();
    $form_state_data['build_info']['safe_strings'] = [
      'a_safe_string' => ['html' => TRUE],
    ];
    $this->formStateCacheStore->expects($this->once())
      ->method('setWithExpire')
      ->with($form_build_id, $form_state_data, $this->isType('int'));

    $this->formCache->setCache($form_build_id, $form, $form_state);
  }

  /**
   * @covers ::setCache
   */
  public function testSetCacheBuildIdMismatch() {
    $form_build_id = 'the_form_build_id';
    $form = [
      '#form_id' => 'the_form_id',
      '#build_id' => 'stale_form_build_id',
    ];
    $form_state = new FormState();

    $this->formCacheStore->expects($this->never())
      ->method('setWithExpire');
    $this->formStateCacheStore->expects($this->never())
      ->method('setWithExpire');
    $this->logger->expects($this->once())
      ->method('error')
      ->with('Form build-id mismatch detected while attempting to store a form in the cache.');
    $this->formCache->setCache($form_build_id, $form, $form_state);
  }


  /**
   * @covers ::deleteCache
   */
  public function testDeleteCache() {
    $form_build_id = 'the_form_build_id';

    $this->formCacheStore->expects($this->once())
      ->method('delete')
      ->with($form_build_id);
    $this->formStateCacheStore->expects($this->once())
      ->method('delete')
      ->with($form_build_id);
    $this->formCache->deleteCache($form_build_id);
  }

  /**
   * Ensures SafeMarkup does not bleed from one test to another.
   */
  protected function resetSafeMarkup() {
    $property = (new \ReflectionClass('Drupal\Component\Utility\SafeMarkup'))->getProperty('safeStrings');
    $property->setAccessible(TRUE);
    $property->setValue(array());
  }

}
