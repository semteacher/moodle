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

/*
 * @package    atto_image
 * @copyright  2013 Damyon Wiese  <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_image_alignment-button
 */

/**
 * Atto image selection tool.
 *
 * @namespace M.atto_image
 * @class Button
 * @extends M.editor_atto.EditorPlugin
 */

var CSS = {
        INPUTALIGNMENT: 'atto_image_alignment',
        INPUTALT: 'atto_image_altentry',
        INPUTHEIGHT: 'atto_image_heightentry',
        INPUTSUBMIT: 'atto_image_urlentrysubmit',
        INPUTURL: 'atto_image_urlentry',
        INPUTSIZE: 'atto_image_size',
        INPUTWIDTH: 'atto_image_widthentry',
        IMAGEALTWARNING: 'atto_image_altwarning',
        IMAGEBROWSER: 'openimagebrowser',
        IMAGEPRESENTATION: 'atto_image_presentation',
        INPUTCONSTRAIN: 'atto_image_constrain',
        IMAGEPREVIEW: 'atto_image_preview'
    },
    ALIGNMENTS = [
        // Vertical alignment.
        {
            name: 'baseline',
            str: 'alignment_baseline',
            value: 'vertical-align'
        }, {
            name: 'sub',
            str: 'alignment_sub',
            value: 'vertical-align'
        }, {
            name: 'super',
            str: 'alignment_super',
            value: 'vertical-align'
        }, {
            name: 'top',
            str: 'alignment_top',
            value: 'vertical-align'
        }, {
            name: 'text-top',
            str: 'alignment_texttop',
            value: 'vertical-align'
        }, {
            name: 'middle',
            str: 'alignment_middle',
            value: 'vertical-align'
        }, {
            name: 'bottom',
            str: 'alignment_bottom',
            value: 'vertical-align'
        }, {
            name: 'text-bottom',
            str: 'alignment_textbottom',
            value: 'vertical-align'
        },

        // Floats.
        {
            name: 'left',
            str: 'alignment_left',
            value: 'float'
        }, {
            name: 'right',
            str: 'alignment_right',
            value: 'float'
        }
    ];

var COMPONENTNAME = 'atto_image',

    TEMPLATE = '' +
            '<form class="atto_form">' +
                '<label for="{{elementid}}_{{CSS.INPUTURL}}">{{get_string "enterurl" component}}</label>' +
                '<input class="fullwidth {{CSS.INPUTURL}}" type="url" id="{{elementid}}_{{CSS.INPUTURL}}" size="32"/>' +
                '<br/>' +

                // Add the repository browser button.
                '{{#if showFilepicker}}' +
                    '<button class="{{CSS.IMAGEBROWSER}}" type="button">{{get_string "browserepositories" component}}</button>' +
                '{{/if}}' +

                // Add the Alt box.
                '<div style="display:none" role="alert" class="warning {{CSS.IMAGEALTWARNING}}">' +
                    '{{get_string "presentationoraltrequired" component}}' +
                '</div>' +
                '<label for="{{elementid}}_{{CSS.INPUTALT}}">{{get_string "enteralt" component}}</label>' +
                '<input class="fullwidth {{CSS.INPUTALT}}" type="text" value="" id="{{elementid}}_{{CSS.INPUTALT}}" size="32"/>' +
                '<br/>' +

                // Add the presentation select box.
                '<input type="checkbox" class="{{CSS.IMAGEPRESENTATION}}" id="{{elementid}}_{{CSS.IMAGEPRESENTATION}}"/>' +
                '<label class="sameline" for="{{elementid}}_{{CSS.IMAGEPRESENTATION}}">{{get_string "presentation" component}}</label>' +
                '<br/>' +

                // Add the size entry boxes.
                '<label class="sameline" for="{{elementid}}_{{CSS.INPUTSIZE}}">Size</label>' +
                '<div id="{{elementid}}_{{CSS.INPUTSIZE}}" class="{{CSS.INPUTSIZE}}">' +
                '<label class="accesshide" for="{{elementid}}_{{CSS.INPUTWIDTH}}">{{get_string "width" component}}</label>' +
                '<input type="text" class="{{CSS.INPUTWIDTH}} input-mini" id="{{elementid}}_{{CSS.INPUTWIDTH}}" size="4"/> x ' +

                // Add the height entry box.
                '<label class="accesshide" for="{{elementid}}_{{CSS.INPUTHEIGHT}}">{{get_string "height" component}}</label>' +
                '<input type="text" class="{{CSS.INPUTHEIGHT}} input-mini" id="{{elementid}}_{{CSS.INPUTHEIGHT}}" size="4"/>' +

                // Add the constrain checkbox.
                '<input type="checkbox" class="{{CSS.INPUTCONSTRAIN}} sameline" id="{{elementid}}_{{CSS.INPUTCONSTRAIN}}"/>' +
                '<label for="{{elementid}}_{{CSS.INPUTCONSTRAIN}}">{{get_string "constrain" component}}</label>' +
                '</div>' +

                // Add the alignment selector.
                '<label class="sameline" for="{{elementid}}_{{CSS.INPUTALIGNMENT}}">{{get_string "alignment" component}}</label>' +
                '<select class="{{CSS.INPUTALIGNMENT}}" id="{{elementid}}_{{CSS.INPUTALIGNMENT}}">' +
                    '{{#each alignments}}' +
                        '<option value="{{value}}:{{name}};">{{get_string str ../component}}</option>' +
                    '{{/each}}' +
                '</select>' +
                '<br/>' +

                // Add the image preview.
                '<div class="mdl-align">' +
                '<img src="#" class="{{CSS.IMAGEPREVIEW}}" id="{{elementid}}_{{CSS.IMAGEPREVIEW}}" alt="" style="display: none;"/>' +
                '<br/>' +

                // Add the submit button and close the form.
                '<button class="{{CSS.INPUTSUBMIT}}" type="submit">{{get_string "createimage" component}}</button>' +
                '</div>' +
            '</form>',

        IMAGETEMPLATE = '' +
            '<img src="{{url}}" alt="{{alt}}" ' +
                '{{#if width}}width="{{width}}" {{/if}}' +
                '{{#if height}}height="{{height}}" {{/if}}' +
                '{{#if presentation}}role="presentation" {{/if}}' +
                '{{#if alignment}}style="{{alignment}}" {{/if}}' +
                '/>';

Y.namespace('M.atto_image').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    /**
     * A reference to the current selection at the time that the dialogue
     * was opened.
     *
     * @property _currentSelection
     * @type Range
     * @private
     */
    _currentSelection: null,

    /**
     * The most recently selected image.
     *
     * @param _selectedImage
     * @type Node
     * @private
     */
    _selectedImage: null,

    /**
     * A reference to the currently open form.
     *
     * @param _form
     * @type Node
     * @private
     */
    _form: null,

    /**
     * Remember the image true dimensions so we can constrain resizing.
     *
     * @param _imageRawWidth
     * @type Integer
     * @private
     */
    _imageRawWidth: 0,

    /**
     * Remember the image true dimensions so we can constrain resizing.
     *
     * @param _imageRawHeight
     * @type Integer
     * @private
     */
    _imageRawHeight: 0,

    initializer: function() {
        this.addButton({
            icon: 'e/insert_edit_image',
            callback: this._displayDialogue,
            tags: 'img',
            tagMatchRequiresAll: false
        });
        this.editor.delegate('dblclick', this._handleDoubleClick, 'img', this);
    },

    /**
     * Handle a double click on an image.
     *
     * @method _handleDoubleClick
     * @param {EventFacade} e
     * @private
     */
    _handleDoubleClick: function(e) {
        var image = e.target;

        var selection = this.get('host').getSelectionFromNode(image);
        this.get('host').setSelection(selection);
        this._displayDialogue();
    },

    /**
     * Display the image editing tool.
     *
     * @method _displayDialogue
     * @private
     */
    _displayDialogue: function() {
        // Store the current selection.
        this._currentSelection = this.get('host').getSelection();
        if (this._currentSelection === false) {
            return;
        }

        var dialogue = this.getDialogue({
            headerContent: M.util.get_string('createimage', COMPONENTNAME),
            width: '480px',
            focusAfterHide: true
        });

        // Set the dialogue content, and then show the dialogue.
        dialogue.set('bodyContent', this._getDialogueContent())
                .show();
    },

    /**
     * Set the inputs for width and height if they are not set, and calculate
     * if the constrain checkbox should be checked or not.
     *
     * @method _loadPreviewImage
     * @param {String} url
     * @private
     */
    _loadPreviewImage: function(url) {
        var image = new Image(), self = this;

        image.onload = function() {
            var input, currentwidth, currentheight, widthRatio, heightRatio;

            self._imageRawWidth = this.width;
            self._imageRawHeight = this.height;

            input = self._form.one('.' + CSS.INPUTWIDTH);
            currentwidth = input.get('value');
            if (currentwidth === '') {
                input.set('value', this.width);
                currentwidth = this.width;
            }
            input = self._form.one('.' + CSS.INPUTHEIGHT);
            currentheight = input.get('value');
            if (currentheight === '') {
                input.set('value', this.height);
                currentheight = this.height;
            }
            input = self._form.one('.' + CSS.IMAGEPREVIEW);
            input.set('src', this.src);
            input.setStyle('display', 'inline');

            if (this.width === 0) {
                this.width = 1;
            }
            if (this.height === 0) {
                this.height = 1;
            }
            input = self._form.one('.' + CSS.INPUTCONSTRAIN);
            widthRatio = Math.round(parseInt(currentwidth, 10) / this.width);
            heightRatio = Math.round(parseInt(currentheight, 10) / this.height);
            input.set('checked', widthRatio === heightRatio);

            // Centre the dialogue once the preview image has loaded.
            self.getDialogue().centerDialogue();
        };

        image.src = url;
    },

    /**
     * Return the dialogue content for the tool, attaching any required
     * events.
     *
     * @method _getDialogueContent
     * @return {Node} The content to place in the dialogue.
     * @private
     */
    _getDialogueContent: function() {
        var template = Y.Handlebars.compile(TEMPLATE),
            canShowFilepicker = this.get('host').canShowFilepicker('image'),
            content = Y.Node.create(template({
                elementid: this.get('host').get('elementid'),
                CSS: CSS,
                component: COMPONENTNAME,
                showFilepicker: canShowFilepicker,
                alignments: ALIGNMENTS
            }));

        this._form = content;

        // Configure the view of the current image.
        this._applyImageProperties(this._form);

        this._form.one('.' + CSS.INPUTURL).on('blur', this._urlChanged, this);
        this._form.one('.' + CSS.INPUTWIDTH).on('blur', this._autoAdjustHeight, this);
        this._form.one('.' + CSS.INPUTHEIGHT).on('blur', this._autoAdjustWidth, this);
        this._form.one('.' + CSS.INPUTURL).on('blur', this._urlChanged, this);
        this._form.one('.' + CSS.INPUTSUBMIT).on('click', this._setImage, this);

        if (canShowFilepicker) {
            this._form.one('.' + CSS.IMAGEBROWSER).on('click', function() {
                    this.get('host').showFilepicker('image', this._filepickerCallback, this);
            }, this);
        }

        return content;
    },

    /**
     * Adjust the height to keep the aspect ratio.
     *
     * @method _autoAdjustHeight
     * @private
     */
    _autoAdjustHeight: function() {
        var currentWidth, newHeight, currentHeight;

        if (!this._form.one('.' + CSS.INPUTCONSTRAIN).get('checked')) {
            currentWidth = parseInt(this._form.one('.' + CSS.INPUTWIDTH).get('value'), 10);
            currentHeight = parseInt(this._form.one('.' + CSS.INPUTHEIGHT).get('value'), 10);
            this._form.one('.' + CSS.IMAGEPREVIEW).setAttribute('height', currentHeight);
            this._form.one('.' + CSS.IMAGEPREVIEW).setAttribute('width', currentWidth);
            return;
        }
        currentWidth = parseInt(this._form.one('.' + CSS.INPUTWIDTH).get('value'), 10);
        newHeight = Math.round((currentWidth / this._imageRawWidth) * this._imageRawHeight);

        this._form.one('.' + CSS.INPUTHEIGHT).set('value', newHeight);
        this._form.one('.' + CSS.IMAGEPREVIEW).setAttribute('height', newHeight);
        this._form.one('.' + CSS.IMAGEPREVIEW).setAttribute('width', currentWidth);
    },

    /**
     * Adjust the width to keep the aspect ratio.
     *
     * @method _autoAdjustWidth
     * @private
     */
    _autoAdjustWidth: function() {
        var currentHeight, newWidth;

        if (!this._form.one('.' + CSS.INPUTCONSTRAIN).get('checked')) {
            currentWidth = parseInt(this._form.one('.' + CSS.INPUTWIDTH).get('value'), 10);
            currentHeight = parseInt(this._form.one('.' + CSS.INPUTHEIGHT).get('value'), 10);
            this._form.one('.' + CSS.IMAGEPREVIEW).setAttribute('height', currentHeight);
            this._form.one('.' + CSS.IMAGEPREVIEW).setAttribute('width', currentWidth);
            return;
        }
        currentHeight = parseInt(this._form.one('.' + CSS.INPUTHEIGHT).get('value'), 10);
        newWidth = Math.round((currentHeight / this._imageRawHeight) * this._imageRawWidth);

        this._form.one('.' + CSS.INPUTWIDTH).set('value', newWidth);
        this._form.one('.' + CSS.IMAGEPREVIEW).setAttribute('width', newWidth);
        this._form.one('.' + CSS.IMAGEPREVIEW).setAttribute('height', currentHeight);
    },

    /**
     * Update the dialogue after an image was selected in the File Picker.
     *
     * @method _filepickerCallback
     * @param {object} params The parameters provided by the filepicker
     * containing information about the image.
     * @private
     */
    _filepickerCallback: function(params) {
        if (params.url !== '') {
            var input = this._form.one('.' + CSS.INPUTURL);
            input.set('value', params.url);

            // Auto set the width and height.
            self._form.one('.' + CSS.INPUTWIDTH).set('value', '');
            self._form.one('.' + CSS.INPUTHEIGHT).set('value', '');

            // Load the preview image.
            this._loadPreviewImage(params.url);
        }
    },

    /**
     * Applies properties of an existing image to the image dialogue for editing.
     *
     * @method _applyImageProperties
     * @param {Node} form
     * @private
     */
    _applyImageProperties: function(form) {
        var properties = this._getSelectedImageProperties(),
            img = form.one('.' + CSS.IMAGEPREVIEW);

        if (properties === false) {
            img.setStyle('display', 'none');
            return;
        }

        if (properties.align) {
            form.one('.' + CSS.INPUTALIGNMENT).set('value', properties.align);
        }
        if (properties.display) {
            img.setStyle('display', properties.display);
        }
        if (properties.width) {
            form.one('.' + CSS.INPUTWIDTH).set('value', properties.width);
        }
        if (properties.height) {
            form.one('.' + CSS.INPUTHEIGHT).set('value', properties.height);
        }
        if (properties.alt) {
            form.one('.' + CSS.INPUTALT).set('value', properties.alt);
        }
        if (properties.src) {
            form.one('.' + CSS.INPUTURL).set('value', properties.src);
            this._loadPreviewImage(properties.src);
        }
        if (properties.presentation) {
            form.one('.' + CSS.IMAGEPRESENTATION).set('checked', 'checked');
        }
    },

    /**
     * Gets the properties of the currently selected image.
     *
     * The first image only if multiple images are selected.
     *
     * @method _getSelectedImageProperties
     * @return {object}
     * @private
     */
    _getSelectedImageProperties: function() {
        var properties = {
                src: null,
                alt :null,
                width: null,
                height: null,
                align: null,
                display: 'inline',
                presentation: false
            },

            // Get the current selection.
            images = this.get('host').getSelectedNodes(),
            i, width, height, style, css;

        if (images) {
            images = images.filter('img');
        }

        if (images && images.size()) {
            image = images.item(0);
            this._selectedImage = image;

            style = image.getAttribute('style');
            width = parseInt(image.getAttribute('width'), 10);
            height = parseInt(image.getAttribute('height'), 10);

            if (width > 0) {
                properties.width = width;
            }
            if (height > 0) {
                properties.height = height;
            }
            for (i in ALIGNMENTS) {
                css = ALIGNMENTS[i].value + ':' + ALIGNMENTS[i].name + ';';
                if (style.replace(' ', '').indexOf(css) !== -1) {
                    properties.align = css;
                    break;
                }
            }
            properties.src = image.getAttribute('src');
            properties.alt = image.getAttribute('alt') || '';
            properties.presentation = (image.get('role') === 'presentation');
            return properties;
        }

        // No image selected - clean up.
        this._selectedImage = null;
        return false;
    },

    /**
     * Update the form when the URL was changed. This includes updating the
     * height, width, and image preview.
     *
     * @method _urlChanged
     * @private
     */
    _urlChanged: function() {
        var input = this._form.one('.' + CSS.INPUTURL);

        if (input.get('value') !== '') {
            // Load the preview image.
            this._loadPreviewImage(input.get('value'));
        }
    },

    /**
     * Update the image in the contenteditable.
     *
     * @method _setImage
     * @param {EventFacade} e
     * @private
     */
    _setImage: function(e) {
        var form = this._form,
            url = form.one('.' + CSS.INPUTURL).get('value'),
            alt = form.one('.' + CSS.INPUTALT).get('value'),
            width = form.one('.' + CSS.INPUTWIDTH).get('value'),
            height = form.one('.' + CSS.INPUTHEIGHT).get('value'),
            alignment = form.one('.' + CSS.INPUTALIGNMENT).get('value'),
            presentation = form.one('.' + CSS.IMAGEPRESENTATION).get('checked'),
            imagehtml,
            host = this.get('host');

        e.preventDefault();

        if (alt === '' && !presentation) {
            form.one('.' + CSS.IMAGEALTWARNING).setStyle('display', 'block');
            form.one('.' + CSS.INPUTALT).setAttribute('aria-invalid', true);
            form.one('.' + CSS.IMAGEPRESENTATION).setAttribute('aria-invalid', true);
            return;
        } else {
            form.one('.' + CSS.IMAGEALTWARNING).setStyle('display', 'none');
            form.one('.' + CSS.INPUTALT).setAttribute('aria-invalid', false);
            form.one('.' + CSS.IMAGEPRESENTATION).setAttribute('aria-invalid', false);
        }

        this.getDialogue({
            focusAfterHide: null
        }).hide();

        // Focus on the editor in preparation for inserting the image.
        host.focus();
        if (url !== '') {
            if (this._selectedImage) {
                host.setSelection(host.getSelectionFromNode(this._selectedImage));
            } else {
                host.setSelection(this._currentSelection);
            }
            template = Y.Handlebars.compile(IMAGETEMPLATE);
            imagehtml = template({
                url: url,
                alt: alt,
                width: width,
                height: height,
                presentation: presentation,
                alignment: alignment
            });

            this.get('host').insertContentAtFocusPoint(imagehtml);

            this.markUpdated();
        }
    }
});
