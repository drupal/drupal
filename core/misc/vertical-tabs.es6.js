/**
 * @file
 * Define vertical tabs functionality.
 */

/**
 * Triggers when form values inside a vertical tab changes.
 *
 * This is used to update the summary in vertical tabs in order to know what
 * are the important fields' values.
 *
 * @event summaryUpdated
 */

(function ($, Drupal, drupalSettings) {
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
    $target.parents('.vertical-tabs__pane').each((index, pane) => {
      $(pane).data('verticalTab').focus();
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
  Drupal.behaviors.verticalTabs = {
    attach(context) {
      const width = drupalSettings.widthBreakpoint || 640;
      const mq = `(max-width: ${width}px)`;

      if (window.matchMedia(mq).matches) {
        return;
      }

      /**
       * Binds a listener to handle fragment link clicks and URL hash changes.
       */
      $('body').once('vertical-tabs-fragments').on('formFragmentLinkClickOrHashChange.verticalTabs', handleFragmentLinkClickOrHashChange);

      $(context).find('[data-vertical-tabs-panes]').once('vertical-tabs').each(function () {
        const $this = $(this).addClass('vertical-tabs__panes');
        const focusID = $this.find(':hidden.vertical-tabs__active-tab').val();
        let tab_focus;

        // Check if there are some details that can be converted to
        // vertical-tabs.
        const $details = $this.find('> details');
        if ($details.length === 0) {
          return;
        }

        // Create the tab column.
        const tab_list = $('<ul class="vertical-tabs__menu"></ul>');
        $this.wrap('<div class="vertical-tabs clearfix"></div>').before(tab_list);

        // Transform each details into a tab.
        $details.each(function () {
          const $that = $(this);
          const vertical_tab = new Drupal.verticalTab({
            title: $that.find('> summary').text(),
            details: $that,
          });
          tab_list.append(vertical_tab.item);
          $that
            .removeClass('collapsed')
            // prop() can't be used on browsers not supporting details element,
            // the style won't apply to them if prop() is used.
            .attr('open', true)
            .addClass('vertical-tabs__pane')
            .data('verticalTab', vertical_tab);
          if (this.id === focusID) {
            tab_focus = $that;
          }
        });

        $(tab_list).find('> li').eq(0).addClass('first');
        $(tab_list).find('> li').eq(-1).addClass('last');

        if (!tab_focus) {
          // If the current URL has a fragment and one of the tabs contains an
          // element that matches the URL fragment, activate that tab.
          const $locationHash = $this.find(window.location.hash);
          if (window.location.hash && $locationHash.length) {
            tab_focus = $locationHash.closest('.vertical-tabs__pane');
          }
          else {
            tab_focus = $this.find('> .vertical-tabs__pane').eq(0);
          }
        }
        if (tab_focus.length) {
          tab_focus.data('verticalTab').focus();
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
  Drupal.verticalTab = function (settings) {
    const self = this;
    $.extend(this, settings, Drupal.theme('verticalTab', settings));

    this.link.attr('href', `#${settings.details.attr('id')}`);

    this.link.on('click', (e) => {
      e.preventDefault();
      self.focus();
    });

    // Keyboard events added:
    // Pressing the Enter key will open the tab pane.
    this.link.on('keydown', (event) => {
      if (event.keyCode === 13) {
        event.preventDefault();
        self.focus();
        // Set focus on the first input field of the visible details/tab pane.
        $('.vertical-tabs__pane :input:visible:enabled').eq(0).trigger('focus');
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
     */
    focus() {
      this.details
        .siblings('.vertical-tabs__pane')
        .each(function () {
          const tab = $(this).data('verticalTab');
          tab.details.hide();
          tab.item.removeClass('is-selected');
        })
        .end()
        .show()
        .siblings(':hidden.vertical-tabs__active-tab')
        .val(this.details.attr('id'));
      this.item.addClass('is-selected');
      // Mark the active tab for screen readers.
      $('#active-vertical-tab').remove();
      this.link.append(`<span id="active-vertical-tab" class="visually-hidden">${Drupal.t('(active tab)')}</span>`);
    },

    /**
     * Updates the tab's summary.
     */
    updateSummary() {
      this.summary.html(this.details.drupalGetSummary());
    },

    /**
     * Shows a vertical tab pane.
     *
     * @return {Drupal.verticalTab}
     *   The verticalTab instance.
     */
    tabShow() {
      // Display the tab.
      this.item.show();
      // Show the vertical tabs.
      this.item.closest('.js-form-type-vertical-tabs').show();
      // Update .first marker for items. We need recurse from parent to retain
      // the actual DOM element order as jQuery implements sortOrder, but not
      // as public method.
      this.item.parent().children('.vertical-tabs__menu-item').removeClass('first')
        .filter(':visible').eq(0).addClass('first');
      // Display the details element.
      this.details.removeClass('vertical-tab--hidden').show();
      // Focus this tab.
      this.focus();
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
      this.item.hide();
      // Update .first marker for items. We need recurse from parent to retain
      // the actual DOM element order as jQuery implements sortOrder, but not
      // as public method.
      this.item.parent().children('.vertical-tabs__menu-item').removeClass('first')
        .filter(':visible').eq(0).addClass('first');
      // Hide the details element.
      this.details.addClass('vertical-tab--hidden').hide();
      // Focus the first visible tab (if there is one).
      const $firstTab = this.details.siblings('.vertical-tabs__pane:not(.vertical-tab--hidden)').eq(0);
      if ($firstTab.length) {
        $firstTab.data('verticalTab').focus();
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
  Drupal.theme.verticalTab = function (settings) {
    const tab = {};
    tab.item = $('<li class="vertical-tabs__menu-item" tabindex="-1"></li>')
      .append(tab.link = $('<a href="#"></a>')
        .append(tab.title = $('<strong class="vertical-tabs__menu-item-title"></strong>').text(settings.title))
        .append(tab.summary = $('<span class="vertical-tabs__menu-item-summary"></span>'),
        ),
      );
    return tab;
  };
}(jQuery, Drupal, drupalSettings));
