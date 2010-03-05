// $Id: vertical-tabs.js,v 1.8 2010/03/05 13:32:09 dries Exp $

(function ($) {

/**
 * This script transforms a set of fieldsets into a stack of vertical
 * tabs. Another tab pane can be selected by clicking on the respective
 * tab.
 *
 * Each tab may have a summary which can be updated by another
 * script. For that to work, each fieldset has an associated
 * 'verticalTabCallback' (with jQuery.data() attached to the fieldset),
 * which is called every time the user performs an update to a form
 * element inside the tab pane.
 */
Drupal.behaviors.verticalTabs = {
  attach: function (context) {
    $('.vertical-tabs-panes', context).once('vertical-tabs', function () {
      var focusID = $(':hidden.vertical-tabs-active-tab', this).val();
      var focus;
      // Create the tab column.
      var list = $('<ul class="vertical-tabs-list"></ul>');
      $(this).wrap('<div class="vertical-tabs clearfix"></div>').before(list);

      // Transform each fieldset into a tab.
      $('> fieldset', this).each(function () {
        var tab = new Drupal.verticalTab({ title: $('> legend', this).text(), fieldset: $(this) });
        list.append(tab.item);
        $(this)
          .removeClass('collapsible collapsed')
          .addClass('vertical-tabs-pane')
          .data('verticalTab', tab);
        if (this.id == focusID) {
          focus = $(this);
        }
      });

      $('> li:first', list).addClass('first');
      $('> li:last', list).addClass('last');

      if (!focus) {
        focus = $('> .vertical-tabs-pane:first', this);
      }
      if (focus.length) {
        focus.data('verticalTab').focus();
      }
    });
  }
};

/**
 * The vertical tab object represents a single tab within a tab group.
 *
 * @param settings
 *   An object with the following keys:
 *   - title: The name of the tab.
 *   - fieldset: The jQuery object of the fieldset that is the tab pane.
 */
Drupal.verticalTab = function (settings) {
  var self = this;
  $.extend(this, settings, Drupal.theme('verticalTab', settings));

  this.link.click(function () {
    self.focus();
    return false;
  });

  this.fieldset
    .bind('summaryUpdated', function () {
      self.updateSummary();
    })
    .trigger('summaryUpdated');
};

Drupal.verticalTab.prototype = {
  // Displays the tab's content pane.
  focus: function () {
    this.fieldset
      .siblings('fieldset.vertical-tabs-pane')
        .each(function () {
          var tab = $(this).data('verticalTab');
          tab.fieldset.hide();
          tab.item.removeClass('selected');
        })
        .end()
      .show()
      .siblings(':hidden.vertical-tabs-active-tab')
        .val(this.fieldset.attr('id'));
    this.item.addClass('selected');
  },

  // Updates the tab's summary.
  updateSummary: function () {
    this.summary.html(this.fieldset.getSummary());
  }
};

/**
 * Theme function for a vertical tab.
 *
 * @param settings
 *   An object with the following keys:
 *   - title: The name of the tab.
 * @return
 *   This function has to return an object with at least these keys:
 *   - item: The root tab jQuery element
 *   - link: The anchor tag that acts as the clickable area of the tab
 *       (jQuery version)
 *   - summary: The jQuery element that contains the tab summary
 */
Drupal.theme.prototype.verticalTab = function (settings) {
  var tab = {};
  tab.item = $('<li class="vertical-tab-button"></li>')
    .append(tab.link = $('<a href="#"></a>')
      .append(tab.title = $('<strong></strong>').text(settings.title))
      .append(tab.summary = $('<small class="summary"></small>')
    )
  );
  return tab;
};

})(jQuery);
