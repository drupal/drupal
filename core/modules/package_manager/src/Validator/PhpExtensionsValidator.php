<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\Component\FileSystem\FileSystem as DrupalFilesystem;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreOperationStageEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Performs validation if certain PHP extensions are enabled.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
class PhpExtensionsValidator implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Indicates if a particular PHP extension is loaded.
   *
   * @param string $name
   *   The name of the PHP extension to check for.
   *
   * @return bool
   *   TRUE if the given extension is loaded, FALSE otherwise.
   */
  final protected function isExtensionLoaded(string $name): bool {
    // If and ONLY if we're currently running a test, allow the list of loaded
    // extensions to be overridden by a state variable.
    if (self::insideTest()) {
      // By default, assume OpenSSL is enabled and Xdebug isn't. This allows us
      // to run tests in environments that we might not support in production,
      // such as Drupal CI.
      $loaded_extensions = \Drupal::state()
        ->get('package_manager_loaded_php_extensions', ['openssl']);
      return in_array($name, $loaded_extensions, TRUE);
    }
    return extension_loaded($name);
  }

  /**
   * Flags a warning if Xdebug is enabled.
   *
   * @param \Drupal\package_manager\Event\StatusCheckEvent $event
   *   The event object.
   */
  public function validateXdebug(StatusCheckEvent $event): void {
    if ($this->isExtensionLoaded('xdebug')) {
      $event->addWarning([
        $this->t('Xdebug is enabled, which may have a negative performance impact on Package Manager and any modules that use it.'),
      ]);
    }
  }

  /**
   * Flags an error if the OpenSSL extension is not installed.
   *
   * @param \Drupal\package_manager\Event\PreOperationStageEvent $event
   *   The event object.
   */
  public function validateOpenSsl(PreOperationStageEvent $event): void {
    if (!$this->isExtensionLoaded('openssl')) {
      $message = $this->t('The OpenSSL extension is not enabled, which is a security risk. See <a href=":url">the PHP documentation</a> for information on how to enable this extension.', [
        ':url' => 'https://www.php.net/manual/en/openssl.installation.php',
      ]);
      $event->addError([$message]);
    }
  }

  /**
   * Whether this validator is running inside a test.
   *
   * @return bool
   */
  private static function insideTest(): bool {
    // @see \Drupal\Core\CoreServiceProvider::registerTest()
    $in_functional_test = drupal_valid_test_ua();
    // @see \Drupal\Core\DependencyInjection\DependencySerializationTrait::__wakeup()
    $in_kernel_test = isset($GLOBALS['__PHPUNIT_BOOTSTRAP']);
    // @see \Drupal\BuildTests\Framework\BuildTestBase::setUp()
    $in_build_test = str_contains(__FILE__, DrupalFilesystem::getOsTemporaryDirectory() . '/build_workspace_');
    return $in_functional_test || $in_kernel_test || $in_build_test;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      StatusCheckEvent::class => [
        ['validateXdebug'],
        ['validateOpenSsl'],
      ],
      PreCreateEvent::class => ['validateOpenSsl'],
      PreApplyEvent::class => ['validateOpenSsl'],
    ];
  }

}
