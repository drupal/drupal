/**
 * @file
 * Provides interactivity for showing and hiding the tabs at mobile widths.
 */

((Drupal, once) => {
  /**
   * Initialize the tabs.
   *
   * @param {HTMLElement} el
   *   The DOM element containing the tabs.
   */
  function init(el) {
    const tabs = el.querySelector('.tabs');
    const expandedClass = 'is-expanded';
    const activeTab = tabs.querySelector('.is-active');

    /**
     * Determines if tabs are expanded for mobile layouts.
     *
     * @return {boolean}
     *   Whether the tabs trigger element is expanded.
     */
    function isTabsMobileLayout() {
      return tabs.querySelector('.tabs__trigger').clientHeight > 0;
    }

    /**
     * Controls tab visibility on click events.
     *
     * @param {Event} e
     *   The event object.
     */
    function handleTriggerClick(e) {
      if (!tabs.classList.contains(expandedClass)) {
        e.currentTarget.setAttribute('aria-expanded', 'true');
        tabs.classList.add(expandedClass);
      } else {
        e.currentTarget.setAttribute('aria-expanded', 'false');
        tabs.classList.remove(expandedClass);
      }
    }

    if (isTabsMobileLayout() && !activeTab.matches('.tabs__tab:first-child')) {
      const newActiveTab = activeTab.cloneNode(true);
      const firstTab = tabs.querySelector('.tabs__tab:first-child');
      tabs.insertBefore(newActiveTab, firstTab);
      tabs.removeChild(activeTab);
    }

    tabs
      .querySelector('.tabs__trigger')
      .addEventListener('click', handleTriggerClick);
  }

  /**
   * Initialize the tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Display tabs according to the screen width.
   */
  Drupal.behaviors.tabs = {
    attach(context) {
      once('olivero-tabs', '[data-drupal-nav-tabs]', context).forEach(init);
    },
  };
})(Drupal, once);
