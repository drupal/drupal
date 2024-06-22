/**
 * @file
 * Adds default classes to buttons for styling purposes.
 */

(function ($, { tabbable, isTabbable }) {
  $.widget('ui.dialog', $.ui.dialog, {
    options: {
      buttonClass: 'button',
      buttonPrimaryClass: 'button--primary',
    },
    _createButtons() {
      const opts = this.options;
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
      this._super();
      const $buttons = this.uiButtonSet.children().addClass(opts.buttonClass);
      if (typeof primaryIndex !== 'undefined') {
        $buttons.eq(index).addClass(opts.buttonPrimaryClass);
      }
    },
    _createWrapper() {
      this.uiDialog = $('<div>')
        .hide()
        .attr({
          // Setting tabIndex makes the div focusable
          tabIndex: -1,
          role: 'dialog',
          'aria-modal': this.options.modal ? 'true' : null,
        })
        .appendTo(this._appendTo());

      this._addClass(
        this.uiDialog,
        'ui-dialog',
        'ui-widget ui-widget-content ui-front',
      );
      this._on(this.uiDialog, {
        keydown(event) {
          if (
            this.options.closeOnEscape &&
            !event.isDefaultPrevented() &&
            event.keyCode &&
            event.keyCode === $.ui.keyCode.ESCAPE
          ) {
            event.preventDefault();
            this.close(event);
            return;
          }

          // Prevent tabbing out of dialogs
          if (
            event.keyCode !== $.ui.keyCode.TAB ||
            event.isDefaultPrevented()
          ) {
            return;
          }

          const tabbableElements = tabbable(this.uiDialog[0]);
          if (tabbableElements.length) {
            const first = tabbableElements[0];
            const last = tabbableElements[tabbableElements.length - 1];

            if (
              (event.target === last || event.target === this.uiDialog[0]) &&
              !event.shiftKey
            ) {
              this._delay(function () {
                $(first).trigger('focus');
              });
              event.preventDefault();
            } else if (
              (event.target === first || event.target === this.uiDialog[0]) &&
              event.shiftKey
            ) {
              this._delay(function () {
                $(last).trigger('focus');
              });
              event.preventDefault();
            }
          }
        },
        mousedown(event) {
          if (this._moveToTop(event)) {
            this._focusTabbable();
          }
        },
      });

      // We assume that any existing aria-describedby attribute means
      // that the dialog content is marked up properly
      // otherwise we brute force the content as the description
      if (!this.element.find('[aria-describedby]').length) {
        this.uiDialog.attr({
          'aria-describedby': this.element.uniqueId().attr('id'),
        });
      }
    },
    // Override jQuery UI's `_focusTabbable()` so finding tabbable elements uses
    // the core/tabbable library instead of jQuery UI's `:tabbable` selector.
    _focusTabbable() {
      // Set focus to the first match:

      // 1. An element that was focused previously.
      let hasFocus = this._focusedElement ? this._focusedElement.get(0) : null;

      // 2. First element inside the dialog matching [autofocus].
      if (!hasFocus) {
        hasFocus = this.element.find('[autofocus]').get(0);
      }

      // 3. Tabbable element inside the content element.
      // 4. Tabbable element inside the buttonpane.
      if (!hasFocus) {
        const $elements = [this.element, this.uiDialogButtonPane];
        for (let i = 0; i < $elements.length; i++) {
          const element = $elements[i].get(0);
          if (element) {
            const elementTabbable = tabbable(element);
            hasFocus = elementTabbable.length ? elementTabbable[0] : null;
          }
          if (hasFocus) {
            break;
          }
        }
      }

      // 5. The close button.
      if (!hasFocus) {
        const closeBtn = this.uiDialogTitlebarClose.get(0);
        hasFocus = closeBtn && isTabbable(closeBtn) ? closeBtn : null;
      }

      // 6. The dialog itself.
      if (!hasFocus) {
        hasFocus = this.uiDialog.get(0);
      }
      $(hasFocus).eq(0).trigger('focus');
    },
  });
})(jQuery, window.tabbable);
