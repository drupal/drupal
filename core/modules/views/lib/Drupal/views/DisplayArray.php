<?php

/**
 * @file
 * Contains \Drupal\views\DisplayArray.
 */

namespace Drupal\views;

/**
 * A class which wraps the displays of a view so you can lazy-initialize them.
 */
class DisplayArray implements \ArrayAccess, \Iterator, \Countable {

  /**
   * Stores a reference to the view which has this displays attached.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * Stores the actual display instances in an array.
   *
   * @var array
   */
  protected $displayHandlers = array();

  /**
   * Stores all display IDs, coming from $this->view->storage->get('display').
   *
   * @var array
   */
  protected $displayIDs;

  /**
   * Constructs a DisplayArray object.
   *
   * @param \Drupal\views\ViewExecutable
   *   The view which has this displays attached.
   */
  public function __construct(ViewExecutable $view) {
    $this->view = $view;

    $this->initializeDisplay('default');

    // Store all display IDs to access them easy and fast.
    $display = $this->view->storage->get('display');
    $this->displayIDs = drupal_map_assoc(array_keys($display));
  }

  /**
   * Destructs a DisplayArray object.
   */
  public function __destruct() {
    foreach ($this->displayHandlers as $display_id => $display) {
      $display->destroy();
      unset($this->displayHandlers[$display_id]);
    }
  }

  /**
   * Initializes a single display and stores the result in $this->displayHandlers.
   *
   * @param string $display_id
   *   The name of the display to initialize.
   */
  protected function initializeDisplay($display_id) {
    // If the display was initialized before, just return.
    if (isset($this->displayHandlers[$display_id])) {
      return;
    }

    // Retrieve and initialize the new display handler with data.
    $display = &$this->view->storage->getDisplay($display_id);
    $this->displayHandlers[$display_id] = drupal_container()->get("plugin.manager.views.display")->createInstance($display['display_plugin']);
    if (empty($this->displayHandlers[$display_id])) {
      // Provide a 'default' handler as an emergency. This won't work well but
      // it will keep things from crashing.
      $this->displayHandlers[$display_id] = drupal_container()->get("plugin.manager.views.display")->createInstance('default');
    }

    $this->displayHandlers[$display_id]->initDisplay($this->view, $display);
    // If this is not the default display handler, let it know which is since
    // it may well utilize some data from the default.
    if ($display_id != 'default') {
      $this->displayHandlers[$display_id]->default_display = $this->displayHandlers['default'];
    }
  }

  /**
   * Implements \ArrayAccess::offsetExists().
   */
  public function offsetExists($offset) {
    return isset($this->displayHandlers[$offset]) || isset($this->displayIDs[$offset]);
  }

  /**
   * Implements \ArrayAccess::offsetGet().
   */
  public function offsetGet($offset) {
    if (!isset($this->displayHandlers[$offset])) {
      $this->initializeDisplay($offset);
    }
    return $this->displayHandlers[$offset];
  }

  /**
   * Implements \ArrayAccess::offsetSet().
   */
  public function offsetSet($offset, $value) {
    $this->displayHandlers[$offset] = $value;
  }

  /**
   * Implements \ArrayAccess::offsetUnset().
   */
  public function offsetUnset($offset) {
    unset($this->displayHandlers[$offset]);
  }

  /**
   * Implements \Iterator::current().
   */
  public function current() {
    return $this->offsetGet($this->key());
  }

  /**
   * Implements \Iterator::next().
   */
  public function next() {
    next($this->displayIDs);
  }

  /**
   * Implements \Iterator::key().
   */
  public function key() {
    return key($this->displayIDs);
  }

  /**
   * Implements \Iterator::valid().
   */
  public function valid() {
    $key = key($this->displayIDs);
    return $key !== NULL && $key !== FALSE;
  }

  /**
   * Implements \Iterator::rewind().
   */
  public function rewind() {
    reset($this->displayIDs);
  }

  /**
   * Implements \Countable::count().
   */
  public function count() {
    return count($this->displayIDs);
  }

}
