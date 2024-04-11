<?php

namespace Drupal\language_test\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\language\ConfigurableLanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Controller routines for language_test routes.
 */
class LanguageTestController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The HTTP kernel service.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new LanguageTestController object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   An HTTP kernel.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(HttpKernelInterface $httpKernel, LanguageManagerInterface $language_manager) {
    $this->httpKernel = $httpKernel;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('http_kernel'), $container->get('language_manager'));
  }

  /**
   * Route entity upcasting test helper.
   *
   * @param \Drupal\language\ConfigurableLanguageInterface $configurable_language
   *   The ConfigurableLanguage object from the route.
   *
   * @return string
   *   Testing feedback based on (translated) entity title.
   */
  public function testEntity(ConfigurableLanguageInterface $configurable_language) {
    return ['#markup' => $this->t('Loaded %label.', ['%label' => $configurable_language->label()])];
  }

  /**
   * Returns links to the current page with different langcodes.
   *
   * Using #type 'link' causes these links to be rendered with the link
   * generator.
   */
  public function typeLinkActiveClass() {
    // We assume that 'en' and 'fr' have been configured.
    $languages = $this->languageManager->getLanguages();
    return [
      'no_language' => [
        '#type' => 'link',
        '#title' => t('Link to the current path with no langcode provided.'),
        '#url' => Url::fromRoute('<current>'),
        '#options' => [
          'attributes' => [
            'id' => 'no_lang_link',
          ],
          'set_active_class' => TRUE,
        ],
      ],
      'fr' => [
        '#type' => 'link',
        '#title' => t('Link to a French version of the current path.'),
        '#url' => Url::fromRoute('<current>'),
        '#options' => [
          'language' => $languages['fr'],
          'attributes' => [
            'id' => 'fr_link',
          ],
          'set_active_class' => TRUE,
        ],
      ],
      'en' => [
        '#type' => 'link',
        '#title' => t('Link to an English version of the current path.'),
        '#url' => Url::fromRoute('<current>'),
        '#options' => [
          'language' => $languages['en'],
          'attributes' => [
            'id' => 'en_link',
          ],
          'set_active_class' => TRUE,
        ],
      ],
    ];
  }

  /**
   * Uses a sub request to retrieve the 'user' page.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The kernels response to the sub request.
   */
  public function testSubRequest() {
    $request = Request::createFromGlobals();
    $server = $request->server->all();
    if (basename($server['SCRIPT_FILENAME']) != basename($server['SCRIPT_NAME'])) {
      // We need this for when the test is executed by run-tests.sh.
      // @todo Remove this once run-tests.sh has been converted to use a Request
      //   object.
      $server['SCRIPT_FILENAME'] = $server['SCRIPT_NAME'];
      $base_path = ltrim($server['REQUEST_URI'], '/');
    }
    else {
      $base_path = $request->getBasePath();
    }
    $sub_request = Request::create($base_path . '/user', 'GET', $request->query->all(), $request->cookies->all(), [], $server);
    return $this->httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST);
  }

}
