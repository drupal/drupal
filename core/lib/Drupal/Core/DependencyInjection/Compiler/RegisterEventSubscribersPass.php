<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

/**
 * Wraps the Symfony event subscriber pass to use different tag names.
 */
class RegisterEventSubscribersPass implements CompilerPassInterface {

  /**
   * Constructs a RegisterEventSubscribersPass object.
   *
   * @param \Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass $pass
   *   The Symfony compiler pass that registers event subscribers.
   */
  public function __construct(
    protected RegisterListenersPass $pass,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    $this->renameTag($container, 'event_subscriber', 'kernel.event_subscriber');
    $this->pass->process($container);
    $this->renameTag($container, 'kernel.event_subscriber', 'event_subscriber');
  }

  /**
   * Renames tags in the container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container.
   * @param string $source_tag
   *   The tag to be renamed.
   * @param string $target_tag
   *   The tag to rename with.
   */
  protected function renameTag(ContainerBuilder $container, string $source_tag, string $target_tag): void {
    foreach ($container->getDefinitions() as $definition) {
      if ($definition->hasTag($source_tag)) {
        $attributes = $definition->getTag($source_tag)[0];
        $definition->addTag($target_tag, $attributes);
        $definition->clearTag($source_tag);
      }
    }
  }

}
