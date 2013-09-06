<?php
/**
 * @file
 * Contains \Drupal\locale\Controller\LocaleController.
 */

namespace Drupal\locale\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
/**
 * Return response for manual check translations.
 */
class LocaleController implements ContainerInjectionInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\locale\Controller\LocaleController object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler, UrlGeneratorInterface $url_generator) {
    $this->moduleHandler = $module_handler;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('url_generator')
    );
  }

  /**
   * Checks for translation updates and displays the translations status.
   *
   * Manually checks the translation status without the use of cron.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to translations reports page.
   */
  public function checkTranslation() {
    $this->moduleHandler->loadInclude('locale', 'inc', 'locale.compare');

    // Check translation status of all translatable project in all languages.
    // First we clear the cached list of projects. Although not strictly
    // nescessary, this is helpfull in case the project list is out of sync.
    locale_translation_flush_projects();
    locale_translation_check_projects();

    // Execute a batch if required. A batch is only used when remote files
    // are checked.
    if (batch_get()) {
      return batch_process('admin/reports/translations');
    }

    return new RedirectResponse($this->urlGenerator->generateFromPath('admin/reports/translations', array('absolute' => TRUE)));
  }
}
