<?php

declare(strict_types=1);

namespace Drupal\package_manager\PathExcluder;

use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Drupal\package_manager\PathLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Excludes vendor hardening files from stage operations.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class VendorHardeningExcluder implements EventSubscriberInterface {

  public function __construct(private readonly PathLocator $pathLocator) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CollectPathsToExcludeEvent::class => 'excludeVendorHardeningFiles',
    ];
  }

  /**
   * Excludes vendor hardening files from stage operations.
   *
   * @param \Drupal\package_manager\Event\CollectPathsToExcludeEvent $event
   *   The event object.
   */
  public function excludeVendorHardeningFiles(CollectPathsToExcludeEvent $event): void {
    // If the core-vendor-hardening plugin (used in the legacy-project template)
    // is present, it may have written security hardening files in the vendor
    // directory. They should always be excluded.
    $vendor_dir = $this->pathLocator->getVendorDirectory();
    $event->addPathsRelativeToProjectRoot([$vendor_dir . '/.htaccess']);
  }

}
