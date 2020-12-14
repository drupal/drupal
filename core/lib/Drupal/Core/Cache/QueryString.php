<?php

namespace Drupal\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class QueryString.
 *
 * A dummy query-string handler service, to gain control over
 * browser-caching. The string changes on every update or full cache
 * flush, forcing browsers to load a new copy of the files, as the
 * URL changed.
 *
 * @package Drupal\Core\Cache
 *
 * @internal
 */
class QueryString implements QueryStringInterface {

  /**
   * System time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * QueryString constructor.
   *
   * A dummy query-string handler service, to gain control over
   * browser-caching. The string changes on every update or full cache
   * flush, forcing browsers to load a new copy of the files, as the
   * URL changed.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   System time service.
   */
  public function __construct(StateInterface $state, TimeInterface $time) {
    $this->state = $state;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   *
   * @internal
   */
  public function reset(string $value = NULL): void {
    // The timestamp is converted to base 36 in order to make it more compact.
    $this->state->set('system.css_js_query_string', $value ?? base_convert($this->time->getRequestTime(), 10, 36));
  }

  /**
   * {@inheritdoc}
   *
   * @internal
   */
  public function get(): string {
    return $this->state->get('system.css_js_query_string', '0');
  }

}
