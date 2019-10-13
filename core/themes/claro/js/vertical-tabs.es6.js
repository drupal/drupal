/**
 * @file
 * Defines vertical tabs functionality.
 *
 * This file replaces core/misc/vertical-tabs.js to fix some bugs in the
 * original implementation, as well as makes minor changes to enable Claro
 * designs:
 * 1. Replaces hard-coded markup and adds 'js-' prefixed CSS classes for the
 *    JavaScript functionality (https://www.drupal.org/node/3081489).
 *    - The original Drupal.behavior and Drupal.verticalTab object hard-code
 *      markup of the tab list and (the outermost) wrapper of the vertical tabs
 *      component.
 *    - The original Drupal.verticalTab object is built on the same (unprefixed)
 *      CSS classes that should be used only for theming the component:
 *      - .vertical-tabs__pane - replaced by .js-vertical-tabs-pane;
 *      - .vertical-tabs__menu-item - replaced by .js-vertical-tabs-menu-item;
 *      - .vertical-tab--hidden - replaced by .js-vertical-tab-hidden.
 * 2. Fixes accessibility bugs (https://www.drupal.org/node/3081500):
 *    - The original Drupal.verticalTab object doesn't take care of the right
 *      aria attributes. Every details summary element is described with
 *      aria-expanded="false" and aria-pressed="false".
 *    - The original Drupal.verticalTab object uses a non-unique CSS id
 *      '#active-vertical-tab' for the marker of the active menu tab. This leads
 *      to broken behavior on filter format and editor configuration form where
 *      multiple vertical tabs may appear
 *      (/admin/config/content/formats/manage/basic_html).
 *    - Auto-focus bug: if the vertical tab is activated by pressing enter on
 *      the vertical tab menu link, the original Drupal.verticalTab object tries
 *      to focus the first visible :input element in a vertical tab content. The
 *      implementation doesn't work in all scenarios. For example, on the
 *      'Filter format and editor' form
 *      (/admin/config/content/formats/manage/basic_html), if the user presses
 *      the enter key on the last vertical tabs element's menu link ('Filter
 *      settings'), the focused element will be the first vertical tabs
 *      ('CKEditor plugin settings') active input, and not the expected one.
 * 3. Consistency between browsers (https://www.drupal.org/node/3081508):
 *    We have to display the setting summary on the 'accordion look' as well.
 *    Using the original file, these are displayed only on browsers without
 *    HTML5 details support, where core's built-in core/misc/collapse.js HTML5
 *    details polyfill is in action.
 * 4. Help fulfill our custom needs (https://www.drupal.org/node/3081519):
 *    The original behavior applies its features only when the actual screen
 *    width is bigger than 640 pixels (or the value of the
 *    drupalSettings.widthBreakpoint). But we want to switch between the
 *    'accordion look' and 'tab look' dynamically, right after the browser
 *    viewport was resized, and not only on page load.
 *    This would be possible even by defining drupalSettings.widthBreakpoint
 *    with '0' value. But since the name of this configuration does not suggest
 *    that it is (and will be) used only by vertical tabs, it is much cleaner
 *    to remove the unneeded condition from the functionality.
 */

/**
 * Triggers when form values inside a vertical tab changes.
 *
 * This is used to update the summary in vertical tabs in order to know what
 * are the important fields' values.
 *
 * @event summaryUpdated
 */

(($, Drupal) => {
  /**
   * Show the parent vertical tab pane of a targeted page fragment.
   *
   * In order to make sure a targeted element inside a vertical tab pane is
   * visible on a hash change or fragment link click, show all parent panes.
   *
   * @param {jQuery.Event} e
   *   The event triggered.
   * @param {jQuery} $target
   *   The targeted node as a jQuery object.
   */
  const handleFragmentLinkClickOrHashChange = (e, $target) => {
    $target.parents('.js-vertical-tabs-pane').each((index, pane) => {
      $(pane)
        .data('verticalTab')
        .focus();
    });
  };

  /**
   * This script transforms a set of details into a stack of vertical tabs.
   *
   * Each tab may have a summary which can be updated by another
   * script. For that to work, each details element has an associated
   * 'verticalTabCallback' (with jQuery.data() attached to the details),
   * which is called every time the user performs an update to a form
   * element inside the tab pane.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behaviors for vertical tabs.
   */
  Drupal.behaviors.claroVerticalTabs = {
    attach(context) {
      /**
       * Binds a listener to handle fragment link clicks and URL hash changes.
       */
      $('body')
        .once('vertical-tabs-fragments')
        .on(
          'formFragmentLinkClickOrHashChange.verticalTabs',
          handleFragmentLinkClickOrHashChange,
        );

      $(context)
        .find('[data-vertical-tabs-panes]')
        .once('vertical-tabs')
        .each(function initializeVerticalTabs() {
          const $this = $(this).addClass('vertical-tabs__items--processed');
          const focusID = $this.find(':hidden.vertical-tabs__active-tab').val();
          let tabFocus;

          // Check if there are some details that can be converted to
          // vertical-tabs.
          const $details = $this.find('> details');
          if ($details.length === 0) {
            return;
          }

          // Create the tab column.
          const tabList = $(Drupal.theme.verticalTabListWrapper());
          $this
            .wrap(
              $(Drupal.theme.verticalTabsWrapper()).addClass(
                'js-vertical-tabs',
              ),
            )
            .before(tabList);

          // Transform each details into a tab.
          $details.each(function initializeVerticalTabItems() {
            const $that = $(this);
            /* eslint-disable new-cap */
            const verticalTab = new Drupal.verticalTab({
              title: $that.find('> summary').text(),
              details: $that,
            });
            /* eslint-enable new-cap */
            tabList.append(verticalTab.item);
            $that
              // prop() can't be used on browsers not supporting details
              // element, the style won't apply to them if prop() is used.
              .removeAttr('open')
              .addClass('js-vertical-tabs-pane')
              .data('verticalTab', verticalTab);
            if (this.id === focusID) {
              tabFocus = $that;
            }
          });

          if (!tabFocus) {
            // If the current URL has a fragment and one of the tabs contains an
            // element that matches the URL fragment, activate that tab.
            const $locationHash = $this.find(window.location.hash);
            if (window.location.hash && $locationHash.length) {
              tabFocus = $locationHash.is('.js-vertical-tabs-pane')
                ? $locationHash
                : $locationHash.closest('.js-vertical-tabs-pane');
            } else {
              tabFocus = $this.find('> .js-vertical-tabs-pane').eq(0);
            }
          }
          if (tabFocus.length) {
            tabFocus.data('verticalTab').focus(false);
          }
        });
    },
  };

  /**
   * The vertical tab object represents a single tab within a tab group.
   *
   * @constructor
   *
   * @param {object} settings
   *   Settings object.
   * @param {string} settings.title
   *   The name of the tab.
   * @param {jQuery} settings.details
   *   The jQuery object of the details element that is the tab pane.
   *
   * @fires event:summaryUpdated
   *
   * @listens event:summaryUpdated
   */
  Drupal.verticalTab = function verticalTab(settings) {
    const self = this;
    $.extend(this, settings, Drupal.theme('verticalTab', settings));

    this.item.addClass('js-vertical-tabs-menu-item');

    this.link.attr('href', `#${settings.details.attr('id')}`);

    this.detailsSummaryDescription = $(
      Drupal.theme.verticalTabDetailsDescription(),
    ).appendTo(this.details.find('> summary'));

    this.link.on('click', event => {
      event.preventDefault();
      self.focus();
    });

    this.details.on('toggle', event => {
      // We will control this by summary clicks.
      event.preventDefault();
    });

    // Open the tab for every browser, with or without details support.
    this.details
      .find('> summary')
      .on('click', event => {
        event.preventDefault();
        self.details.attr('open', true);
        if (self.details.hasClass('collapse-processed')) {
          setTimeout(() => {
            self.focus();
          }, 10);
        } else {
          self.focus();
        }
      })
      .on('keydown', event => {
        if (event.keyCode === 13) {
          // Set focus on the first input field of the current visible details/tab
          // pane.
          setTimeout(() => {
            self.details
              .find(':input:visible:enabled')
              .eq(0)
              .trigger('focus');
          }, 10);
        }
      });

    // Keyboard events added:
    // Pressing the Enter key will open the tab pane.
    this.link.on('keydown', event => {
      if (event.keyCode === 13) {
        event.preventDefault();
        self.focus();
        // Set focus on the first input field of the current visible details/tab
        // pane.
        self.details
          .find(':input:visible:enabled')
          .eq(0)
          .trigger('focus');
      }
    });

    this.details
      .on('summaryUpdated', () => {
        self.updateSummary();
      })
      .trigger('summaryUpdated');
  };

  Drupal.verticalTab.prototype = {
    /**
     * Displays the tab's content pane.
     *
     * @param {bool} triggerFocus
     *   Whether focus should be triggered for the summary element.
     */
    focus(triggerFocus = true) {
      this.details
        .siblings('.js-vertical-tabs-pane')
        .each(function closeOtherTabs() {
          const tab = $(this).data('verticalTab');
          if (tab.details.attr('open')) {
            tab.details
              .removeAttr('open')
              .find('> summary')
              .attr({
                'aria-expanded': 'false',
                'aria-pressed': 'false',
              });
            tab.item.removeClass('is-selected');
          }
        })
        .end()
        .siblings(':hidden.vertical-tabs__active-tab')
        .val(this.details.attr('id'));

      this.details
        .attr('open', true)
        .find('> summary')
        .attr({
          'aria-expanded': 'true',
          'aria-pressed': 'true',
        })
        .closest('.js-vertical-tabs')
        .find('.js-vertical-tab-active')
        .remove();

      if (triggerFocus) {
        const $summary = this.details.find('> summary');
        if ($summary.is(':visible')) {
          $summary.trigger('focus');
        }
      }
      this.item.addClass('is-selected');
      // Mark the active tab for screen readers.
      this.title.after(
        $(Drupal.theme.verticalTabActiveTabIndicator()).addClass(
          'js-vertical-tab-active',
        ),
      );
    },

    /**
     * Updates the tab's summary.
     */
    updateSummary() {
      const summary = this.details.drupalGetSummary();
      this.detailsSummaryDescription.html(summary);
      this.summary.html(summary);
    },

    /**
     * Shows a vertical tab pane.
     *
     * @return {Drupal.verticalTab}
     *   The verticalTab instance.
     */
    tabShow() {
      // Display the tab.
      this.item.removeClass('vertical-tabs__menu-item--hidden').show();
      // Show the vertical tabs.
      this.item.closest('.js-form-type-vertical-tabs').show();
      // Display the details element.
      this.details
        .removeClass('vertical-tab--hidden js-vertical-tab-hidden')
        .show();
      // Update first and last CSS classes for details.
      this.details
        .parent()
        .children('.js-vertical-tabs-pane')
        .removeClass('vertical-tabs__item--first vertical-tabs__item--last')
        .filter(':visible')
        .eq(0)
        .addClass('vertical-tabs__item--first');
      this.details
        .parent()
        .children('.js-vertical-tabs-pane')
        .filter(':visible')
        .eq(-1)
        .addClass('vertical-tabs__item--last');
      // Make tab active, but without triggering focus.
      this.focus(false);
      return this;
    },

    /**
     * Hides a vertical tab pane.
     *
     * @return {Drupal.verticalTab}
     *   The verticalTab instance.
     */
    tabHide() {
      // Hide this tab.
      this.item.addClass('vertical-tabs__menu-item--hidden').hide();
      // Hide the details element.
      this.details
        .addClass('vertical-tab--hidden js-vertical-tab-hidden')
        .hide();
      // Update first and last CSS classes for details.
      this.details
        .parent()
        .children('.js-vertical-tabs-pane')
        .removeClass('vertical-tabs__item--first vertical-tabs__item--last')
        .filter(':visible')
        .eq(0)
        .addClass('vertical-tabs__item--first');
      this.details
        .parent()
        .children('.js-vertical-tabs-pane')
        .filter(':visible')
        .eq(-1)
        .addClass('vertical-tabs__item--last');
      // Focus the first visible tab (if there is one).
      const $firstTab = this.details
        .siblings('.js-vertical-tabs-pane:not(.js-vertical-tab-hidden)')
        .eq(0);
      if ($firstTab.length) {
        $firstTab.data('verticalTab').focus(false);
      }
      // Hide the vertical tabs (if no tabs remain).
      else {
        this.item.closest('.js-form-type-vertical-tabs').hide();
      }
      return this;
    },
  };

  /**
   * Theme function for a vertical tab.
   *
   * @param {object} settings
   *   An object with the following keys:
   * @param {string} settings.title
   *   The name of the tab.
   *
   * @return {object}
   *   This function has to return an object with at least these keys:
   *   - item: The root tab jQuery element
   *   - link: The anchor tag that acts as the clickable area of the tab
   *       (jQuery version)
   *   - summary: The jQuery element that contains the tab summary
   */
  Drupal.theme.verticalTab = settings => {
    const tab = {};
    tab.item = $(
      '<li class="vertical-tabs__menu-item" tabindex="-1"></li>',
    ).append(
      (tab.link = $('<a href="#" class="vertical-tabs__menu-link"></a>').append(
        $('<span class="vertical-tabs__menu-link-content"></span>')
          .append(
            (tab.title = $(
              '<strong class="vertical-tabs__menu-link-title"></strong>',
            ).text(settings.title)),
          )
          .append(
            (tab.summary = $(
              '<span class="vertical-tabs__menu-link-summary"></span>',
            )),
          ),
      )),
    );
    return tab;
  };

  /**
   * Wrapper of the menu and the panes.
   *
   * @return {string}
   *   A string representing the DOM fragment.
   */
  Drupal.theme.verticalTabsWrapper = () =>
    '<div class="vertical-tabs clearfix"></div>';

  /**
   * The wrapper of the vertical tab menu items.
   *
   * @return {string}
   *   A string representing the DOM fragment.
   */
  Drupal.theme.verticalTabListWrapper = () =>
    '<ul class="vertical-tabs__menu"></ul>';

  /**
   * The wrapper of the details summary message added to the summary element.
   *
   * @return {string}
   *   A string representing the DOM fragment.
   */
  Drupal.theme.verticalTabDetailsDescription = () =>
    '<span class="vertical-tabs__details-summary-summary"></span>';

  /**
   * Themes the active vertical tab menu item message.
   *
   * @return {string}
   *   A string representing the DOM fragment.
   */
  Drupal.theme.verticalTabActiveTabIndicator = () =>
    `<span class="visually-hidden">${Drupal.t('(active tab)')}</span>`;
})(jQuery, Drupal);
