/**
 * @file
 * Handles AJAX fetching of views, including filter submission and response.
 */

(function ($, Drupal, drupalSettings) {
  /**
   * Attaches the AJAX behavior to exposed filters forms and key View links.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches ajaxView functionality to relevant elements.
   */
  Drupal.behaviors.ViewsAjaxView = {};
  Drupal.behaviors.ViewsAjaxView.attach = function (context, settings) {
    if (settings?.views?.ajaxViews) {
      const {
        views: { ajaxViews },
      } = settings;
      Object.keys(ajaxViews || {}).forEach((i) => {
        Drupal.views.instances[i] = new Drupal.views.ajaxView(ajaxViews[i]);
      });
    }
  };
  Drupal.behaviors.ViewsAjaxView.detach = (context, settings, trigger) => {
    if (trigger === 'unload') {
      if (settings?.views?.ajaxViews) {
        const {
          views: { ajaxViews },
        } = settings;
        Object.keys(ajaxViews || {}).forEach((i) => {
          const selector = `.js-view-dom-id-${ajaxViews[i].view_dom_id}`;
          if ($(selector, context).length) {
            delete Drupal.views.instances[i];
            delete settings.views.ajaxViews[i];
          }
        });
      }
    }
  };

  /**
   * @namespace
   */
  Drupal.views = {};

  /**
   * @type {object.<string, Drupal.views.ajaxView>}
   */
  Drupal.views.instances = {};

  /**
   * JavaScript object for a certain view.
   *
   * @constructor
   *
   * @param {object} settings
   *   Settings object for the ajax view.
   * @param {string} settings.view_dom_id
   *   The DOM id of the view.
   */
  Drupal.views.ajaxView = function (settings) {
    const selector = `.js-view-dom-id-${settings.view_dom_id}`;
    this.$view = $(selector);

    // Retrieve the path to use for views' ajax.
    let ajaxPath = drupalSettings.views.ajax_path;

    // If there are multiple views this might've ended up showing up multiple
    // times.
    if (ajaxPath.constructor.toString().includes('Array')) {
      ajaxPath = ajaxPath[0];
    }

    // Check if there are any GET parameters to send to views.
    let queryString = window.location.search || '';
    if (queryString !== '') {
      // Remove the question mark and Drupal path component if any.
      queryString = queryString
        .slice(1)
        .replace(/q=[^&]+&?|page=[^&]+&?|&?render=[^&]+/, '');
      if (queryString !== '') {
        // If there is a '?' in ajaxPath, clean URL are on and & should be
        // used to add parameters.
        queryString = (/\?/.test(ajaxPath) ? '&' : '?') + queryString;
      }
    }

    this.element_settings = {
      url: ajaxPath + queryString,
      submit: settings,
      httpMethod: 'GET',
      setClick: true,
      event: 'click',
      selector,
      progress: { type: 'fullscreen' },
    };

    this.settings = settings;

    // Add the ajax to exposed forms.
    this.$exposed_form = $(
      `form#views-exposed-form-${settings.view_name.replace(
        /_/g,
        '-',
      )}-${settings.view_display_id.replace(/_/g, '-')}`,
    );
    once('exposed-form', this.$exposed_form).forEach(
      this.attachExposedFormAjax.bind(this),
    );

    // Add the ajax to pagers.
    once(
      'ajax-pager',
      this.$view
        // Don't attach to nested views. Doing so would attach multiple behaviors
        // to a given element.
        .filter(this.filterNestedViews.bind(this)),
    ).forEach(this.attachPagerAjax.bind(this));

    // Add a trigger to update this view specifically. In order to trigger a
    // refresh use the following code.
    //
    // @code
    // $('.view-name').trigger('RefreshView');
    // @endcode
    const selfSettings = $.extend({}, this.element_settings, {
      event: 'RefreshView',
      base: this.selector,
      httpMethod: 'GET',
      element: this.$view.get(0),
    });
    this.refreshViewAjax = Drupal.ajax(selfSettings);
  };

  /**
   * @method
   */
  Drupal.views.ajaxView.prototype.attachExposedFormAjax = function () {
    const that = this;
    this.exposedFormAjax = [];
    // Exclude the reset buttons so no AJAX behaviors are bound. Many things
    // break during the form reset phase if using AJAX.
    $(
      'input[type=submit], button[type=submit], input[type=image]',
      this.$exposed_form,
    )
      .not('[data-drupal-selector=edit-reset]')
      .each(function (index) {
        const selfSettings = $.extend({}, that.element_settings, {
          base: $(this).attr('id'),
          element: this,
        });
        that.exposedFormAjax[index] = Drupal.ajax(selfSettings);
      });
  };

  /**
   * @return {boolean}
   *   If there is at least one parent with a view class return false.
   */
  Drupal.views.ajaxView.prototype.filterNestedViews = function () {
    // If there is at least one parent with a view class, this view
    // is nested (e.g., an attachment). Bail.
    return !this.$view.parents('.view').length;
  };

  /**
   * Attach the ajax behavior to each link.
   */
  Drupal.views.ajaxView.prototype.attachPagerAjax = function () {
    this.$view
      .find(
        '.js-pager__items a, th.views-field a, .attachment .views-summary a',
      )
      .each(this.attachPagerLinkAjax.bind(this));
  };

  /**
   * Attach the ajax behavior to a singe link.
   *
   * @param {string} [id]
   *   The ID of the link.
   * @param {HTMLElement} link
   *   The link element.
   */
  Drupal.views.ajaxView.prototype.attachPagerLinkAjax = function (id, link) {
    const $link = $(link);
    const viewData = {};
    const href = $link.attr('href');
    // Construct an object using the settings defaults and then overriding
    // with data specific to the link.
    $.extend(
      viewData,
      this.settings,
      Drupal.Views.parseQueryString(href),
      // Extract argument data from the URL.
      Drupal.Views.parseViewArgs(href, this.settings.view_base_path),
    );

    const selfSettings = $.extend({}, this.element_settings, {
      submit: viewData,
      base: false,
      element: link,
      httpMethod: 'GET',
    });
    this.pagerAjax = Drupal.ajax(selfSettings);
  };
})(jQuery, Drupal, drupalSettings);
