<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\FormTestBase.
 */

namespace Drupal\Tests\Core\Form {

use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a base class for testing form functionality.
 *
 * @see \Drupal\Core\Form\FormBuilder
 */
abstract class FormTestBase extends UnitTestCase {

  /**
   * The form builder being tested.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * @var \Drupal\Core\Form\FormValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formValidator;

  /**
   * @var \Drupal\Core\Form\FormSubmitterInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formSubmitter;

  /**
   * The mocked URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

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
  protected $formCache;

  /**
   * The expirable key value store used by form state cache.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formStateCache;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $controllerResolver;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $csrfToken;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The class results.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $classResolver;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $eventDispatcher;

  /**
   * The expirable key value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $keyValueExpirableFactory;

  /**
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $translationManager;

  /**
   * @var \Drupal\Core\HttpKernel|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $httpKernel;

  public function setUp() {
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $this->formCache = $this->getMock('Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface');
    $this->formStateCache = $this->getMock('Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface');
    $this->keyValueExpirableFactory = $this->getMockBuilder('Drupal\Core\KeyValueStore\KeyValueExpirableFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $this->keyValueExpirableFactory->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap(array(
        array('form', $this->formCache),
        array('form_state', $this->formStateCache),
      )));

    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $this->classResolver = $this->getClassResolverStub();
    $this->csrfToken = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $this->httpKernel = $this->getMockBuilder('Drupal\Core\HttpKernel')
      ->disableOriginalConstructor()
      ->getMock();
    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->request = new Request();
    $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $this->requestStack = new RequestStack();
    $this->requestStack->push($this->request);
    $this->formValidator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs(array($this->requestStack, $this->getStringTranslationStub(), $this->csrfToken))
      ->setMethods(array('drupalSetMessage'))
      ->getMock();
    $this->formSubmitter = $this->getMockBuilder('Drupal\Core\Form\FormSubmitter')
      ->setConstructorArgs(array($this->requestStack, $this->urlGenerator))
      ->setMethods(array('batchGet', 'drupalInstallationAttempted'))
      ->getMock();

    $this->formBuilder = new TestFormBuilder($this->formValidator, $this->formSubmitter, $this->moduleHandler, $this->keyValueExpirableFactory, $this->eventDispatcher, $this->requestStack, $this->classResolver, $this->csrfToken, $this->httpKernel);
    $this->formBuilder->setCurrentUser($this->account);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->formBuilder->drupalStaticReset();
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
   * Simulates a form submission within a request, bypassing submitForm().
   *
   * Calling submitForm() will reset the form builder, if two forms were on the
   * same page, they will be submitted simultaneously.
   *
   * @param string $form_id
   *   The unique string identifying the form.
   * @param \Drupal\Core\Form\FormInterface $form_arg
   *   The form object.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param bool $programmed
   *   Whether $form_state['programmed'] should be set to TRUE or not. If it is
   *   not set to TRUE, you must provide additional data in $form_state for the
   *   submission to take place.
   *
   * @return array
   *   The built form.
   */
  protected function simulateFormSubmission($form_id, FormInterface $form_arg, array &$form_state, $programmed = TRUE) {
    $form_state['build_info']['callback_object'] = $form_arg;
    $form_state['build_info']['args'] = array();
    $form_state['input']['op'] = 'Submit';
    $form_state['programmed'] = $programmed;
    $form_state['submitted'] = TRUE;
    return $this->formBuilder->buildForm($form_id, $form_state);
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
  protected static $seenIds = array();

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
  protected function drupalHtmlClass($class) {
    return $class;
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalHtmlId($id) {
    if (isset(static::$seenIds[$id])) {
      $id = $id . '--' . ++static::$seenIds[$id];
    }
    else {
      static::$seenIds[$id] = 1;
    }
    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function drupalStaticReset($name = NULL) {
    static::$seenIds = array();
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

}
