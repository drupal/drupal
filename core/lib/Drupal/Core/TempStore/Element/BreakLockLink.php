<?php

namespace Drupal\Core\TempStore\Element;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\Core\Render\RendererInterface;

/**
 * Provides a link to break a tempstore lock.
 *
 * Properties:
 * - #label: The label of the object that is locked.
 * - #lock: \Drupal\Core\TempStore\Lock object.
 * - #url: \Drupal\Core\Url object pointing to the break lock form.
 *
 * Usage example:
 * @code
 * $build['examples_lock'] = [
 *   '#type' => 'break_lock_link',
 *   '#label' => $this->t('example item'),
 *   '#lock' => $tempstore->getMetadata('example_key'),
 *   '#url' => \Drupal\Core\Url::fromRoute('examples.break_lock_form'),
 * ];
 * @endcode
 */
#[RenderElement('break_lock_link')]
class BreakLockLink extends RenderElementBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new BreakLockLink.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected DateFormatterInterface $dateFormatter,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RendererInterface $renderer,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        [$this, 'preRenderLock'],
      ],
    ];
  }

  /**
   * Pre-render callback: Renders a lock into #markup.
   *
   * @param array $element
   *   A structured array with the following keys:
   *   - #label: The label of the object that is locked.
   *   - #lock: The lock object.
   *   - #url: The URL object with the destination to the break lock form.
   *
   * @return array
   *   The passed-in element containing a rendered lock in '#markup'.
   */
  public function preRenderLock($element) {
    if (isset($element['#lock']) && isset($element['#label']) && isset($element['#url'])) {
      /** @var \Drupal\Core\TempStore\Lock $lock */
      $lock = $element['#lock'];
      $age = $this->dateFormatter->formatTimeDiffSince($lock->getUpdated());
      $owner = $this->entityTypeManager->getStorage('user')->load($lock->getOwnerId());
      $username = [
        '#theme' => 'username',
        '#account' => $owner,
      ];
      $element['#markup'] = $this->t('This @label is being edited by user @user, and is therefore locked from editing by others. This lock is @age old. Click here to <a href=":url">break this lock</a>.', [
        '@label' => $element['#label'],
        '@user' => $this->renderer->render($username),
        '@age' => $age,
        ':url' => $element['#url']->toString(),
      ]);
    }
    return $element;
  }

}
