/**
 * Base framework for Drupal-specific JavaScript, behaviors, and settings.
 */
window.Drupal = {behaviors: {}};

// Class indicating that JS is enabled; used for styling purpose.
document.documentElement.className += ' js';

// Allow other JavaScript libraries to use $.
if (window.jQuery) {
  jQuery.noConflict();
}

// JavaScript should be made compatible with libraries other than jQuery by
// wrapping it in an anonymous closure.
(function (domready, Drupal, drupalSettings, drupalTranslations) {

  "use strict";

  /**
   * Custom error type thrown after attach/detach if one or more behaviors failed.
   *
   * @param list
   *   An array of errors thrown during attach/detach.
   * @param event
   *   A string containing either 'attach' or 'detach'.
   */
  function DrupalBehaviorError(list, event) {
    this.name = 'DrupalBehaviorError';
    this.event = event || 'attach';
    this.list = list;
    // Makes the list of errors readable.
    var messageList = [];
    messageList.push(this.event);
    var il = this.list.length;
    for (var i = 0; i < il; i++) {
      messageList.push(this.list[i].behavior + ': ' + this.list[i].error.message);
    }
    this.message = messageList.join(' ; ');
  }

  DrupalBehaviorError.prototype = new Error();

  /**
   * Attach all registered behaviors to a page element.
   *
   * Behaviors are event-triggered actions that attach to page elements, enhancing
   * default non-JavaScript UIs. Behaviors are registered in the Drupal.behaviors
   * object using the method 'attach' and optionally also 'detach' as follows:
   * @code
   *    Drupal.behaviors.behaviorName = {
   *      attach: function (context, settings) {
   *        ...
   *      },
   *      detach: function (context, settings, trigger) {
   *        ...
   *      }
   *    };
   * @endcode
   *
   * Drupal.attachBehaviors is added below to the jQuery.ready event and therefore
   * runs on initial page load. Developers implementing Ajax in their solutions
   * should also call this function after new page content has been loaded,
   * feeding in an element to be processed, in order to attach all behaviors to
   * the new content.
   *
   * Behaviors should use
   * @code
   *   var elements = $(context).find(selector).once('behavior-name');
   * @endcode
   * to ensure the behavior is attached only once to a given element. (Doing so
   * enables the reprocessing of given elements, which may be needed on occasion
   * despite the ability to limit behavior attachment to a particular element.)
   *
   * @param context
   *   An element to attach behaviors to. If none is given, the document element
   *   is used.
   * @param settings
   *   An object containing settings for the current context. If none is given,
   *   the global drupalSettings object is used.
   */
  Drupal.attachBehaviors = function (context, settings) {
    context = context || document;
    settings = settings || drupalSettings;
    var errors = [];
    var behaviors = Drupal.behaviors;
    // Execute all of them.
    for (var i in behaviors) {
      if (behaviors.hasOwnProperty(i) && typeof behaviors[i].attach === 'function') {
        // Don't stop the execution of behaviors in case of an error.
        try {
          behaviors[i].attach(context, settings);
        }
        catch (e) {
          errors.push({behavior: i, error: e});
        }
      }
    }
    // Once all behaviors have been processed, inform the user about errors.
    if (errors.length) {
      throw new DrupalBehaviorError(errors, 'attach');
    }
  };

  // Attach all behaviors.
  domready(function () { Drupal.attachBehaviors(document, drupalSettings); });

  /**
   * Detach registered behaviors from a page element.
   *
   * Developers implementing AHAH/Ajax in their solutions should call this
   * function before page content is about to be removed, feeding in an element
   * to be processed, in order to allow special behaviors to detach from the
   * content.
   *
   * Such implementations should use .findOnce() and .removeOnce() to find
   * elements with their corresponding Drupal.behaviors.behaviorName.attach
   * implementation, i.e. .removeOnce('behaviorName'), to ensure the behavior is
   * detached only from previously processed elements.
   *
   * @param context
   *   An element to detach behaviors from. If none is given, the document element
   *   is used.
   * @param settings
   *   An object containing settings for the current context. If none given, the
   *   global drupalSettings object is used.
   * @param trigger
   *   A string containing what's causing the behaviors to be detached. The
   *   possible triggers are:
   *   - unload: (default) The context element is being removed from the DOM.
   *   - move: The element is about to be moved within the DOM (for example,
   *     during a tabledrag row swap). After the move is completed,
   *     Drupal.attachBehaviors() is called, so that the behavior can undo
   *     whatever it did in response to the move. Many behaviors won't need to
   *     do anything simply in response to the element being moved, but because
   *     IFRAME elements reload their "src" when being moved within the DOM,
   *     behaviors bound to IFRAME elements (like WYSIWYG editors) may need to
   *     take some action.
   *   - serialize: When an Ajax form is submitted, this is called with the
   *     form as the context. This provides every behavior within the form an
   *     opportunity to ensure that the field elements have correct content
   *     in them before the form is serialized. The canonical use-case is so
   *     that WYSIWYG editors can update the hidden textarea to which they are
   *     bound.
   *
   * @see Drupal.attachBehaviors
   */
  Drupal.detachBehaviors = function (context, settings, trigger) {
    context = context || document;
    settings = settings || drupalSettings;
    trigger = trigger || 'unload';
    var errors = [];
    var behaviors = Drupal.behaviors;
    // Execute all of them.
    for (var i in behaviors) {
      if (behaviors.hasOwnProperty(i) && typeof behaviors[i].detach === 'function') {
        // Don't stop the execution of behaviors in case of an error.
        try {
          behaviors[i].detach(context, settings, trigger);
        }
        catch (e) {
          errors.push({behavior: i, error: e});
        }
      }
    }
    // Once all behaviors have been processed, inform the user about errors.
    if (errors.length) {
      throw new DrupalBehaviorError(errors, 'detach:' + trigger);
    }
  };

  /**
   * Helper to test document width for mobile configurations.
   * @todo Temporary solution for the mobile initiative.
   */
  Drupal.checkWidthBreakpoint = function (width) {
    width = width || drupalSettings.widthBreakpoint || 640;
    return (document.documentElement.clientWidth > width);
  };

  /**
   * Encode special characters in a plain-text string for display as HTML.
   *
   * @param str
   *   The string to be encoded.
   * @return
   *   The encoded string.
   * @ingroup sanitization
   */
  Drupal.checkPlain = function (str) {
    str = str.toString()
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
    return str;
  };

  /**
   * Replace placeholders with sanitized values in a string.
   *
   * @param {String} str
   *   A string with placeholders.
   * @param {Object} args
   *   An object of replacements pairs to make. Incidences of any key in this
   *   array are replaced with the corresponding value. Based on the first
   *   character of the key, the value is escaped and/or themed:
   *    - !variable: inserted as is
   *    - @variable: escape plain text to HTML (Drupal.checkPlain)
   *    - %variable: escape text and theme as a placeholder for user-submitted
   *      content (checkPlain + Drupal.theme('placeholder'))
   *
   * @return {String}
   *   Returns the replaced string.
   *
   * @see Drupal.t()
   * @ingroup sanitization
   */
  Drupal.formatString = function (str, args) {
    // Keep args intact.
    var processedArgs = {};
    // Transform arguments before inserting them.
    for (var key in args) {
      if (args.hasOwnProperty(key)) {
        switch (key.charAt(0)) {
          // Escaped only.
          case '@':
            processedArgs[key] = Drupal.checkPlain(args[key]);
            break;
          // Pass-through.
          case '!':
            processedArgs[key] = args[key];
            break;
          // Escaped and placeholder.
          default:
            processedArgs[key] = Drupal.theme('placeholder', args[key]);
            break;
        }
      }
    }

    return Drupal.stringReplace(str, processedArgs, null);
  };

  /**
   * Replace substring.
   *
   * The longest keys will be tried first. Once a substring has been replaced,
   * its new value will not be searched again.
   *
   * @param {String} str
   *   A string with placeholders.
   * @param {Object} args
   *   Key-value pairs.
   * @param {Array|null} keys
   *   Array of keys from the "args".  Internal use only.
   *
   * @return {String}
   *   Returns the replaced string.
   */
  Drupal.stringReplace = function (str, args, keys) {
    if (str.length === 0) {
      return str;
    }

    // If the array of keys is not passed then collect the keys from the args.
    if (!Array.isArray(keys)) {
      keys = [];
      for (var k in args) {
        if (args.hasOwnProperty(k)) {
          keys.push(k);
        }
      }

      // Order the keys by the character length. The shortest one is the first.
      keys.sort(function (a, b) { return a.length - b.length; });
    }

    if (keys.length === 0) {
      return str;
    }

    // Take next longest one from the end.
    var key = keys.pop();
    var fragments = str.split(key);

    if (keys.length) {
      for (var i = 0; i < fragments.length; i++) {
        // Process each fragment with a copy of remaining keys.
        fragments[i] = Drupal.stringReplace(fragments[i], args, keys.slice(0));
      }
    }

    return fragments.join(args[key]);
  };

  /**
   * Translate strings to the page language or a given language.
   *
   * See the documentation of the server-side t() function for further details.
   *
   * @param str
   *   A string containing the English string to translate.
   * @param args
   *   An object of replacements pairs to make after translation. Incidences
   *   of any key in this array are replaced with the corresponding value.
   *   See Drupal.formatString().
   *
   * @param options
   *   - 'context' (defaults to the empty context): The context the source string
   *     belongs to.
   *
   * @return
   *   The translated string.
   */
  Drupal.t = function (str, args, options) {
    options = options || {};
    options.context = options.context || '';

    // Fetch the localized version of the string.
    if (typeof drupalTranslations !== 'undefined' && drupalTranslations.strings && drupalTranslations.strings[options.context] && drupalTranslations.strings[options.context][str]) {
      str = drupalTranslations.strings[options.context][str];
    }

    if (args) {
      str = Drupal.formatString(str, args);
    }
    return str;
  };

  /**
   * Returns the URL to a Drupal page.
   */
  Drupal.url = function (path) {
    return drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix + path;
  };

  /**
   * Format a string containing a count of items.
   *
   * This function ensures that the string is pluralized correctly. Since
   * Drupal.t() is called by this function, make sure not to pass
   * already-localized strings to it.
   *
   * See the documentation of the server-side
   * \Drupal\Core\StringTranslation\TranslationInterface::formatPlural()
   * function for more details.
   *
   * @param {Number} count
   *   The item count to display.
   * @param {String} singular
   *   The string for the singular case. Please make sure it is clear this is
   *   singular, to ease translation (e.g. use "1 new comment" instead of "1
   *   new"). Do not use @count in the singular string.
   * @param {String} plural
   *   The string for the plural case. Please make sure it is clear this is
   *   plural, to ease translation. Use @count in place of the item count, as in
   *   "@count new comments".
   * @param {Object} args
   *   An object of replacements pairs to make after translation. Incidences
   *   of any key in this array are replaced with the corresponding value.
   *   See Drupal.formatString().
   *   Note that you do not need to include @count in this array.
   *   This replacement is done automatically for the plural case.
   * @param {Object} options
   *   The options to pass to the Drupal.t() function.
   *
   * @return {String}
   *   A translated string.
   */
  Drupal.formatPlural = function (count, singular, plural, args, options) {
    args = args || {};
    args['@count'] = count;

    var pluralDelimiter = drupalSettings.pluralDelimiter;
    var translations = Drupal.t(singular + pluralDelimiter + plural, args, options).split(pluralDelimiter);
    var index = 0;

    // Determine the index of the plural form.
    if (typeof drupalTranslations !== 'undefined' && drupalTranslations.pluralFormula) {
      index = count in drupalTranslations.pluralFormula ? drupalTranslations.pluralFormula[count] : drupalTranslations.pluralFormula['default'];
    }
    else if (args['@count'] !== 1) {
      index = 1;
    }

    return translations[index];
  };

  /**
   * Encodes a Drupal path for use in a URL.
   *
   * For aesthetic reasons slashes are not escaped.
   */
  Drupal.encodePath = function (item) {
    return window.encodeURIComponent(item).replace(/%2F/g, '/');
  };

  /**
   * Generate the themed representation of a Drupal object.
   *
   * All requests for themed output must go through this function. It examines
   * the request and routes it to the appropriate theme function. If the current
   * theme does not provide an override function, the generic theme function is
   * called.
   *
   * For example, to retrieve the HTML for text that should be emphasized and
   * displayed as a placeholder inside a sentence, call
   * Drupal.theme('placeholder', text).
   *
   * @param func
   *   The name of the theme function to call.
   * @param ...
   *   Additional arguments to pass along to the theme function.
   * @return
   *   Any data the theme function returns. This could be a plain HTML string,
   *   but also a complex object.
   */
  Drupal.theme = function (func) {
    var args = Array.prototype.slice.apply(arguments, [1]);
    if (func in Drupal.theme) {
      return Drupal.theme[func].apply(this, args);
    }
  };

  /**
   * Formats text for emphasized display in a placeholder inside a sentence.
   *
   * @param str
   *   The text to format (plain-text).
   * @return
   *   The formatted text (html).
   */
  Drupal.theme.placeholder = function (str) {
    return '<em class="placeholder">' + Drupal.checkPlain(str) + '</em>';
  };

})(domready, Drupal, window.drupalSettings, window.drupalTranslations);
