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
   * @var \Drupal\Core\Form\FormBuilderInterface
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

    $form_arg = $this->getMock('Drupal\Core\Form\FormInterface');
    $form_arg->expects($this->once())
      ->method('getFormId')
      ->will($this->returnValue($expected_form_id));

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

    $form_arg = $this->getMock('Drupal\Core\Form\FormInterface');
    $form_arg->expects($this->once())
      ->method('getFormId')
      ->will($this->returnValue($form_id));
    $form_arg->expects($this->once())
      ->method('buildForm')
      ->will($this->returnValue($expected_form));


    $form = $this->formBuilder->getForm($form_arg);
    $this->assertFormElement($expected_form, $form, 'test');
    $this->assertSame($form_id, $form['#id']);
  }

  /**
   * Tests the buildForm() method with a form object.
   */
  public function testBuildFormWithObject() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();

    $form_arg = $this->getMock('Drupal\Core\Form\FormInterface');
    $form_arg->expects($this->once())
      ->method('buildForm')
      ->will($this->returnValue($expected_form));

    $form_state['build_info']['callback_object'] = $form_arg;
    $form_state['build_info']['args'] = array();

    $form = $this->formBuilder->buildForm($form_id, $form_state);
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
    $this->moduleHandler->expects($this->any())
      ->method('invokeAll')
      ->with('forms', array($form_id, array()))
      ->will($this->returnValue(array(
        'test_form_id_specific' => array(
          'callback' => $base_form_id,
        ),
      )));

    $form_state['build_info']['args'] = array();

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

    $form_arg = $this->getMock('Drupal\Core\Form\FormInterface');
    $form_arg->expects($this->any())
      ->method('buildForm')
      ->will($this->returnValue($expected_form));

    // Do an initial build of the form and track the build ID.
    $form_state = array();
    $form_state['build_info']['callback_object'] = $form_arg;
    $form_state['build_info']['args'] = array();
    $form = $this->formBuilder->buildForm($form_id, $form_state);
    $original_build_id = $form['#build_id'];

    // Rebuild the form, and assert that the build ID has not changed.
    $form_state['rebuild'] = TRUE;
    $form_state['input']['form_id'] = $form_id;
    $form_state['rebuild_info']['copy']['#build_id'] = TRUE;
    $this->formBuilder->processForm($form_id, $form, $form_state);
    $this->assertSame($original_build_id, $form['#build_id']);

    // Rebuild the form again, and assert that there is a new build ID.
    $form_state['rebuild_info'] = array();
    $form = $this->formBuilder->buildForm($form_id, $form_state);
    $this->assertNotSame($original_build_id, $form['#build_id']);
  }

  /**
   * Tests the submitForm() method.
   */
  public function testSubmitForm() {
    $expected_form = test_form_id();
    $expected_form['test']['#required'] = TRUE;
    $expected_form['options']['#required'] = TRUE;
    $expected_form['value']['#required'] = TRUE;

    $form_arg = $this->getMock('Drupal\Core\Form\FormInterface');
    $form_arg->expects($this->any())
      ->method('buildForm')
      ->will($this->returnValue($expected_form));

    $form_state = array();
    $form_state['values']['test'] = $this->randomName();
    $this->formBuilder->submitForm($form_arg, $form_state);
    $errors = $this->formBuilder->getErrors();
    $this->assertNotEmpty($errors['options']);

    $form_state = array();
    $form_state['values']['test'] = $this->randomName();
    $form_state['values']['options'] = 'foo';
    $this->formBuilder->submitForm($form_arg, $form_state);
    $errors = $this->formBuilder->getErrors();
    $this->assertNull($errors);

    $form_state = array();
    $form_state['values']['test'] = $this->randomName();
    $form_state['values']['options'] = $this->randomName();
    $form_state['values']['op'] = 'Submit';
    $this->formBuilder->submitForm($form_arg, $form_state);
    $errors = $this->formBuilder->getErrors();
    $this->assertNotEmpty($errors['options']);
  }

  /**
   * Tests the getCache() method.
   */
  public function testGetCache() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();

    // FormBuilder::buildForm() will be called 3 times, but the form object will
    // only be called twice due to caching.
    $form_arg = $this->getMock('Drupal\Core\Form\FormInterface');
    $form_arg->expects($this->exactly(2))
      ->method('buildForm')
      ->will($this->returnValue($expected_form));

    // The CSRF token and the user authentication are checked each time.
    $this->csrfToken->expects($this->exactly(3))
      ->method('get')
      ->will($this->returnValue('csrf_token'));
    $this->account->expects($this->exactly(3))
      ->method('isAuthenticated')
      ->will($this->returnValue(TRUE));

    // Do an initial build of the form and track the build ID.
    $form_state = array();
    $form_state['build_info']['callback_object'] = $form_arg;
    $form_state['build_info']['args'] = array();
    $form_state['build_info']['files'] = array(array('module' => 'node', 'type' => 'pages.inc'));
    $form_state['cache'] = TRUE;
    $form = $this->formBuilder->buildForm($form_id, $form_state);

    // Rebuild the form, this time setting it up to be cached.
    $form_state['rebuild'] = TRUE;
    $form_state['rebuild_info']['copy']['#build_id'] = TRUE;
    $form_state['input']['form_token'] = $form['#token'];
    $form_state['input']['form_id'] = $form_id;
    $form_state['input']['form_build_id'] = $form['#build_id'];
    $form = $this->formBuilder->buildForm($form_id, $form_state);

    $cached_form = $form;
    $cached_form['#cache_token'] = 'csrf_token';
    // The form cache, form_state cache, and CSRF token validation will only be
    // called on the cached form.
    $this->formCache->expects($this->once())
      ->method('get')
      ->will($this->returnValue($cached_form));
    $this->formStateCache->expects($this->once())
      ->method('get')
      ->will($this->returnValue($form_state));
    $this->csrfToken->expects($this->once())
      ->method('validate')
      ->will($this->returnValue(TRUE));

    // The final form build will not trigger any actual form building, but will
    // use the form cache.
    $this->formBuilder->buildForm($form_id, $form_state);
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

    $form_arg = $this->getMock('Drupal\Core\Form\FormInterface');
    $form_arg->expects($this->any())
      ->method('buildForm')
      ->will($this->returnValue($expected_form));

    // Do an initial build of the form and track the build ID.
    $form_state = array();
    $form_state['build_info']['callback_object'] = $form_arg;
    $form_state['build_info']['args'] = array();
    $this->formBuilder->buildForm($form_id, $form_state);
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
  protected function menuGetItem() {
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
  protected function elementChildren(&$elements, $sort = FALSE) {
    $children = array();
    foreach ($elements as $key => $value) {
      if ($key === '' || $key[0] !== '#') {
        if (is_array($value)) {
          $children[] = $key;
        }
      }
    }
    return $children;
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

  public function buildForm(array $form, array &$form_state) { }
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
