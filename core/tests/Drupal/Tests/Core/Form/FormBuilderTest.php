<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\FormBuilderTest.
 */

namespace Drupal\Tests\Core\Form {

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the form builder.
 */
class FormBuilderTest extends UnitTestCase {

  /**
   * The form builder being tested.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The mocked URL generator.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The mocked module handler.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The expirable key value store used by form cache.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $formCache;

  /**
   * The expirable key value store used by form state cache.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $formStateCache;

  /**
   * The current user.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The CSRF token generator.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Form builder test',
      'description' => 'Tests the form builder.',
      'group' => 'Form API',
    );
  }

  public function setUp() {
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $this->formCache = $this->getMock('Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface');
    $this->formStateCache = $this->getMock('Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface');
    $key_value_expirable_factory = $this->getMockBuilder('\Drupal\Core\KeyValueStore\KeyValueExpirableFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $key_value_expirable_factory->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap(array(
        array('form', $this->formCache),
        array('form_state', $this->formStateCache),
      )));

    $event_dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $translation_manager = $this->getStringTranslationStub();
    $this->csrfToken = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $http_kernel = $this->getMockBuilder('Drupal\Core\HttpKernel')
      ->disableOriginalConstructor()
      ->getMock();

    $this->formBuilder = new TestFormBuilder($this->moduleHandler, $key_value_expirable_factory, $event_dispatcher, $this->urlGenerator, $translation_manager, $this->csrfToken, $http_kernel);
    $this->formBuilder->setRequest(new Request());

    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->formBuilder->setCurrentUser($this->account);

  }

  /**
   * Tests the getFormId() method with a string based form ID.
   */
  public function testGetFormIdWithString() {
    $form_arg = 'foo';

    $form_state = array();
    $form_id = $this->formBuilder->getFormId($form_arg, $form_state);

    $this->assertSame($form_arg, $form_id);
    $this->assertEmpty($form_state);
  }

  /**
   * Tests the getFormId() method with a class name form ID.
   */
  public function testGetFormIdWithClassName() {
    $form_arg = 'Drupal\Tests\Core\Form\TestForm';

    $form_state = array();
    $form_id = $this->formBuilder->getFormId($form_arg, $form_state);

    $this->assertSame('test_form', $form_id);
    $this->assertSame($form_arg, get_class($form_state['build_info']['callback_object']));
  }

  /**
   * Tests the getFormId() method with an injected class name form ID.
   */
  public function testGetFormIdWithInjectedClassName() {
    $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    \Drupal::setContainer($container);

    $form_arg = 'Drupal\Tests\Core\Form\TestFormInjected';

    $form_state = array();
    $form_id = $this->formBuilder->getFormId($form_arg, $form_state);

    $this->assertSame('test_form', $form_id);
    $this->assertSame($form_arg, get_class($form_state['build_info']['callback_object']));
  }

  /**
   * Tests the getFormId() method with a form object.
   */
  public function testGetFormIdWithObject() {
    $expected_form_id = 'my_module_form_id';

    $form_arg = $this->getMockForm($expected_form_id);

    $form_state = array();
    $form_id = $this->formBuilder->getFormId($form_arg, $form_state);

    $this->assertSame($expected_form_id, $form_id);
    $this->assertSame($form_arg, $form_state['build_info']['callback_object']);
  }

  /**
   * Tests the getFormId() method with a base form object.
   */
  public function testGetFormIdWithBaseForm() {
    $expected_form_id = 'my_module_form_id';
    $base_form_id = 'my_module';

    $form_arg = $this->getMock('Drupal\Core\Form\BaseFormIdInterface');
    $form_arg->expects($this->once())
      ->method('getFormId')
      ->will($this->returnValue($expected_form_id));
    $form_arg->expects($this->once())
      ->method('getBaseFormId')
      ->will($this->returnValue($base_form_id));

    $form_state = array();
    $form_id = $this->formBuilder->getFormId($form_arg, $form_state);

    $this->assertSame($expected_form_id, $form_id);
    $this->assertSame($form_arg, $form_state['build_info']['callback_object']);
    $this->assertSame($base_form_id, $form_state['build_info']['base_form_id']);
  }

  /**
   * Tests the redirectForm() method when a redirect is expected.
   *
   * @param array $form_state
   *   An array of form state data to use for the redirect.
   * @param string $result
   *   The URL the redirect is targeting.
   * @param int $status
   *   (optional) The HTTP status code for the redirect.
   *
   * @dataProvider providerTestRedirectWithResult
   */
  public function testRedirectWithResult($form_state, $result, $status = 302) {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromPath')
      ->will($this->returnValueMap(array(
        array(NULL, array('query' => array(), 'absolute' => TRUE), '<front>'),
        array('foo', array('absolute' => TRUE), 'foo'),
        array('bar', array('query' => array('foo' => 'baz'), 'absolute' => TRUE), 'bar'),
        array('baz', array('absolute' => TRUE), 'baz'),
      ))
    );

    $form_state += $this->formBuilder->getFormStateDefaults();
    $redirect = $this->formBuilder->redirectForm($form_state);
    $this->assertSame($result, $redirect->getTargetUrl());
    $this->assertSame($status, $redirect->getStatusCode());
  }

  /**
   * Tests the redirectForm() with redirect_route when a redirect is expected.
   *
   * @param array $form_state
   *   An array of form state data to use for the redirect.
   * @param string $result
   *   The URL the redirect is targeting.
   * @param int $status
   *   (optional) The HTTP status code for the redirect.
   *
   * @dataProvider providerTestRedirectWithRouteWithResult
   */
  public function testRedirectWithRouteWithResult($form_state, $result, $status = 302) {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->will($this->returnValueMap(array(
          array('test_route_a', array(), array('absolute' => TRUE), 'test-route'),
          array('test_route_b', array('key' => 'value'), array('absolute' => TRUE), 'test-route/value'),
        ))
      );

    $form_state += $this->formBuilder->getFormStateDefaults();
    $redirect = $this->formBuilder->redirectForm($form_state);
    $this->assertSame($result, $redirect->getTargetUrl());
    $this->assertSame($status, $redirect->getStatusCode());
  }

  /**
   * Tests the redirectForm() method with a response object.
   */
  public function testRedirectWithResponseObject() {
    $redirect = new RedirectResponse('/example');
    $form_state['redirect'] = $redirect;

    $form_state += $this->formBuilder->getFormStateDefaults();
    $result_redirect = $this->formBuilder->redirectForm($form_state);

    $this->assertSame($redirect, $result_redirect);
  }

  /**
   * Tests the redirectForm() method when no redirect is expected.
   *
   * @param array $form_state
   *   An array of form state data to use for the redirect.
   *
   * @dataProvider providerTestRedirectWithoutResult
   */
  public function testRedirectWithoutResult($form_state) {
    $this->urlGenerator->expects($this->never())
      ->method('generateFromPath');
    $this->urlGenerator->expects($this->never())
      ->method('generateFromRoute');
    $form_state += $this->formBuilder->getFormStateDefaults();
    $redirect = $this->formBuilder->redirectForm($form_state);
    $this->assertNull($redirect);
  }

  /**
   * Provides test data for testing the redirectForm() method with a redirect.
   *
   * @return array
   *   Returns some test data.
   */
  public function providerTestRedirectWithResult() {
    return array(
      array(array(), '<front>'),
      array(array('redirect' => 'foo'), 'foo'),
      array(array('redirect' => array('foo')), 'foo'),
      array(array('redirect' => array('foo')), 'foo'),
      array(array('redirect' => array('bar', array('query' => array('foo' => 'baz')))), 'bar'),
      array(array('redirect' => array('baz', array(), 301)), 'baz', 301),
    );
  }

  /**
   * Provides test data for testing the redirectForm() method with a route name.
   *
   * @return array
   *   Returns some test data.
   */
  public function providerTestRedirectWithRouteWithResult() {
    return array(
      array(array('redirect_route' => array('route_name' => 'test_route_a')), 'test-route'),
      array(array('redirect_route' => array('route_name' => 'test_route_b', 'route_parameters' => array('key' => 'value'))), 'test-route/value'),
    );
  }

  /**
   * Provides test data for testing the redirectForm() method with no redirect.
   *
   * @return array
   *   Returns some test data.
   */
  public function providerTestRedirectWithoutResult() {
    return array(
      array(array('programmed' => TRUE)),
      array(array('rebuild' => TRUE)),
      array(array('no_redirect' => TRUE)),
      array(array('redirect' => FALSE)),
    );
  }

  /**
   * Tests the getForm() method with a string based form ID.
   */
  public function testGetFormWithString() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();

    $form = $this->formBuilder->getForm($form_id);
    $this->assertFormElement($expected_form, $form, 'test');
    $this->assertSame($form_id, $form['#id']);
  }

  /**
   * Tests the getForm() method with a form object.
   */
  public function testGetFormWithObject() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();

    $form_arg = $this->getMockForm($form_id, $expected_form);

    $form = $this->formBuilder->getForm($form_arg);
    $this->assertFormElement($expected_form, $form, 'test');
    $this->assertSame($form_id, $form['#id']);
  }

  /**
   * Tests the getForm() method with a class name based form ID.
   */
  public function testGetFormWithClassString() {
    $form_id = '\Drupal\Tests\Core\Form\TestForm';
    $object = new TestForm();
    $form = array();
    $form_state = array();
    $expected_form = $object->buildForm($form, $form_state);

    $form = $this->formBuilder->getForm($form_id);
    $this->assertFormElement($expected_form, $form, 'test');
    $this->assertSame('test_form', $form['#id']);
  }

  /**
   * Tests the buildForm() method with a string based form ID.
   */
  public function testBuildFormWithString() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();

    $form = $this->formBuilder->getForm($form_id);
    $this->assertFormElement($expected_form, $form, 'test');
    $this->assertSame($form_id, $form['#id']);
  }

  /**
   * Tests the buildForm() method with a class name based form ID.
   */
  public function testBuildFormWithClassString() {
    $form_id = '\Drupal\Tests\Core\Form\TestForm';
    $object = new TestForm();
    $form = array();
    $form_state = array();
    $expected_form = $object->buildForm($form, $form_state);

    $form = $this->formBuilder->buildForm($form_id, $form_state);
    $this->assertFormElement($expected_form, $form, 'test');
    $this->assertSame('test_form', $form['#id']);
  }

  /**
   * Tests the buildForm() method with a form object.
   */
  public function testBuildFormWithObject() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();

    $form_arg = $this->getMockForm($form_id, $expected_form);

    $form_state = array();
    $form = $this->formBuilder->buildForm($form_arg, $form_state);
    $this->assertFormElement($expected_form, $form, 'test');
    $this->assertSame($form_id, $form_state['build_info']['form_id']);
    $this->assertSame($form_id, $form['#id']);
  }

  /**
   * Tests the buildForm() method with a hook_forms() based form ID.
   */
  public function testBuildFormWithHookForms() {
    $form_id = 'test_form_id_specific';
    $base_form_id = 'test_form_id';
    $expected_form = $base_form_id();
    // Set the module handler to return information from hook_forms().
    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('forms', array($form_id, array()))
      ->will($this->returnValue(array(
        'test_form_id_specific' => array(
          'callback' => $base_form_id,
        ),
      )));

    $form_state = array();
    $form = $this->formBuilder->buildForm($form_id, $form_state);
    $this->assertFormElement($expected_form, $form, 'test');
    $this->assertSame($form_id, $form_state['build_info']['form_id']);
    $this->assertSame($form_id, $form['#id']);
    $this->assertSame($base_form_id, $form_state['build_info']['base_form_id']);
  }

  /**
   * Tests the rebuildForm() method.
   */
  public function testRebuildForm() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();

    // The form will be built four times.
    $form_arg = $this->getMock('Drupal\Core\Form\FormInterface');
    $form_arg->expects($this->exactly(2))
      ->method('getFormId')
      ->will($this->returnValue($form_id));
    $form_arg->expects($this->exactly(4))
      ->method('buildForm')
      ->will($this->returnValue($expected_form));

    // Do an initial build of the form and track the build ID.
    $form_state = array();
    $form = $this->formBuilder->buildForm($form_arg, $form_state);
    $original_build_id = $form['#build_id'];

    // Rebuild the form, and assert that the build ID has not changed.
    $form_state['rebuild'] = TRUE;
    $form_state['input']['form_id'] = $form_id;
    $form_state['rebuild_info']['copy']['#build_id'] = TRUE;
    $this->formBuilder->processForm($form_id, $form, $form_state);
    $this->assertSame($original_build_id, $form['#build_id']);

    // Rebuild the form again, and assert that there is a new build ID.
    $form_state['rebuild_info'] = array();
    $form = $this->formBuilder->buildForm($form_arg, $form_state);
    $this->assertNotSame($original_build_id, $form['#build_id']);
  }

  /**
   * Tests the submitForm() method.
   */
  public function testSubmitForm() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();
    $expected_form['test']['#required'] = TRUE;
    $expected_form['options']['#required'] = TRUE;
    $expected_form['value']['#required'] = TRUE;

    $form_arg = $this->getMock('Drupal\Core\Form\FormInterface');
    $form_arg->expects($this->exactly(5))
      ->method('getFormId')
      ->will($this->returnValue($form_id));
    $form_arg->expects($this->exactly(5))
      ->method('buildForm')
      ->will($this->returnValue($expected_form));

    $form_state = array();
    $form_state['values']['test'] = $this->randomName();
    $form_state['values']['op'] = 'Submit';
    $this->formBuilder->submitForm($form_arg, $form_state);
    $errors = $this->formBuilder->getErrors($form_state);
    $this->assertNotEmpty($errors['options']);

    $form_state = array();
    $form_state['values']['test'] = $this->randomName();
    $form_state['values']['options'] = 'foo';
    $form_state['values']['op'] = 'Submit';
    $this->formBuilder->submitForm($form_arg, $form_state);
    $errors = $this->formBuilder->getErrors($form_state);
    $this->assertEmpty($errors);

    $form_state = array();
    $form_state['values']['test'] = $this->randomName();
    $form_state['values']['options'] = array('foo');
    $form_state['values']['op'] = 'Submit';
    $this->formBuilder->submitForm($form_arg, $form_state);
    $errors = $this->formBuilder->getErrors($form_state);
    $this->assertEmpty($errors);

    $form_state = array();
    $form_state['values']['test'] = $this->randomName();
    $form_state['values']['options'] = array('foo', 'baz');
    $form_state['values']['op'] = 'Submit';
    $this->formBuilder->submitForm($form_arg, $form_state);
    $errors = $this->formBuilder->getErrors($form_state);
    $this->assertNotEmpty($errors['options']);

    $form_state = array();
    $form_state['values']['test'] = $this->randomName();
    $form_state['values']['options'] = $this->randomName();
    $form_state['values']['op'] = 'Submit';
    $this->formBuilder->submitForm($form_arg, $form_state);
    $errors = $this->formBuilder->getErrors($form_state);
    $this->assertNotEmpty($errors['options']);
  }

  /**
   * Tests the flattenOptions() method.
   *
   * @dataProvider providerTestFlattenOptions
   */
  public function testFlattenOptions($options) {
    $form_id = 'test_form_id';
    $expected_form = $form_id();
    $expected_form['select']['#required'] = TRUE;
    $expected_form['select']['#options'] = $options;

    $form_arg = $this->getMockForm($form_id, $expected_form);

    $form_state = array();
    $form_state['values']['select'] = 'foo';
    $form_state['values']['op'] = 'Submit';
    $this->formBuilder->submitForm($form_arg, $form_state);
    $errors = $this->formBuilder->getErrors($form_state);
    $this->assertEmpty($errors);
  }

  /**
   * Provides test data for the flattenOptions() method.
   *
   * @return array
   */
  public function providerTestFlattenOptions() {
    $object = new \stdClass();
    $object->option = array('foo' => 'foo');
    return array(
      array(array('foo' => 'foo')),
      array(array(array('foo' => 'foo'))),
      array(array($object)),
    );
  }

  /**
   * Tests the setErrorByName() method.
   *
   * @param array|null $limit_validation_errors
   *   The errors to limit validation for, NULL will run all validation.
   * @param array $expected_errors
   *   The errors expected to be set.
   *
   * @dataProvider providerTestSetErrorByName
   */
  public function testSetErrorByName($limit_validation_errors, $expected_errors) {
    $form_id = 'test_form_id';
    $expected_form = $form_id();
    $expected_form['actions']['submit']['#submit'][] = 'test_form_id_custom_submit';
    $expected_form['actions']['submit']['#limit_validation_errors'] = $limit_validation_errors;

    $form_arg = $this->getMockForm($form_id, $expected_form);
    $form_builder = $this->formBuilder;
    $form_arg->expects($this->once())
      ->method('validateForm')
      ->will($this->returnCallback(function (array &$form, array &$form_state) use ($form_builder) {
        $form_builder->setErrorByName('test', $form_state, 'Fail 1');
        $form_builder->setErrorByName('test', $form_state, 'Fail 2');
        $form_builder->setErrorByName('options', $form_state);
      }));

    $form_state = array();
    $form_state['values']['test'] = $this->randomName();
    $form_state['values']['options'] = 'foo';
    $form_state['values']['op'] = 'Submit';
    $this->formBuilder->submitForm($form_arg, $form_state);

    $errors = $this->formBuilder->getErrors($form_state);
    $this->assertSame($expected_errors, $errors);
  }

  /**
   * Provides test data for testing the setErrorByName() method.
   *
   * @return array
   *   Returns some test data.
   */
  public function providerTestSetErrorByName() {
    return array(
      // Only validate the 'options' element.
      array(array(array('options')), array('options' => '')),
      // Do not limit an validation, and, ensuring the first error is returned
      // for the 'test' element.
      array(NULL, array('test' => 'Fail 1', 'options' => '')),
      // Limit all validation.
      array(array(), array()),
    );
  }

  /**
   * Tests the getError() method.
   *
   * @dataProvider providerTestGetError
   */
  public function testGetError($parents, $expected = NULL) {
    $form_state = array();
    // Set errors on a top level and a child element, and a nested element.
    $this->formBuilder->setErrorByName('foo', $form_state, 'Fail 1');
    $this->formBuilder->setErrorByName('foo][bar', $form_state, 'Fail 2');
    $this->formBuilder->setErrorByName('baz][bim', $form_state, 'Fail 3');

    $element['#parents'] = $parents;
    $error = $this->formBuilder->getError($element, $form_state);
    $this->assertSame($expected, $error);
  }

  /**
   * Provides test data for testing the getError() method.
   *
   * @return array
   *   Returns some test data.
   */
  public function providerTestGetError() {
    return array(
      array(array('foo'), 'Fail 1'),
      array(array('foo', 'bar'), 'Fail 1'),
      array(array('baz')),
      array(array('baz', 'bim'), 'Fail 3'),
      array(array($this->randomName())),
      array(array()),
    );
  }

  /**
   * Tests the getCache() method.
   */
  public function testGetCache() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();
    $expected_form['#token'] = FALSE;

    // FormBuilder::buildForm() will be called twice, but the form object will
    // only be called once due to caching.
    $form_arg = $this->getMockForm($form_id, $expected_form, 1);

    // The CSRF token is checked each time.
    $this->csrfToken->expects($this->exactly(2))
      ->method('get')
      ->will($this->returnValue('csrf_token'));
    // The CSRF token is validated only when retrieving from the cache.
    $this->csrfToken->expects($this->once())
      ->method('validate')
      ->with('csrf_token')
      ->will($this->returnValue(TRUE));
    // The user is checked for authentication once for the form building and
    // twice for each cache set.
    $this->account->expects($this->exactly(3))
      ->method('isAuthenticated')
      ->will($this->returnValue(TRUE));

    // Do an initial build of the form and track the build ID.
    $form_state = array();
    $form_state['build_info']['args'] = array();
    $form_state['build_info']['files'] = array(array('module' => 'node', 'type' => 'pages.inc'));
    $form_state['cache'] = TRUE;
    $form = $this->formBuilder->buildForm($form_arg, $form_state);

    $cached_form = $form;
    $cached_form['#cache_token'] = 'csrf_token';
    // The form cache, form_state cache, and CSRF token validation will only be
    // called on the cached form.
    $this->formCache->expects($this->once())
      ->method('setWithExpire');
    $this->formCache->expects($this->once())
      ->method('get')
      ->will($this->returnValue($cached_form));
    $this->formStateCache->expects($this->once())
      ->method('get')
      ->will($this->returnValue($form_state));

    // The final form build will not trigger any actual form building, but will
    // use the form cache.
    $form_state['input']['form_id'] = $form_id;
    $form_state['input']['form_build_id'] = $form['#build_id'];
    $this->formBuilder->buildForm($form_id, $form_state);
    $errors = $this->formBuilder->getErrors($form_state);
    $this->assertEmpty($errors);
  }

  /**
   * Tests the sendResponse() method.
   *
   * @expectedException \Exception
   */
  public function testSendResponse() {
    $form_id = 'test_form_id';
    $expected_form = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
      ->disableOriginalConstructor()
      ->getMock();
    $expected_form->expects($this->once())
      ->method('prepare')
      ->will($this->returnValue($expected_form));

    $form_arg = $this->getMockForm($form_id, $expected_form);

    // Do an initial build of the form and track the build ID.
    $form_state = array();
    $this->formBuilder->buildForm($form_arg, $form_state);
  }

  /**
   * Provides a mocked form object.
   *
   * @param string $form_id
   *   (optional) The form ID to be used. If none is provided, the form will be
   *   set with no expectation about getFormId().
   * @param mixed $expected_form
   *   (optional) If provided, the expected form response for buildForm() to
   *   return. Defaults to NULL.
   * @param int $count
   *   (optional) The number of times the form is expected to be built. Defaults
   *   to 1.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Form\FormInterface
   *   The mocked form object.
   */
  protected function getMockForm($form_id, $expected_form = NULL, $count = 1) {
    $form = $this->getMock('Drupal\Core\Form\FormInterface');
    $form->expects($this->once())
      ->method('getFormId')
      ->will($this->returnValue($form_id));

    if ($expected_form) {
      $form->expects($this->exactly($count))
        ->method('buildForm')
        ->will($this->returnValue($expected_form));
    }
    return $form;
  }

  /**
   * Asserts that the expected form structure is found in a form for a given key.
   *
   * @param array $expected_form
   *   The expected form structure.
   * @param array $actual_form
   *   The actual form.
   * @param string|null $form_key
   *   (optional) The form key to look in. Otherwise the entire form will be
   *   compared.
   */
  protected function assertFormElement(array $expected_form, array $actual_form, $form_key = NULL) {
    $expected_element = $form_key ? $expected_form[$form_key] : $expected_form;
    $actual_element = $form_key ? $actual_form[$form_key] : $actual_form;
    $this->assertSame(array_intersect_key($expected_element, $actual_element), $expected_element);
  }

}

/**
 * Provides a test form builder class.
 */
class TestFormBuilder extends FormBuilder {

  /**
   * {@inheritdoc}
   */
  protected function sendResponse(Response $response) {
    parent::sendResponse($response);
    // Throw an exception instead of exiting.
    throw new \Exception('exit');
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  public function setCurrentUser(AccountInterface $account) {
    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementInfo($type) {
    $types['token'] = array(
      '#input' => TRUE,
    );
    $types['value'] = array(
      '#input' => TRUE,
    );
    $types['select'] = array(
      '#input' => TRUE,
      '#multiple' => FALSE,
      '#empty_value' => '',
    );
    $types['radios'] = array(
      '#input' => TRUE,
    );
    $types['textfield'] = array(
      '#input' => TRUE,
    );
    $types['submit'] = array(
      '#input' => TRUE,
      '#name' => 'op',
      '#is_button' => TRUE,
    );
    if (!isset($types[$type])) {
      $types[$type] = array();
    }
    return $types[$type];
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalInstallationAttempted() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalSetMessage($message = NULL, $type = 'status', $repeat = FALSE) {
  }

  /**
   * {@inheritdoc}
   */
  protected function watchdog($type, $message, array $variables = NULL, $severity = WATCHDOG_NOTICE, $link = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalHtmlClass($class) {
    return $class;
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalHtmlId($id) {
    return $id;
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalStaticReset($name = NULL) {
  }

}

class TestForm implements FormInterface {
  public function getFormId() {
    return 'test_form';
  }

  public function buildForm(array $form, array &$form_state) {
    return test_form_id();
  }
  public function validateForm(array &$form, array &$form_state) { }
  public function submitForm(array &$form, array &$form_state) { }
}
class TestFormInjected extends TestForm implements ContainerInjectionInterface {
  public static function create(ContainerInterface $container) {
    return new static();
  }
}

}

namespace {
  function test_form_id() {
    $form['test'] = array(
      '#type' => 'textfield',
      '#title' => 'Test',
    );
    $form['select'] = array(
      '#type' => 'select',
      '#options' => array(
        'foo' => 'foo',
        'bar' => 'bar',
      ),
    );
    $form['options'] = array(
      '#type' => 'radios',
      '#options' => array(
        'foo' => 'foo',
        'bar' => 'bar',
      ),
    );
    $form['value'] = array(
      '#type' => 'value',
      '#value' => 'bananas',
    );
    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Submit',
    );
    return $form;
  }
  function test_form_id_custom_submit(array &$form, array &$form_state) {
  }

  if (!defined('WATCHDOG_ERROR')) {
    define('WATCHDOG_ERROR', 3);
  }
  if (!function_exists('batch_get')) {
    function &batch_get() {
      $batch = array();
      return $batch;
    }
  }
}
