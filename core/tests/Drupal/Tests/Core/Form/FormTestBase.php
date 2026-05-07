<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DrupalKernel;
use Drupal\Core\EventSubscriber\RedirectResponseSubscriber;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormCacheInterface;
use Drupal\Core\Form\FormErrorHandlerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormValidator;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Utility\CallableResolver;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

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
   * @var \Drupal\Core\Form\FormValidatorInterface
   */
  protected $formValidator;

  /**
   * The form submitter.
   */
  protected StubFormSubmitter $formSubmitter;

  /**
   * The mocked URL generator.
   */
  protected UrlGeneratorInterface&Stub $urlGenerator;

  /**
   * The mocked module handler.
   */
  protected ModuleHandlerInterface&Stub $moduleHandler;

  /**
   * The form cache.
   */
  protected FormCacheInterface&Stub $formCache;

  /**
   * The cache backend to use.
   */
  protected CacheBackendInterface&Stub $cache;

  /**
   * The current user.
   */
  protected AccountInterface&Stub $account;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $controllerResolver;

  /**
   * The CSRF token generator.
   */
  protected CsrfTokenGenerator&Stub $csrfToken;

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
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $classResolver;

  /**
   * The element info manager.
   */
  protected ElementInfoManagerInterface&Stub $elementInfo;

  /**
   * The event dispatcher.
   */
  protected EventDispatcherInterface&Stub $eventDispatcher;

  /**
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $translationManager;

  /**
   * The Drupal kernel.
   */
  protected DrupalKernel&Stub $kernel;

  /**
   * The logger.
   */
  protected LoggerInterface&Stub $logger;

  /**
   * The redirect response subscriber.
   */
  protected RedirectResponseSubscriber&Stub $redirectResponseSubscriber;

  /**
   * The theme manager.
   */
  protected ThemeManagerInterface&Stub $themeManager;

  /**
   * The callable resolver.
   */
  protected CallableResolver | Stub $callableResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->moduleHandler = $this->createStub(ModuleHandlerInterface::class);

    $this->formCache = $this->createStub(FormCacheInterface::class);
    $this->cache = $this->createStub(CacheBackendInterface::class);
    $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
    $this->redirectResponseSubscriber = $this->createStub(RedirectResponseSubscriber::class);

    $this->classResolver = $this->getClassResolverStub();

    $this->elementInfo = $this->createStub(ElementInfoManagerInterface::class);
    $this->elementInfo
      ->method('getInfo')
      ->willReturnCallback([$this, 'getInfo']);

    $this->csrfToken = $this->createStub(CsrfTokenGenerator::class);
    $this->kernel = $this->createStub(DrupalKernel::class);
    $this->account = $this->createStub(AccountInterface::class);
    $this->themeManager = $this->createStub(ThemeManagerInterface::class);
    $this->request = Request::createFromGlobals();
    $this->request->setSession(new Session(new MockArraySessionStorage()));
    $this->eventDispatcher = $this->createStub(EventDispatcherInterface::class);
    $this->requestStack = new RequestStack();
    $this->requestStack->push($this->request);
    $this->logger = $this->createStub(LoggerChannelInterface::class);
    $form_error_handler = $this->createStub(FormErrorHandlerInterface::class);
    $this->callableResolver = $this->createStub(CallableResolver::class);
    $this->formValidator = new FormValidator($this->requestStack, $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $form_error_handler, $this->callableResolver);
    $this->formSubmitter = new StubFormSubmitter(
      $this->requestStack,
      $this->urlGenerator,
      $this->redirectResponseSubscriber,
      $this->callableResolver,
    );

    $this->formBuilder = new FormBuilder($this->formValidator, $this->formSubmitter, $this->formCache, $this->moduleHandler, $this->eventDispatcher, $this->requestStack, $this->classResolver, $this->elementInfo, $this->themeManager, $this->csrfToken, $this->callableResolver);
  }

  /**
   * Reinitializes the CSRF token generator as a mock object.
   */
  protected function setUpMockCsrfTokenGenerator(): void {
    $this->csrfToken = $this->createMock(CsrfTokenGenerator::class);
    $reflection = new \ReflectionProperty($this->formBuilder, 'csrfToken');
    $reflection->setValue($this->formBuilder, $this->csrfToken);
  }

  /**
   * Reinitializes the form cache as a mock object.
   */
  protected function setUpMockFormCacheInterface(): void {
    $this->formCache = $this->createMock(FormCacheInterface::class);
    $reflection = new \ReflectionProperty($this->formBuilder, 'formCache');
    $reflection->setValue($this->formBuilder, $this->formCache);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    Html::resetSeenIds();
    (new FormState())->clearErrors();
    parent::tearDown();
  }

  /**
   * Provides a mocked form object.
   *
   * @param string $form_id
   *   The form ID to be used.
   * @param mixed $expected_form
   *   (optional) If provided, the expected form response for buildForm() to
   *   return. Defaults to NULL.
   * @param int $count
   *   (optional) The number of times the form is expected to be built. Defaults
   *   to 1.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Form\FormInterface
   *   The mocked form object.
   */
  protected function getMockForm($form_id, $expected_form = NULL, int $count = 1) {
    $form = $this->createMock('Drupal\Core\Form\FormInterface');
    $form->expects($this->once())
      ->method('getFormId')
      ->willReturn($form_id);

    if ($expected_form) {
      $form->expects($this->exactly($count))
        ->method('buildForm')
        ->willReturn($expected_form);
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

  /**
   * A stub method returning properties for the defined element type.
   *
   * @param string $type
   *   The machine name of an element type plugin.
   *
   * @return array
   *   An array with dummy values to be used in tests. Defaults to an empty
   *   array.
   */
  public function getInfo($type) {
    $types['hidden'] = [
      '#input' => TRUE,
    ];
    $types['token'] = [
      '#input' => TRUE,
    ];
    $types['value'] = [
      '#input' => TRUE,
    ];
    $types['radios'] = [
      '#input' => TRUE,
    ];
    $types['textfield'] = [
      '#input' => TRUE,
    ];
    $types['submit'] = [
      '#input' => TRUE,
      '#name' => 'op',
      '#is_button' => TRUE,
    ];
    if (!isset($types[$type])) {
      $types[$type] = [];
    }
    return $types[$type];
  }

}
