<?php

declare(strict_types=1);

namespace Drupal\automated_cron\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * A subscriber running cron after a response is sent.
 */
class AutomatedCron implements EventSubscriberInterface {

  public function __construct(
    #[AutowireServiceClosure('cron')]
    protected readonly \Closure $cron,
    protected readonly ConfigFactoryInterface $configFactory,
    protected StateInterface $state,
  ) {}

  /**
   * Run the automated cron if enabled.
   *
   * @param \Symfony\Component\HttpKernel\Event\TerminateEvent $event
   *   The Event to process.
   */
  public function onTerminate(TerminateEvent $event): void {
    $interval = $this->configFactory->get('automated_cron.settings')->get('interval');
    if ($interval > 0) {
      $cron_next = $this->state->get('system.cron_last', 0) + $interval;
      if ((int) $event->getRequest()->server->get('REQUEST_TIME') > $cron_next) {
        ($this->cron)()->run();
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents(): array {
    return [KernelEvents::TERMINATE => [['onTerminate', 100]]];
  }

}
