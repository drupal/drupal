<?php

declare(strict_types=1);

namespace Drupal\Core\Command;

use Drupal\Core\DrupalKernel;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as ComponentEventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Contains helper methods for console commands that boot up Drupal.
 */
trait BootableCommandTrait {

  /**
   * The class loader.
   *
   * @var object
   */
  protected object $classLoader;

  /**
   * Boots up a Drupal environment.
   *
   * @return \Drupal\Core\DrupalKernelInterface
   *   The Drupal kernel.
   *
   * @throws \Exception
   *   Exception thrown if kernel does not boot.
   */
  protected function boot(): DrupalKernelInterface {
    $kernel = new DrupalKernel('prod', $this->classLoader);
    $kernel::bootEnvironment();
    $kernel->setSitePath($this->getSitePath());
    Settings::initialize($kernel->getAppRoot(), $kernel->getSitePath(), $this->classLoader);
    $kernel->boot();
    // The request needs to be created with a URL that, even if not actually
    // reachable, at least has a valid *form*, so that Drupal can correctly
    // generate links and URLs.
    $request = Request::create('http://' . basename($kernel->getSitePath()) . '/core/scripts/drupal');
    $kernel->preHandle($request);

    // Try to register an event listener to properly terminate the Drupal kernel
    // when the console application itself terminates. This ensures that
    // `kernel.destructable_services` are destructed, which in turn ensures that
    // the router can be rebuilt if needed, along with other services that
    // perform actions on destruct.
    $event_dispatcher = $kernel->getContainer()
      ->get(EventDispatcherInterface::class);

    if ($kernel instanceof TerminableInterface && $event_dispatcher instanceof ComponentEventDispatcherInterface) {
      $event_dispatcher->addListener(ConsoleEvents::TERMINATE, function () use ($kernel, $request): void {
        $kernel->terminate($request, new Response());
      });
      $this->getApplication()->setDispatcher($event_dispatcher);
    }
    return $kernel;
  }

  /**
   * Gets the site path.
   *
   * Defaults to 'sites/default'. For testing purposes this can be overridden
   * using the DRUPAL_DEV_SITE_PATH environment variable.
   *
   * @return string
   *   The site path to use.
   */
  protected function getSitePath(): string {
    return getenv('DRUPAL_DEV_SITE_PATH') ?: 'sites/default';
  }

}
