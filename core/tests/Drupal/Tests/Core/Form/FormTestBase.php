<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\FormTestBase.
 */

namespace Drupal\Tests\Core\Form {

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
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
   * The form cache.
   *
   * @var \Drupal\Core\Form\FormCacheInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formCache;

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
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $translationManager;

  /**
   * @var \Drupal\Core\DrupalKernelInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $kernel;

  /**
   * @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The mocked theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $themeManager;

  protected function setUp() {
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $this->formCache = $this->getMock('Drupal\Core\Form\FormCacheInterface');
    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $this->classResolver = $this->getClassResolverStub();
    $this->csrfToken = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $this->kernel = $this->getMockBuilder('\Drupal\Core\DrupalKernel')
      ->disableOriginalConstructor()
      ->getMock();
    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->themeManager = $this->getMock('Drupal\Core\Theme\ThemeManagerInterface');
    $this->request = new Request();
    $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $this->requestStack = new RequestStack();
    $this->requestStack->push($this->request);
    $this->logger = $this->getMock('Drupal\Core\Logger\LoggerChannelInterface');
    $this->formValidator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs(array($this->requestStack, $this->getStringTranslationStub(), $this->csrfToken, $this->logger))
      ->setMethods(array('drupalSetMessage'))
      ->getMock();
    $this->formSubmitter = $this->getMockBuilder('Drupal\Core\Form\FormSubmitter')
      ->setConstructorArgs(array($this->requestStack, $this->urlGenerator))
      ->setMethods(array('batchGet', 'drupalInstallationAttempted'))
      ->getMock();

    $this->formBuilder = new TestFormBuilder($this->formValidator, $this->formSubmitter, $this->formCache, $this->moduleHandler, $this->eventDispatcher, $this->requestStack, $this->classResolver, $this->themeManager, $this->csrfToken, $this->kernel);
    $this->formBuilder->setCurrentUser($this->account);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    Html::resetSeenIds();
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
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param bool $programmed
   *   Whether $form_state->setProgrammed() should be passed TRUE or not. If it
   *   is not set to TRUE, you must provide additional data in $form_state for
   *   the submission to take place.
   *
   * @return array
   *   The built form.
   */
  protected function simulateFormSubmission($form_id, FormInterface $form_arg, FormStateInterface $form_state, $programmed = TRUE) {
    $input = $form_state->getUserInput();
    $input['op'] = 'Submit';
    $form_state
      ->setUserInput($input)
      ->setProgrammed($programmed)
      ->setSubmitted();
    return $this->formBuilder->buildForm($form_arg, $form_state);
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
