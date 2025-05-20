<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\SandboxValidationEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Checks that rsync is available.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class RsyncValidator implements EventSubscriberInterface {

  use StringTranslationTrait;

  public function __construct(
    private readonly ExecutableFinderInterface $executableFinder,
    private readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Checks that rsync is available.
   *
   * @param \Drupal\package_manager\Event\SandboxValidationEvent $event
   *   The event being handled.
   */
  public function validate(SandboxValidationEvent $event): void {
    // If the we are going to change the active directory directly, we don't
    // need rsync.
    if ($event->sandboxManager->isDirectWrite()) {
      return;
    }

    try {
      $this->executableFinder->find('rsync');
      $rsync_found = TRUE;
    }
    catch (LogicException) {
      $rsync_found = FALSE;
    }

    if ($rsync_found === FALSE) {
      $message = $this->t('<code>rsync</code> is not available.');

      if ($this->moduleHandler->moduleExists('help')) {
        $help_url = Url::fromRoute('help.page')
          ->setRouteParameter('name', 'package_manager')
          ->setOption('fragment', 'package-manager-faq-rsync')
          ->toString();

        $message = $this->t('@message See the <a href=":url">Package Manager help</a> for more information on how to resolve this.', [
          '@message' => $message,
          ':url' => $help_url,
        ]);
      }
      $event->addError([$message]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      StatusCheckEvent::class => 'validate',
      PreCreateEvent::class => 'validate',
    ];
  }

}
