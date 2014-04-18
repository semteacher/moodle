YUI.add('moodle-atto_equation-button', function (Y, NAME) {

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    atto_equation
 * @copyright  2013 Damyon Wiese  <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Atto text editor equation plugin.
 */

/**
 * Atto equation editor.
 *
 * @namespace M.atto_equation
 * @class Button
 * @extends M.editor_atto.EditorPlugin
 */

var COMPONENTNAME = 'atto_equation',
    LOGNAME = 'atto_equation',
    CSS = {
        EQUATION_TEXT: 'atto_equation_equation',
        EQUATION_PREVIEW: 'atto_equation_preview',
        SUBMIT: 'atto_equation_submit',
        LIBRARY: 'atto_equation_library',
        LIBRARY_GROUPS: 'atto_equation_groups',
        LIBRARY_GROUP_PREFIX: 'atto_equation_group'
    },
    SELECTORS = {
        LIBRARY: '.' + CSS.LIBRARY,
        LIBRARY_GROUP: '.' + CSS.LIBRARY_GROUPS + ' > div > div',
        EQUATION_TEXT: '.' + CSS.EQUATION_TEXT,
        EQUATION_PREVIEW: '.' + CSS.EQUATION_PREVIEW,
        SUBMIT: '.' + CSS.SUBMIT,
        LIBRARY_BUTTON: '.' + CSS.LIBRARY + ' button'
    },
    DELIMITERS = {
        START: '\\(',
        END: '\\)'
    },
    TEMPLATES = {
        FORM: '' +
            '<form class="atto_form">' +
                '{{{library}}}' +
                '<label for="{{elementid}}_{{CSS.EQUATION_TEXT}}">{{{get_string "editequation" component texdocsurl}}}</label>' +
                '<textarea class="fullwidth {{CSS.EQUATION_TEXT}}" id="{{elementid}}_{{CSS.EQUATION_TEXT}}" rows="8"></textarea><br/>' +
                '<label for="{{elementid}}_{{CSS.EQUATION_PREVIEW}}">{{get_string "preview" component}}</label>' +
                '<div class="fullwidth {{CSS.EQUATION_PREVIEW}}" id="{{elementid}}_{{CSS.EQUATION_PREVIEW}}"></div>' +
                '<div class="mdl-align">' +
                    '<br/>' +
                    '<button class="{{CSS.SUBMIT}}">{{get_string "saveequation" component}}</button>' +
                '</div>' +
            '</form>',
        LIBRARY: '' +
            '<div class="{{CSS.LIBRARY}}">' +
                '<ul>' +
                    '{{#each library}}' +
                        '<li><a href="#{{../elementid}}_{{../CSS.LIBRARY_GROUP_PREFIX}}_{{@key}}">' +
                            '{{get_string groupname ../component}}' +
                        '</a></li>' +
                    '{{/each}}' +
                '</ul>' +
                '<div class="{{CSS.LIBRARY_GROUPS}}">' +
                    '{{#each library}}' +
                        '<div id="{{../elementid}}_{{../CSS.LIBRARY_GROUP_PREFIX}}_{{@key}}">' +
                            '<div role="toolbar">' +
                            '{{#split "\n" elements}}' +
                                '<button tabindex="-1" data-tex="{{this}}" aria-label="{{this}}" title="{{this}}">' +
                                    '{{../../DELIMITERS.START}}{{this}}{{../../DELIMITERS.END}}' +
                                '</button>' +
                            '{{/split}}' +
                            '</div>' +
                        '</div>' +
                    '{{/each}}' +
                '</div>' +
            '</div>'
    };

Y.namespace('M.atto_equation').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {

    /**
     * The selection object returned by the browser.
     *
     * @property _currentSelection
     * @type Range
     * @default null
     * @private
     */
    _currentSelection: null,

    /**
     * The cursor position in the equation textarea.
     *
     * @property _lastCursorPos
     * @type Number
     * @default 0
     * @private
     */
    _lastCursorPos: 0,

    /**
     * A reference to the dialogue content.
     *
     * @property _content
     * @type Node
     * @private
     */
    _content: null,

    /**
     * The source equation we are editing in the text.
     *
     * @property _sourceEquation
     * @type String
     * @private
     */
    _sourceEquation: '',

    /**
     * A reference to the tab focus set on each group.
     *
     * The keys are the IDs of the group, the value is the Node on which the focus is set.
     *
     * @property _groupFocus
     * @type Object
     * @private
     */
    _groupFocus: null,

    initializer: function() {
        this._groupFocus = {};

        // If there is a tex filter active - enable this button.
        if (this.get('texfilteractive')) {
            // Add the button to the toolbar.
            this.addButton({
                icon: 'e/math',
                callback: this._displayDialogue
            });

            // We need custom highlight logic for this button.
            this.get('host').on('atto:selectionchanged', function() {
                if (this._resolveEquation()) {
                    this.highlightButtons();
                } else {
                    this.unHighlightButtons();
                }
            }, this);

            // We need to convert these to a non dom node based format.
            this.editor.all('tex').each(function (texNode) {
                var replacement = Y.Node.create('<span>' + DELIMITERS.START + ' ' + texNode.get('text') + ' ' + DELIMITERS.END + '</span>');
                texNode.replace(replacement);
            });
        }

    },

    /**
     * Display the equation editor.
     *
     * @method _displayDialogue
     * @private
     */
    _displayDialogue: function() {
        this._currentSelection = this.get('host').getSelection();

        if (this._currentSelection === false) {
            return;
        }

        var dialogue = this.getDialogue({
            headerContent: M.util.get_string('pluginname', COMPONENTNAME),
            focusAfterHide: true,
            width: 600
        });

        var content = this._getDialogueContent();
        dialogue.set('bodyContent', content);

        var library = content.one(SELECTORS.LIBRARY);

        var tabview = new Y.TabView({
            srcNode: library
        });

        tabview.render();
        dialogue.show();
        // Trigger any JS filters to reprocess the new nodes.
        Y.fire(M.core.event.FILTER_CONTENT_UPDATED, {nodes: (new Y.NodeList(dialogue.get('boundingBox')))});

        var equation = this._resolveEquation();
        if (equation) {
            content.one(SELECTORS.EQUATION_TEXT).set('text', equation);
        }
        this._updatePreview(false);
    },

    /**
     * If there is selected text and it is part of an equation,
     * extract the equation (and set it in the form).
     *
     * @method _resolveEquation
     * @private
     * @return {String|Boolean} The equation or false.
     */
    _resolveEquation: function() {

        // Find the equation in the surrounding text.
        var selectedNode = this.get('host').getSelectionParentNode(),
            text,
            equation,
            patterns = [], i;

        // Note this is a document fragment and YUI doesn't like them.
        if (!selectedNode) {
            return false;
        }

        text = Y.one(selectedNode).get('text');
        // We use space or not space because . does not match new lines.
        // $$ blah $$.
        patterns.push(/\$\$([\S\s]*)\$\$/);
        // E.g. "\( blah \)".
        patterns.push(/\\\(([\S\s]*)\\\)/);
        // E.g. "\[ blah \]".
        patterns.push(/\\\[([\S\s]*)\\\]/);
        // E.g. "[tex] blah [/tex]".
        patterns.push(/\[tex\]([\S\s]*)\[\/tex\]/);

        for (i = 0; i < patterns.length; i++) {
            pattern = patterns[i];
            equation = pattern.exec(text);
            if (equation && equation.length) {
                // Remember the inner match so we can replace it later.
                this.sourceEquation = equation = equation[1];

                return equation;
            }
        }

        this.sourceEquation = '';
        return false;
    },

    /**
     * Handle insertion of a new equation, or update of an existing one.
     *
     * @method _setEquation
     * @param {EventFacade} e
     * @private
     */
    _setEquation: function(e) {
        var input,
            selectedNode,
            text,
            value,
            host;

        host = this.get('host');

        e.preventDefault();
        this.getDialogue({
            focusAfterHide: null
        }).hide();

        input = e.currentTarget.ancestor('.atto_form').one('textarea');

        value = input.get('value');
        if (value !== '') {
            host.setSelection(this._currentSelection);

            if (this.sourceEquation.length) {
                // Replace the equation.
                selectedNode = Y.one(host.getSelectionParentNode());
                text = selectedNode.get('text');

                text = text.replace(this.sourceEquation, value);
                selectedNode.set('text', text);
            } else {
                // Insert the new equation.
                value = DELIMITERS.START + ' ' + value + ' ' + DELIMITERS.END;
                host.insertContentAtFocusPoint(value);
            }

            // Clean the YUI ids from the HTML.
            this.markUpdated();
        }
    },

    /**
     * Smart throttle, only call a function every delay milli seconds,
     * and always run the last call. Y.throttle does not work here,
     * because it calls the function immediately, the first time, and then
     * ignores repeated calls within X seconds. This does not guarantee
     * that the last call will be executed (which is required here).
     *
     * @param {function} fn
     * @param {Number} delay Delay in milliseconds
     * @method _throttle
     * @private
     */
    _throttle: function(fn, delay) {
        var timer = null;
        return function () {
            var context = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
              fn.apply(context, args);
            }, delay);
        };
    },

    /**
     * Update the preview div to match the current equation.
     *
     * @param {EventFacade} e
     * @method _updatePreview
     * @private
     */
    _updatePreview: function(e) {
        var textarea = this._content.one(SELECTORS.EQUATION_TEXT),
            equation = textarea.get('value'),
            url,
            preview,
            currentPos = textarea.get('selectionStart'),
            prefix = '',
            cursorLatex = '\\square ',
            isChar,
            params;

        if (e) {
            e.preventDefault();
        }

        if (!currentPos) {
            currentPos = 0;
        }
        // Move the cursor so it does not break expressions.
        //
        while (equation.charAt(currentPos) === '\\' && currentPos > 0) {
            currentPos -= 1;
        }
        isChar = /[a-zA-Z\{\}]/;
        while (isChar.test(equation.charAt(currentPos)) && currentPos < equation.length) {
            currentPos += 1;
        }
        // Save the cursor position - for insertion from the library.
        this._lastCursorPos = currentPos;
        equation = prefix + equation.substring(0, currentPos) + cursorLatex + equation.substring(currentPos);

        var previewNode = this._content.one(SELECTORS.EQUATION_PREVIEW);
        equation = DELIMITERS.START + ' ' + equation + ' ' + DELIMITERS.END;
        // Make an ajax request to the filter.
        url = M.cfg.wwwroot + '/lib/editor/atto/plugins/equation/ajax.php';
        params = {
            sesskey: M.cfg.sesskey,
            contextid: this.get('contextid'),
            action: 'filtertext',
            text: equation
        };

        preview = Y.io(url, {
            sync: true,
            data: params
        });

        if (preview.status === 200) {
            previewNode.setHTML(preview.responseText);
            Y.fire(M.core.event.FILTER_CONTENT_UPDATED, {nodes: (new Y.NodeList(previewNode))});
        }
    },

    /**
     * Return the dialogue content for the tool, attaching any required
     * events.
     *
     * @method _getDialogueContent
     * @return {Node}
     * @private
     */
    _getDialogueContent: function() {
        var library = this._getLibraryContent(),
            template = Y.Handlebars.compile(TEMPLATES.FORM);

        this._content = Y.Node.create(template({
            elementid: this.get('host').get('elementid'),
            component: COMPONENTNAME,
            library: library,
            texdocsurl: this.get('texdocsurl'),
            CSS: CSS
        }));

        // Sets the default focus.
        this._content.all(SELECTORS.LIBRARY_GROUP).each(function(group) {
            // The first button gets the focus.
            this._setGroupTabFocus(group, group.one('button'));
            // Sometimes the filter adds an anchor in the button, no tabindex on that.
            group.all('button a').setAttribute('tabindex', '-1');
        }, this);

        // Keyboard navigation in groups.
        this._content.delegate('key', this._groupNavigation, 'down:37,39', SELECTORS.LIBRARY_BUTTON, this);

        this._content.one(SELECTORS.SUBMIT).on('click', this._setEquation, this);
        this._content.one(SELECTORS.EQUATION_TEXT).on('valuechange', this._throttle(this._updatePreview, 500), this);
        this._content.one(SELECTORS.EQUATION_TEXT).on('mouseup', this._throttle(this._updatePreview, 500), this);
        this._content.one(SELECTORS.EQUATION_TEXT).on('keyup', this._throttle(this._updatePreview, 500), this);
        this._content.delegate('click', this._selectLibraryItem, SELECTORS.LIBRARY_BUTTON, this);

        return this._content;
    },

    /**
     * Callback handling the keyboard navigation in the groups of the library.
     *
     * @param {EventFacade} e The event.
     * @method _groupNavigation
     * @private
     */
    _groupNavigation: function(e) {
        e.preventDefault();

        var current = e.currentTarget,
            parent = current.get('parentNode'), // This must be the <div> containing all the buttons of the group.
            buttons = parent.all('button'),
            direction = e.keyCode !== 37 ? 1 : -1,
            index = buttons.indexOf(current),
            nextButton;

        if (index < 0) {
            index = 0;
        }

        index += direction;
        if (index < 0) {
            index = buttons.size() - 1;
        } else if (index >= buttons.size()) {
            index = 0;
        }
        nextButton = buttons.item(index);

        this._setGroupTabFocus(parent, nextButton);
        nextButton.focus();
    },

    /**
     * Sets tab focus for the group.
     *
     * @method _setGroupTabFocus
     * @param {Node} button The node that focus should now be set to.
     * @private
     */
    _setGroupTabFocus: function(parent, button) {
        var parentId = parent.generateID();

        // Unset the previous entry.
        if (typeof this._groupFocus[parentId] !== 'undefined') {
            this._groupFocus[parentId].setAttribute('tabindex', '-1');
        }

        // Set on the new entry.
        this._groupFocus[parentId] = button;
        button.setAttribute('tabindex', 0);
        parent.setAttribute('aria-activedescendant', button.generateID());
    },

    /**
     * Reponse to button presses in the TeX library panels.
     *
     * @method _selectLibraryItem
     * @param {EventFacade} e
     * @return {string}
     * @private
     */
    _selectLibraryItem: function(e) {
        var tex = e.currentTarget.getAttribute('data-tex');

        e.preventDefault();

        // Set the group focus on the button.
        this._setGroupTabFocus(e.currentTarget.get('parentNode'), e.currentTarget);

        input = e.currentTarget.ancestor('.atto_form').one('textarea');

        value = input.get('value');

        value = value.substring(0, this._lastCursorPos) + tex + value.substring(this._lastCursorPos, value.length);

        input.set('value', value);
        input.focus();

        var focusPoint = this._lastCursorPos + tex.length,
            realInput = input.getDOMNode();
        if (typeof realInput.selectionStart === "number") {
            // Modern browsers have selectionStart and selectionEnd to control the cursor position.
            realInput.selectionStart = realInput.selectionEnd = focusPoint;
        } else if (typeof realInput.createTextRange !== "undefined") {
            // Legacy browsers (IE<=9) use createTextRange().
            var range = realInput.createTextRange();
            range.moveToPoint(focusPoint);
            range.select();
        }
        // Focus must be set before updating the preview for the cursor box to be in the correct location.
        this._updatePreview(false);
    },

    /**
     * Return the HTML for rendering the library of predefined buttons.
     *
     * @method _getLibraryContent
     * @return {string}
     * @private
     */
    _getLibraryContent: function() {
        var template = Y.Handlebars.compile(TEMPLATES.LIBRARY),
            library = this.get('library'),
            content = '';

        // Helper to iterate over a newline separated string.
        Y.Handlebars.registerHelper('split', function(delimiter, str, options) {
            var parts,
                current,
                out;
            if (typeof delimiter === "undefined" || typeof str === "undefined") {
                return '';
            }

            out = '';
            parts = str.trim().split(delimiter);
            while (parts.length > 0) {
                current = parts.shift().trim();
                out += options.fn(current);
            }

            return out;
        });
        content = template({
            elementid: this.get('host').get('elementid'),
            component: COMPONENTNAME,
            library: library,
            CSS: CSS,
            DELIMITERS: DELIMITERS
        });

        var url = M.cfg.wwwroot + '/lib/editor/atto/plugins/equation/ajax.php';
        var params = {
            sesskey: M.cfg.sesskey,
            contextid: this.get('contextid'),
            action: 'filtertext',
            text: content
        };

        preview = Y.io(url, {
            sync: true,
            data: params,
            method: 'POST'
        });

        if (preview.status === 200) {
            content = preview.responseText;
        }
        return content;
    }
}, {
    ATTRS: {
        /**
         * Whether the TeX filter is currently active.
         *
         * @attribute texfilteractive
         * @type Boolean
         */
        texfilteractive: {
            value: false
        },

        /**
         * The contextid to use when generating this preview.
         *
         * @attribute contextid
         * @type String
         */
        contextid: {
            value: null
        },

        /**
         * The content of the example library.
         *
         * @attribute library
         * @type object
         */
        library: {
            value: {}
        },

        /**
         * The link to the Moodle Docs page about TeX.
         *
         * @attribute texdocsurl
         * @type string
         */
        texdocsurl: {
            value: null
        }

    }
});


}, '@VERSION@', {"requires": ["moodle-editor_atto-plugin", "moodle-core-event", "io", "event-valuechange", "tabview"]});
