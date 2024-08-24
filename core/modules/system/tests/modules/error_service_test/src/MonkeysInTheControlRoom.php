<?php

declare(strict_types=1);

namespace Drupal\error_service_test;

use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * A http middleware designed to cause bedlam.
 *
 * @see error_service_test.services.yml
 */
class MonkeysInTheControlRoom implements HttpKernelInterface {

  /**
   * The app kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $app;

  /**
   * The settings.
   */
  protected Settings $settings;

  /**
   * MonkeysInTheControlRoom constructor.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $app
   *   The wrapper HTTP kernel.
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings object.
   */
  public function __construct(HttpKernelInterface $app, Settings $settings) {
    $this->app = $app;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    if (\Drupal::state()->get('error_service_test.break_bare_html_renderer')) {
      // Let the bedlam begin.
      // 1) Force a container rebuild.
      /** @var \Drupal\Core\DrupalKernelInterface $kernel */
      $kernel = \Drupal::service('kernel');
      $kernel->rebuildContainer();
      // 2) Fetch the in-situ container builder.
      $container = ErrorServiceTestServiceProvider::$containerBuilder;
      // Ensure the compiler pass worked.
      if (!$container) {
        throw new \Exception('Oh oh, monkeys stole the ServiceProvider.');
      }
      // Stop the theme manager from being found - and triggering error
      // maintenance mode.
      $container->removeDefinition('theme.manager');
      // Mash. Mash. Mash.
      \Drupal::setContainer($container);
      throw new \Exception('Oh oh, bananas in the instruments.');
    }

    if (\Drupal::state()->get('error_service_test.break_logger')) {
      throw new \Exception('Deforestation');
    }

    return $this->app->handle($request, $type, $catch);
  }

}
