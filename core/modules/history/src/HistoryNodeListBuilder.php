<?php

namespace Drupal\history;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides NodeListBuilder in order to provide history support.
 */
class HistoryNodeListBuilder extends NodeListBuilder {

  /**
   * Mark content as read.
   *
   * @var int
   */
  const MARK_READ = 0;

  /**
   * Mark content as being new.
   *
   * @var int
   */
  const MARK_NEW = 1;

  /**
   * Mark content as being updated.
   *
   * @var int
   */
  const MARK_UPDATED = 2;

  /**
   * Memory cache for node marks.
   *
   * @var int[]
   */
  protected $nodeMark = [];

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new list builder instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination, RendererInterface $renderer, AccountProxyInterface $current_user) {
    parent::__construct($entity_type, $storage, $date_formatter, $redirect_destination);
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): self {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination'),
      $container->get('renderer'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);

    $mark = [
      '#theme' => 'mark',
      '#mark_type' => $this->getNodeMark($entity),
    ];
    $row['title']['data']['#suffix'] = ' ' . $this->renderer->render($mark);

    return $row;
  }

  /**
   * Determines the type of marker to be displayed for a given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return int
   *   One of the MARK constants.
   */
  protected function getNodeMark(NodeInterface $node): int {
    if ($this->currentUser->isAnonymous()) {
      return static::MARK_READ;
    }
    $nid = $node->id();
    if (!isset($this->nodeMark[$nid])) {
      $this->nodeMark[$nid] = history_read($nid);
    }
    $changed_time = $node->getChangedTime();
    if ($this->nodeMark[$nid] == 0 && $changed_time > HISTORY_READ_LIMIT) {
      return static::MARK_NEW;
    }
    elseif ($changed_time > $this->nodeMark[$nid] && $changed_time > HISTORY_READ_LIMIT) {
      return static::MARK_UPDATED;
    }
    return static::MARK_READ;
  }

}
