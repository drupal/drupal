<?php

declare(strict_types=1);

namespace Drupal\announcements_feed\Plugin\Block;

use Drupal\announcements_feed\AnnounceRenderer;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Announcements Feed' block.
 *
 * @Block(
 *   id = "announce_block",
 *   admin_label = @Translation("Announcements Feed"),
 * )
 *
 * @internal
 */
class AnnounceBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new AnnouncementsFeedBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\announcements_feed\AnnounceRenderer $announceRenderer
   *   The AnnounceRenderer service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected AnnounceRenderer $announceRenderer, protected AccountInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('announcements_feed.renderer'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    return AccessResult::allowedIfHasPermission($this->currentUser, 'access announcements');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->announceRenderer->render();
  }

}
