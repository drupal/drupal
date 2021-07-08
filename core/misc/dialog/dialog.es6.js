/**
 * @file
 * Dialog API inspired by HTML5 dialog element.
 *
 * @see http://www.whatwg.org/specs/web-apps/current-work/multipage/commands.html#the-dialog-element
 */

(function ($, Drupal, drupalSettings, Popper, displace, dialogPolyfill) {
  Element.prototype.dialogObject = {};

  Element.prototype.dialog = function (...args) {
    // eslint-disable-next-line prefer-rest-params
    if (typeof args[0] === 'object') {
      this.dialogObject = new Drupal.Dialog(this, args[0]);
    }
    if (typeof args[0] === 'string') {
      if (typeof args[1] !== 'undefined') {
        if (args[0] === 'option' && typeof args[2] !== 'undefined') {
          const option = {};
          option[args[1]] = args[2];
          return this.dialogObject[args[0]](option);
        }
        return this.dialogObject[args[0]](args[1]);
      }

      return this.dialogObject[args[0]]();
    }
  };

  $.fn.extend({
    dialog(...args) {
      const itReturned = this[0].dialog(...args);
      return itReturned || this;
    },
  });

  Drupal.Dialog = class {
    constructor(element, settings) {
      this.keyCodes = {
        ESCAPE: 27,
        TAB: 9,
      };

      this.sizeRelatedOptions = {
        buttons: true,
        height: true,
        maxHeight: true,
        maxWidth: true,
        minHeight: true,
        minWidth: true,
        width: true,
      };

      this.resizableRelatedOptions = {
        maxHeight: true,
        maxWidth: true,
        minHeight: true,
        minWidth: true,
      };

      const defaultSettings = this.constructor.defaultSettings();
      // @todo this is probably needed for other internal properties.
      defaultSettings.classes = {
        ...defaultSettings.classes,
        ...settings.classes,
      };
      delete settings.classes;
      this.dialogOptions = { ...defaultSettings, ...settings };
      this.$element = $(element);
      this.element = element;
      this.isOpen = null;
      this.uiDialog = {};
      this.widget = () => this.uiDialog;
      this.uiDialogTitle = {};
      this.uiDialogTitlebar = {};
      this.uiDialogButtonPane = {};
      this.uiButtonSet = {};
      this.option = (args) => this.options(args);

      this.originalCss = {
        display: this.$element[0].style.display,
        width: this.$element[0].style.width,
        minHeight: this.$element[0].style.minHeight,
        maxHeight: this.$element[0].style.maxHeight,
        height: this.$element[0].style.height,
      };

      this.originalPosition = {
        parent: this.$element.parent(),
        index: this.$element.parent().children().index(this.element),
      };
      this.originalTitle = this.$element.attr('title');
      if (this.dialogOptions.title == null && this.originalTitle != null) {
        this.dialogOptions.title = this.originalTitle;
      }

      // Dialogs can't be disabled
      if (this.dialogOptions.disabled) {
        this.dialogOptions.disabled = false;
      }

      this.popper = null;
      this.create();
      this.init();
    }

    static defaultSettings() {
      return {
        appendTo: 'body',
        autoOpen: true,
        buttonClass: 'button',
        buttonPrimaryClass: 'button--primary',
        buttons: [],
        classes: {
          'ui-dialog': 'ui-corner-all',
          'ui-dialog-titlebar': 'ui-corner-all',
        },
        closeOnEscape: true,
        closeText: 'Close',
        dialogClass: '',
        draggable: true,
        hide: null,
        height: 'auto',
        maxHeight: null,
        maxWidth: null,
        minHeight: 150,
        minWidth: 150,
        modal: false,
        position: {
          my: 'center',
          at: 'center',
          of: document.querySelector('body'),
        },
        resizable: true,
        show: null,
        title: null,
        width: 300,
        // Drupal-specific extensions: see dialog.jquery-ui.js.

        // When using this API directly (when generating dialogs on the client
        // side), you may want to override this method and do
        // `jQuery(event.target).remove()` as well, to remove the dialog on
        // closing.
        close(event) {
          Drupal.dialog(event.target).close();
          Drupal.detachBehaviors(event.target, null, 'unload');
        },
      };
    }

    processPosition() {
      const placement = 'top';
      const centered = this.dialogOptions.position.at === 'center';

      // @todo this is looking for a drupal property, which is counter to
      // making this a general-use library.
      const isTop =
        this.dialogOptions.hasOwnProperty('drupalOffCanvasPosition') &&
        this.dialogOptions.drupalOffCanvasPosition === 'top';

      if (centered) {
        const centerDialog = () => {
          const top =
            (window.innerHeight - displace.offsets.top) / 2 -
            this.uiDialog.height() / 2;

          this.uiDialog.css({
            position: 'fixed',
            top: `${top}px`,
            margin: '0 auto',
            overflow: 'hidden',
          });
        };
        centerDialog();

        // IE11 does not support resizeObserver
        if (typeof ResizeObserver !== 'undefined') {
          const ro = new ResizeObserver(() => {
            centerDialog();
          });
          ro.observe(this.uiDialog[0]);
        } else {
          // @todo implement IE11 alternative if this somehow lands before IE11
          //  support ends.
        }
      } else if (!isTop) {
        let additionalTopOffset = 0;
        if (this.dialogOptions.position.at.includes('top+')) {
          const numIndex = this.dialogOptions.position.at.indexOf('top+') + 4;
          additionalTopOffset = parseInt(
            this.dialogOptions.position.at.substr(numIndex),
            10,
          );
        } else if (this.dialogOptions.position.at.includes('top-') > 0) {
          const numIndex = this.dialogOptions.position.at.indexOf('top-') + 4;
          additionalTopOffset =
            parseInt(this.dialogOptions.position.at.substr(numIndex), 10) * -1;
        }

        const { maxHeight } = this.dialogOptions;

        const yCenterModifier = {
          name: 'yCenterModifier',
          enabled: true,
          phase: 'main',
          fn({ state }) {
            const maxHeightPx =
              typeof maxHeight === 'string'
                ? (window.innerHeight *
                    parseInt(maxHeight.replace('%', ''), 10)) /
                  100
                : maxHeight;
            const popperHeight = Math.min(
              state.elements.popper.offsetHeight,
              maxHeightPx,
            );
            const centerOffset = centered
              ? (window.innerHeight - displace.offsets.top) / 2 -
                popperHeight / 2
              : 0;
            state.modifiersData.popperOffsets.y =
              centerOffset + additionalTopOffset;
          },
        };

        const modifiers = [yCenterModifier];

        const rightModifier = {
          name: 'rightModifier',
          enabled: true,
          phase: 'main',
          fn({ state }) {
            if (state.placement === 'top') {
              state.modifiersData.popperOffsets.x =
                window.innerWidth - state.elements.popper.offsetWidth;
            }
          },
        };

        if (this.dialogOptions.position.at.includes('right')) {
          modifiers.push(rightModifier);
        }

        const positionAround =
          this.dialogOptions.position.of === window
            ? document.querySelector('body')
            : this.dialogOptions.position.of;

        this.popper = Popper.createPopper(
          positionAround,
          this.widget().get(0),
          {
            placement,
            modifiers,
            strategy: 'fixed',
          },
        );
      }
    }

    init() {
      if (this.dialogOptions.autoOpen) {
        this.open();
      }
    }

    options(...args) {
      if (typeof args[0] === 'undefined') {
        return this.getOptions();
      }
      if (typeof args[0] === 'object') {
        Object.keys(args[0]).forEach((option) => {
          this[option] = args[0][option];
          this.setOption(option, args[0][option]);
        });
        return this.$element;
      }
      if (typeof args[0] === 'string') {
        if (typeof args[1] === 'string') {
          this.setOption(args[0], args[1]);
          return this;
        }
        return this[args[0]];
      }
    }

    setOption(key, value) {
      if (typeof value === 'object') {
        this.dialogOptions[key] = { ...this.dialogOptions[key], ...value };
      } else {
        this.dialogOptions[key] = value;
      }

      if (key.includes('Height') || key.includes('Width')) {
        this.size();
        this.processPosition();
      }

      if (key.includes('position')) {
        this.processPosition();
      }
      if (key === 'buttons') {
        this.dialogOptions.buttons = value;
        this.createButtonPane();
      }
    }

    getOptions(key) {
      if (key) {
        return this.dialogOptions[key];
      }
      return this.dialogOptions;
    }

    open() {
      if (this.isOpen) {
        if (this.moveToTop()) {
          console.log(
            '@todo: constrain focus to this open dialog that previously was not focusable because another dialog was prioritized.',
          );
        }
        return;
      }

      this.isOpen = true;
      this.size();
      this.setPosition();
      this.moveToTop(null, true);

      if (this.dialogOptions.modal && !this.uiDialog[0].hasAttribute('open')) {
        this.uiDialog[0].showModal();
      } else {
        this.uiDialog[0].show();
      }

      // @todo the dialog is open, so now focus needs to be constrained.
      this.dialogTrigger('open');
    }

    setPosition() {
      // Need to show the dialog to get offsets.
      // @todo not sure why this is needed. This is commented out because at the
      //  time dialog here is made visible, it is positioned as absolute which
      //  introduces some scrolling on the page at least with native dialogs.
      // const isVisible = this.uiDialog.is(':visible');

      /* if (!isVisible) {
        this.uiDialog[0].show();
      } */
      this.processPosition();

      /* if (!isVisible) {
        this.uiDialog[0].close();
      } */
    }

    size() {
      // If the user has resized the dialog, the .ui-dialog and .ui-dialog-content
      // divs will both have width and height set, so we need to reset them
      const options = this.dialogOptions;

      // Reset content sizing
      this.$element.show().css({
        width: 'auto',
        minHeight: 0,
        maxHeight: 'none',
        marginTop: '0',
        height: 0,
      });

      if (options.minWidth > options.width) {
        options.width = options.minWidth;
      }

      // Reset wrapper sizing
      // determine the height of all the non-content elements
      const nonContentHeight = this.uiDialog
        .css({
          width: options.width,
        })
        .outerHeight();
      const minContentHeight = Math.max(
        0,
        options.minHeight - nonContentHeight,
      );
      const maxContentHeight =
        typeof options.maxHeight === 'number'
          ? Math.max(0, options.maxHeight - nonContentHeight)
          : 'none';

      if (options.height === 'auto') {
        this.$element.css({
          minHeight: minContentHeight,
          maxHeight: maxContentHeight,
          height: 'auto',
        });
      } else {
        this.$element.height(Math.max(0, options.height - nonContentHeight));
      }
      if (this.uiDialog.is(':data(ui-resizable)')) {
        const minHeight = this.minHeight();
        this.uiDialog.resizable('option', 'minHeight', minHeight);
      }
    }

    minHeight() {
      const options = this.dialogOptions;

      return options.height === 'auto'
        ? options.minHeight
        : Math.min(options.minHeight, options.height);
    }

    create() {
      this.createWrapper();

      this.$element.show().removeAttr('title').appendTo(this.uiDialog);
      this.$element.addClass('ui-dialog-content ui-widget-content');

      this.createTitlebar();
      this.createButtonPane();

      if (this.dialogOptions.draggable && $.fn.draggable) {
        console.log(
          '@todo make draggable or decide that draggable is not needed',
        );
      }
      if (this.dialogOptions.resizable && $.fn.resizable) {
        this.makeResizable();
      }

      this.isOpen = false;
    }

    createButtonPane() {
      if (this.uiDialogButtonPane.length > 0) {
        this.uiDialogButtonPane.find('.ui-dialog-buttonset').empty();
      } else {
        this.uiDialogButtonPane = $('<div>');
        this.uiDialogButtonPane.addClass(
          'ui-dialog-buttonpane ui-widget-content ui-helper-clearfix',
        );
      }

      this.uiButtonSet = $('<div>').prependTo(this.uiDialogButtonPane);
      this.uiButtonSet.addClass('ui-dialog-buttonset');

      this.createButtons();
    }

    createButtons() {
      const that = this;
      const { buttons } = this.dialogOptions;
      const opts = this.dialogOptions;
      let primaryIndex;
      let index;
      const il = opts.buttons.length;
      for (index = 0; index < il; index++) {
        if (
          opts.buttons[index].primary &&
          opts.buttons[index].primary === true
        ) {
          primaryIndex = index;
          delete opts.buttons[index].primary;
          break;
        }
      }

      // If we already have a button pane, remove it
      this.uiDialogButtonPane.remove();
      this.uiButtonSet.empty();

      if ($.isEmptyObject(buttons) || ($.isArray(buttons) && !buttons.length)) {
        this.uiDialog.removeClass('ui-dialog-buttons');
        return;
      }

      $.each(buttons, function (name, props) {
        props =
          typeof props === 'function' ? { click: props, text: name } : props;

        // Default to a non-submitting button
        props = $.extend({ type: 'button' }, props);

        // Change the context for the click callback to be the main element
        const { click } = props;
        const buttonOptions = {
          icon: props.icon,
          iconPosition: props.iconPosition,
          showLabel: props.showLabel,

          // Deprecated options
          icons: props.icons,
          text: props.text,
        };

        delete props.click;
        delete props.icon;
        delete props.iconPosition;
        delete props.showLabel;

        // Deprecated options
        delete props.icons;
        if (typeof props.text === 'boolean') {
          delete props.text;
        }

        const buttonText = buttonOptions.text || '';
        const $button = $(`<button type="button">${buttonText}</button>`);
        if (props.hasOwnProperty('class')) {
          $button.attr('class', props.class);
        }
        $button.appendTo(that.uiButtonSet).on('click', function () {
          // eslint-disable-next-line prefer-rest-params
          click.apply(that.element[0], arguments);
        });
      });
      this.uiDialog.addClass('ui-dialog-buttons');
      this.uiDialogButtonPane.appendTo(this.uiDialog);
      const $buttons = this.uiButtonSet.children().addClass(opts.buttonClass);
      if (typeof primaryIndex !== 'undefined') {
        $buttons.eq(index).addClass(opts.buttonPrimaryClass);
      }
    }

    createTitlebar() {
      this.uiDialogTitlebar = $('<div>');
      this.uiDialogTitlebar.addClass(
        'ui-dialog-titlebar ui-widget-header ui-helper-clearfix',
      );
      this.uiDialogTitlebar.addClass(
        this.dialogOptions.classes['ui-dialog-titlebar'],
      );
      this.uiDialogTitlebar.on('mousedown', (event) => {
        // Don't prevent click on close button (#8838)
        // Focusing a dialog that is partially scrolled out of view
        // causes the browser to scroll it into view, preventing the click event
        if (!$(event.target).closest('.ui-dialog-titlebar-close')) {
          // Dialog isn't getting focus when dragging (#8063)
          // this.uiDialog.trigger( "focus" );
          // @todo must retain focus during drag.
        }
      });

      this.uiDialogTitlebarClose = $(
        `<button title="${Drupal.t(
          'Close',
        )}"><span class="ui-button-icon ui-icon ui-icon-closethick">${
          this.dialogOptions.closeText
        }</span><span class="ui-button-icon-space"> </span></button>`,
      ).appendTo(this.uiDialogTitlebar);

      this.uiDialogTitlebarClose.addClass(
        'ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close',
      );

      this.uiDialogTitlebarClose.on('click', (event) => {
        event.preventDefault();
        this.close(event);
      });

      this.uiDialogTitle = $('<span id="">').prependTo(this.uiDialogTitlebar);
      this.uiDialogTitle.addClass('ui-dialog-title');
      this.uiDialogTitle.attr('id', this.uiDialog.attr('aria-labelledby'));
      this.title(this.uiDialogTitle);

      this.uiDialogTitlebar.prependTo(this.uiDialog);

      this.uiDialog.attr({
        'aria-labelledby': this.uiDialogTitle.attr('id'),
      });
    }

    title(title) {
      if (this.dialogOptions.title) {
        title.text(this.dialogOptions.title);
      } else {
        title.html('&#160;');
      }
    }

    close(event) {
      if (!this.isOpen || this.dialogTrigger('beforeClose', event) === false) {
        return;
      }

      this.isOpen = false;
      this.uiDialog.open = false;

      const { triggeringElement } = this.dialogOptions;
      if (triggeringElement) {
        triggeringElement.focus();
      } else {
        // If a dialog triggering element isn't defined, focus <body>.
        $(document.body).focus();
      }

      this.uiDialog[0].close();
      this.dialogTrigger('close', event);
    }

    dialogTrigger(type, event, data) {
      const callback = this.dialogOptions[type];

      data = data || {};
      event = $.Event(event);
      event.type = (
        type === this.uiDialogEventPrefix
          ? type
          : this.uiDialogEventPrefix + type
      ).toLowerCase();

      // The original event may come from any element
      // so we need to reset the target on the new event
      event.target = this.element[0];

      // Copy original event properties over to the new event
      const orig = event.originalEvent;
      if (orig) {
        Object.keys(orig || {}).forEach((prop) => {
          if (!(prop in event)) {
            event[prop] = orig[prop];
          }
        });
      }

      this.$element.trigger(event, data);
      return !(
        (typeof callback === 'function' &&
          callback.apply(this.element[0], [event].concat(data)) === false) ||
        event.isDefaultPrevented()
      );
    }

    createWrapper() {
      let id = this.$element.attr('id');
      let existingWrapper = {};
      if (id) {
        existingWrapper = $(`[data-drupal-dialog-contains="${id}"]`);
      } else {
        let idNum = 0;
        while ($(`#drupal-dialog-id-${idNum}`).length !== 0) {
          idNum++;
        }
        id = `drupal-dialog-id-${idNum}`;
      }

      if (existingWrapper.length > 0) {
        this.uiDialog = existingWrapper;
        this.uiDialog.find('.ui-dialog-titlebar').remove();
        this.uiDialog.find('.ui-dialog-buttonpane').remove();
        this.uiDialog.removeClass();
        this.uiDialog.attr('style', '');
      } else {
        this.uiDialog = $('<dialog/>')
          .attr({
            'data-drupal-dialog-wrapper': '',
            'data-drupal-dialog-contains': id,
            'aria-describedby': id,
            'aria-labelledby': `${id}-label`,
          })
          .appendTo(this.getAppendTo());
        if (!this.constructor.hasNativeDialog()) {
          dialogPolyfill.registerDialog(this.uiDialog[0]);
        }
      }

      this.uiDialog.addClass(this.dialogOptions.dialogClass);
      this.uiDialog.addClass(this.dialogOptions.classes['ui-dialog']);
      this.uiDialog.addClass('ui-dialog ui-widget ui-widget-content ui-front');
      this.uiDialog.on('keydown', (event) => {
        if (
          this.dialogOptions.closeOnEscape &&
          !event.isDefaultPrevented() &&
          event.keyCode &&
          event.keyCode === this.keyCodes.ESCAPE
        ) {
          event.preventDefault();
          this.close(event);
        }
      });
    }

    moveToTop(event, silent) {
      let moved = false;
      const zIndices = this.uiDialog
        .siblings('.ui-front:visible')
        .map(function () {
          return +$(this).css('z-index');
        })
        .get();
      const zIndexMax = Math.max.apply(null, zIndices);

      if (zIndexMax >= +this.uiDialog.css('z-index')) {
        this.uiDialog.css('z-index', zIndexMax + 1);
        moved = true;
      }

      if (moved && !silent) {
        this.trigger('focus', event);
      }
      return moved;
    }

    getAppendTo() {
      const element = this.dialogOptions.appendTo;
      if (element && (element.jquery || element.nodeType)) {
        return $(element);
      }
      return $(document)
        .find(element || 'body')
        .eq(0);
    }

    /**
     * At the moment this is flat-out still using jQueryUI resizable as a
     * suitable replacement hasn't been found and it's far too complex to
     * provide a quick custom solution.
     */
    makeResizable() {
      const that = this;
      const options = this.dialogOptions;
      const handles = options.resizable;

      // .ui-resizable has position: relative defined in the stylesheet
      // but dialogs have to use absolute or fixed positioning
      const position = this.uiDialog.css('position');
      const resizeHandles =
        typeof handles === 'string' ? handles : 'n,e,s,w,se,sw,ne,nw';

      function filteredUi(ui) {
        return {
          originalPosition: ui.originalPosition,
          originalSize: ui.originalSize,
          position: ui.position,
          size: ui.size,
        };
      }

      this.uiDialog
        .resizable({
          cancel: '.ui-dialog-content',
          containment: 'document',
          alsoResize: this.$element,
          maxWidth: options.maxWidth,
          maxHeight: options.maxHeight,
          minWidth: options.minWidth,
          minHeight: this.minHeight(),
          handles: resizeHandles,
          start(event, ui) {
            that.uiDialog.addClass('ui-dialog-resizing');
            that.dialogTrigger('resizeStart', event, filteredUi(ui));
          },
          resize(event, ui) {
            that.dialogTrigger('resize', event, filteredUi(ui));
          },
          stop(event, ui) {
            const offset = that.uiDialog.offset();
            const left = offset.left - $(document).scrollLeft();
            const top = offset.top - $(document).scrollTop();

            options.height = that.uiDialog.height();
            options.width = that.uiDialog.width();
            options.position = {
              my: 'left top',
              at: `left${left >= 0 ? '' : ''}${left} top${
                top >= 0 ? '' : ''
              }${top}`,
              of: that.window,
            };
            that.uiDialog.removeClass('ui-dialog-resizing');
            that.dialogTrigger('resizeStop', event, filteredUi(ui));
          },
        })
        .css('position', position);
    }

    static hasNativeDialog() {
      return typeof HTMLDialogElement === 'function';
    }
  };
  /**
   * Default dialog options.
   *
   * @type {object}
   *
   * @prop {bool} [autoOpen=true]
   * @prop {string} [dialogClass='']
   * @prop {string} [buttonClass='button']
   * @prop {string} [buttonPrimaryClass='button--primary']
   * @prop {function} close
   */
  drupalSettings.dialog = {
    autoOpen: true,
    dialogClass: '',
    // Drupal-specific extensions: see dialog.jquery-ui.js.
    buttonClass: 'button',
    buttonPrimaryClass: 'button--primary',
    // When using this API directly (when generating dialogs on the client
    // side), you may want to override this method and do
    // `jQuery(event.target).remove()` as well, to remove the dialog on
    // closing.
    close(event) {
      Drupal.dialog(event.target).close();
      Drupal.detachBehaviors(event.target, null, 'unload');
    },
  };

  /**
   * @typedef {object} Drupal.dialog~dialogDefinition
   *
   * @prop {boolean} open
   *   Is the dialog open or not.
   * @prop {*} returnValue
   *   Return value of the dialog.
   * @prop {function} show
   *   Method to display the dialog on the page.
   * @prop {function} showModal
   *   Method to display the dialog as a modal on the page.
   * @prop {function} close
   *   Method to hide the dialog from the page.
   */

  /**
   * Polyfill HTML5 dialog element with jQueryUI.
   *
   * @param {HTMLElement} element
   *   The element that holds the dialog.
   * @param {object} options
   *   jQuery UI options to be passed to the dialog.
   *
   * @return {Drupal.dialog~dialogDefinition}
   *   The dialog instance.
   */
  Drupal.dialog = function (element, options) {
    let undef;
    const $element = $(element);
    const dialog = {
      open: false,
      returnValue: undef,
    };

    function openDialog(settings) {
      settings = $.extend(
        Drupal.Dialog.defaultSettings(),
        drupalSettings.dialog,
        options,
        settings,
      );
      // Trigger a global event to allow scripts to bind events to the dialog.
      $(window).trigger('dialog:beforecreate', [dialog, $element, settings]);

      $element.dialog(settings);
      dialog.open = true;
      $(window).trigger('dialog:aftercreate', [dialog, $element, settings]);
    }

    function closeDialog(value) {
      $(window).trigger('dialog:beforeclose', [dialog, $element]);
      $element.dialog('close');
      dialog.returnValue = value;
      dialog.open = false;
      $(window).trigger('dialog:afterclose', [dialog, $element]);

      if ($element.closest('[data-drupal-dialog-wrapper]').length !== 0) {
        $element.closest('[data-drupal-dialog-wrapper]').remove();
      }
    }

    dialog.show = () => {
      openDialog({ modal: false });
    };
    dialog.showModal = () => {
      openDialog({
        modal: true,
      });
    };
    dialog.close = closeDialog;

    return dialog;
  };
})(jQuery, Drupal, drupalSettings, Popper, Drupal.displace, dialogPolyfill);
