<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\InaccessibleMenuLink.
 */

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\Exception\PluginException;

/**
 * A menu link plugin for wrapping another menu link, in sensitive situations.
 *
 * @see \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators::checkAccess()
 */
class InaccessibleMenuLink extends MenuLinkBase {

  /**
   * The wrapped menu link.
   *
   * @var \Drupal\Core\Menu\MenuLinkInterface
   */
  protected $wrappedLink;

  /**
   * Constructs a new InaccessibleMenuLink.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $wrapped_link
   *   The menu link to wrap.
   */
  public function __construct(MenuLinkInterface $wrapped_link) {
    $this->wrappedLink = $wrapped_link;
    $plugin_definition = [
      'route_name' => '<front>',
      'route_parameters' => [],
      'url' => NULL,
    ] + $this->wrappedLink->getPluginDefinition();
    parent::__construct([], $this->wrappedLink->getPluginId(), $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('Inaccessible');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->wrappedLink->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->wrappedLink->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->wrappedLink->getCacheMaxAge();
  }


  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
    throw new PluginException('Inaccessible menu link plugins do not support updating');
  }

}
