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
 * @package    atto_media
 * @copyright  2013 Damyon Wiese  <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_media-button
 */

/**
 * Atto media selection tool.
 *
 * @namespace M.atto_media
 * @class Button
 * @extends M.editor_atto.EditorPlugin
 */

var COMPONENTNAME = 'atto_media',
    MEDIA_TYPES = {LINK: 'LINK', VIDEO: 'VIDEO', AUDIO: 'AUDIO'},
    TRACK_KINDS = {
        SUBTITLES: 'SUBTITLES',
        CAPTIONS: 'CAPTIONS',
        DESCRIPTIONS: 'DESCRIPTIONS',
        CHAPTERS: 'CHAPTERS',
        METADATA: 'METADATA'
    },
    CSS = {
        SOURCE: 'atto_media_source',
        TRACK: 'atto_media_track',
        MEDIA_SOURCE: 'atto_media_media_source',
        POSTER_SOURCE: 'atto_media_poster_source',
        TRACK_SOURCE: 'atto_media_track_source',
        DISPLAY_OPTIONS: 'atto_media_display_options',
        NAME_INPUT: 'atto_media_name_entry',
        URL_INPUT: 'atto_media_url_entry',
        POSTER_SIZE: 'atto_media_poster_size',
        LINK_SIZE: 'atto_media_link_size',
        WIDTH_INPUT: 'atto_media_width_entry',
        HEIGHT_INPUT: 'atto_media_height_entry',
        TRACK_KIND_INPUT: 'atto_media_track_kind_entry',
        TRACK_LABEL_INPUT: 'atto_media_track_label_entry',
        TRACK_LANG_INPUT: 'atto_media_track_lang_entry',
        TRACK_DEFAULT_SELECT: 'atto_media_track_default',
        MEDIA_CONTROLS_TOGGLE: 'atto_media_controls',
        MEDIA_AUTOPLAY_TOGGLE: 'atto_media_autoplay',
        MEDIA_MUTE_TOGGLE: 'atto_media_mute',
        MEDIA_LOOP_TOGGLE: 'atto_media_loop',
        ADVANCED_SETTINGS: 'atto_media_advancedsettings',
        LINK: MEDIA_TYPES.LINK.toLowerCase(),
        VIDEO: MEDIA_TYPES.VIDEO.toLowerCase(),
        AUDIO: MEDIA_TYPES.AUDIO.toLowerCase(),
        TRACK_SUBTITLES: TRACK_KINDS.SUBTITLES.toLowerCase(),
        TRACK_CAPTIONS: TRACK_KINDS.CAPTIONS.toLowerCase(),
        TRACK_DESCRIPTIONS: TRACK_KINDS.DESCRIPTIONS.toLowerCase(),
        TRACK_CHAPTERS: TRACK_KINDS.CHAPTERS.toLowerCase(),
        TRACK_METADATA: TRACK_KINDS.METADATA.toLowerCase()
    },
    SELECTORS = {
        SOURCE: '.' + CSS.SOURCE,
        TRACK: '.' + CSS.TRACK,
        MEDIA_SOURCE: '.' + CSS.MEDIA_SOURCE,
        POSTER_SOURCE: '.' + CSS.POSTER_SOURCE,
        TRACK_SOURCE: '.' + CSS.TRACK_SOURCE,
        DISPLAY_OPTIONS: '.' + CSS.DISPLAY_OPTIONS,
        NAME_INPUT: '.' + CSS.NAME_INPUT,
        URL_INPUT: '.' + CSS.URL_INPUT,
        POSTER_SIZE: '.' + CSS.POSTER_SIZE,
        LINK_SIZE: '.' + CSS.LINK_SIZE,
        WIDTH_INPUT: '.' + CSS.WIDTH_INPUT,
        HEIGHT_INPUT: '.' + CSS.HEIGHT_INPUT,
        TRACK_KIND_INPUT: '.' + CSS.TRACK_KIND_INPUT,
        TRACK_LABEL_INPUT: '.' + CSS.TRACK_LABEL_INPUT,
        TRACK_LANG_INPUT: '.' + CSS.TRACK_LANG_INPUT,
        TRACK_DEFAULT_SELECT: '.' + CSS.TRACK_DEFAULT_SELECT,
        MEDIA_CONTROLS_TOGGLE: '.' + CSS.MEDIA_CONTROLS_TOGGLE,
        MEDIA_AUTOPLAY_TOGGLE: '.' + CSS.MEDIA_AUTOPLAY_TOGGLE,
        MEDIA_MUTE_TOGGLE: '.' + CSS.MEDIA_MUTE_TOGGLE,
        MEDIA_LOOP_TOGGLE: '.' + CSS.MEDIA_LOOP_TOGGLE,
        ADVANCED_SETTINGS: '.' + CSS.ADVANCED_SETTINGS,
        LINK_TAB: 'li.' + CSS.LINK,
        LINK_TAB_PANE: '#' + CSS.LINK,
        VIDEO_TAB: 'li.' + CSS.VIDEO,
        VIDEO_TAB_PANE: '#' + CSS.VIDEO,
        AUDIO_TAB: 'li.' + CSS.AUDIO,
        AUDIO_TAB_PANE: '#' + CSS.AUDIO,
        TRACK_SUBTITLES_TAB: 'li.' + CSS.TRACK_SUBTITLES,
        TRACK_SUBTITLES_PANE: '#' + CSS.TRACK_SUBTITLES,
        TRACK_CAPTIONS_TAB: 'li.' + CSS.TRACK_CAPTIONS,
        TRACK_CAPTIONS_PANE: '#' + CSS.TRACK_CAPTIONS,
        TRACK_DESCRIPTIONS_TAB: 'li.' + CSS.TRACK_DESCRIPTIONS,
        TRACK_DESCRIPTIONS_PANE: '#' + CSS.TRACK_DESCRIPTIONS,
        TRACK_CHAPTERS_TAB: 'li.' + CSS.TRACK_CHAPTERS,
        TRACK_CHAPTERS_PANE: '#' + CSS.TRACK_CHAPTERS,
        TRACK_METADATA_TAB: 'li.' + CSS.TRACK_METADATA,
        TRACK_METADATA_PANE: '#' + CSS.TRACK_METADATA
    },
    TEMPLATES = {
        ROOT: '' +
            '<form class="mform atto_form atto_media" id="atto_media_form">' +
                '<ul class="root nav nav-tabs" role="tablist">' +
                    '<li class="link nav-item">' +
                        '<a class="nav-link active" href="#link" role="tab" data-toggle="tab">' +
                            '{{get_string "link" component}}' +
                        '</a>' +
                    '</li>' +
                    '<li class="video nav-item">' +
                        '<a class="nav-link" href="#video" role="tab" data-toggle="tab">' +
                            '{{get_string "video" component}}' +
                        '</a>' +
                    '</li>' +
                    '<li class="audio nav-item">' +
                        '<a class="nav-link" href="#audio" role="tab" data-toggle="tab">' +
                            '{{get_string "audio" component}}' +
                        '</a>' +
                    '</li>' +
                '</ul>' +
                '<div class="root tab-content">' +
                    '<div class="tab-pane active" id="{{CSS.LINK}}">{{> tab_panes.link}}</div>' +
                    '<div class="tab-pane" id="{{CSS.VIDEO}}">{{> tab_panes.video}}</div>' +
                    '<div class="tab-pane" id="{{CSS.AUDIO}}">{{> tab_panes.audio}}</div>' +
                '</div>' +
                '<div class="mdl-align">' +
                    '<br/>' +
                    '<button class="submit" type="submit">{{get_string "createmedia" component}}</button>' +
                '</div>' +
            '</form>',
        TAB_PANES: {
            LINK: '' +
                '{{renderPartial "form_components.source" context=this id="linksource"}}' +
                '<label>' +
                    'Enter name' +
                    '<input class="fullwidth {{CSS.NAME_INPUT}}" type="text" id="{{elementid}}_atto_media_link_nameentry"' +
                        'size="32" required="true"/>' +
                '</label>',
            VIDEO: '' +
                '{{renderPartial "form_components.source" context=this id=CSS.MEDIA_SOURCE entersourcelabel="videosourcelabel"' +
                    ' addcomponentlabel="addvideosource" multisource="true"}}' +
                '<fieldset class="collapsible collapsed" id="video-display-options">' +
                    '<input name="mform_isexpanded_video-display-options" type="hidden">' +
                    '<legend class="ftoggler">{{get_string "displayoptions" component}}</legend>' +
                    '<div class="fcontainer">' +
                        '{{> form_components.display_options}}' +
                    '</div>' +
                '</fieldset>' +
                '<fieldset class="collapsible collapsed" id="video-advanced-settings">' +
                    '<input name="mform_isexpanded_video-advanced-settings" type="hidden">' +
                    '<legend class="ftoggler">{{get_string "advancedsettings" component}}</legend>' +
                    '<div class="fcontainer">' +
                        '{{> form_components.advanced_settings}}' +
                    '</div>' +
                '</fieldset>' +
                '<fieldset class="collapsible collapsed" id="video-tracks">' +
                    '<input name="mform_isexpanded_video-tracks" type="hidden">' +
                    '<legend class="ftoggler">{{get_string "tracks" component}}</legend>' +
                    '<div class="fcontainer">' +
                        '{{renderPartial "form_components.track_tabs" context=this id=CSS.VIDEO}}' +
                    '</div>' +
                '</fieldset>',
            AUDIO: '' +
                '{{renderPartial "form_components.source" context=this id=CSS.MEDIA_SOURCE entersourcelabel="audiosourcelabel"' +
                    ' addcomponentlabel="addaudiosource" multisource="true"}}' +
                '<fieldset class="collapsible collapsed" id="audio-advanced-settings">' +
                    '<input name="mform_isexpanded_audio-advanced-settings" type="hidden">' +
                    '<legend class="ftoggler">{{get_string "advancedsettings" component}}</legend>' +
                    '<div class="fcontainer">' +
                        '{{> form_components.advanced_settings}}' +
                    '</div>' +
                '</fieldset>' +
                '<fieldset class="collapsible collapsed" id="audio-tracks">' +
                    '<input name="mform_isexpanded_audio-tracks" type="hidden">' +
                    '<legend class="ftoggler">{{get_string "tracks" component}}</legend>' +
                    '<div class="fcontainer">' +
                        '{{renderPartial "form_components.track_tabs" context=this id=CSS.AUDIO}}' +
                    '</div>' +
                '</fieldset>'
        },
        FORM_COMPONENTS: {
            SOURCE: '' +
                '<div class="{{CSS.SOURCE}} {{id}}">' +
                    '<label {{#fullwidth}}class="fullwidth"{{/fullwidth}}>' +
                        '{{#entersourcelabel}}{{get_string ../entersourcelabel ../component}}{{/entersourcelabel}}' +
                        '{{^entersourcelabel}}{{get_string "entersource" ../component}}{{/entersourcelabel}}</a>' +
                        '{{^fullwidth}}<br/>{{/fullwidth}}' +
                        '<input class="{{CSS.URL_INPUT}}{{#fullwidth}} fullwidth{{/fullwidth}}" type="url" size="32"/>' +
                    '</label>' +
                    '{{#fullwidth}}<br/>{{/fullwidth}}' +
                    '<button class="openmediabrowser" type="button">{{get_string "browserepositories" component}}</button>' +
                    '{{#multisource}}' +
                        '{{renderPartial "form_components.add_component" context=../this label=../addcomponentlabel}}' +
                    '{{/multisource}}' +
                '</div>',
            ADD_COMPONENT: '' +
                '<div class="text-right">' +
                    '<a href="#" class="addcomponent">' +
                        '{{#label}}{{get_string ../label ../component}}{{/label}}' +
                        '{{^label}}{{get_string "add" ../component}}{{/label}}' +
                    '</a>' +
                '</div>',
            REMOVE_COMPONENT: '' +
                '<div class="text-right">' +
                    '<a href="#" class="removecomponent">' +
                        '{{#label}}{{get_string ../label ../component}}{{/label}}' +
                        '{{^label}}{{get_string "remove" ../component}}{{/label}}' +
                    '</a>' +
                '</div>',
            DISPLAY_OPTIONS: '' +
                '<div class="{{CSS.DISPLAY_OPTIONS}}">' +
                    '<label>' +
                        '{{get_string "size" component}}' +
                        '<div class={{CSS.POSTER_SIZE}}>' +
                            '<label>' +
                                '<span class="accesshide">{{get_string "posterwidth" component}}</span>' +
                                '<input type="text" class="{{CSS.WIDTH_INPUT}} input-mini" size="4"/>' +
                            '</label>' +
                            ' x ' +
                            '<label>' +
                                '<span class="accesshide">{{get_string "posterheight" component}}</span>' +
                                '<input type="text" class="{{CSS.HEIGHT_INPUT}} input-mini" size="4"/>' +
                            '</label>' +
                        '</div>' +
                    '</label>' +
                    '<div class="clearfix"></div>' +
                    '{{renderPartial "form_components.source" context=this id=CSS.POSTER_SOURCE entersourcelabel="poster"}}' +
                '<div>',
            ADVANCED_SETTINGS: '' +
                '<div class="{{CSS.ADVANCED_SETTINGS}}">' +
                    '<label>' +
                        '<input type="checkbox" class="{{CSS.MEDIA_CONTROLS_TOGGLE}}"/>' +
                        '{{get_string "controls" component}}' +
                    '</label>' +
                    '<label>' +
                        '<input type="checkbox" class="{{CSS.MEDIA_AUTOPLAY_TOGGLE}}"/>' +
                        '{{get_string "autoplay" component}}' +
                    '</label>' +
                    '<label>' +
                        '<input type="checkbox" class="{{CSS.MEDIA_MUTE_TOGGLE}}"/>' +
                        '{{get_string "mute" component}}' +
                    '</label>' +
                    '<label>' +
                        '<input type="checkbox" class="{{CSS.MEDIA_LOOP_TOGGLE}}"/>' +
                        '{{get_string "loop" component}}' +
                    '</label>' +
                '</div>',
            TRACK_TABS: '' +
                '<ul class="nav nav-tabs">' +
                    '<li class="nav-item {{CSS.TRACK_SUBTITLES}}">' +
                        '<a class="nav-link active" href="#{{id}}_{{CSS.TRACK_SUBTITLES}}" role="tab" data-toggle="tab">' +
                            '{{get_string "subtitles" component}}' +
                        '</a>' +
                    '</li>' +
                    '<li class="nav-item {{CSS.TRACK_CAPTIONS}}">' +
                        '<a class="nav-link" href="#{{id}}_{{CSS.TRACK_CAPTIONS}}" role="tab" data-toggle="tab">' +
                            '{{get_string "captions" component}}' +
                        '</a>' +
                    '</li>' +
                    '<li class="nav-item {{CSS.TRACK_DESCRIPTIONS}}">' +
                        '<a class="nav-link" href="#{{id}}_{{CSS.TRACK_DESCRIPTIONS}}" role="tab" data-toggle="tab">' +
                            '{{get_string "descriptions" component}}' +
                        '</a>' +
                    '</li>' +
                    '<li class="nav-item {{CSS.TRACK_CHAPTERS}}">' +
                        '<a class="nav-link" href="#{{id}}_{{CSS.TRACK_CHAPTERS}}" role="tab" data-toggle="tab">' +
                            '{{get_string "chapters" component}}' +
                        '</a>' +
                    '</li>' +
                    '<li class="nav-item {{CSS.TRACK_METADATA}}">' +
                        '<a class="nav-link" href="#{{id}}_{{CSS.TRACK_METADATA}}" role="tab" data-toggle="tab">' +
                            '{{get_string "metadata" component}}' +
                        '</a>' +
                    '</li>' +
                '</ul>' +
                '<div class="tab-content">' +
                    '<div class="tab-pane active {{CSS.TRACK_SUBTITLES}}" id="{{id}}_{{CSS.TRACK_SUBTITLES}}">' +
                        '{{renderPartial "form_components.track" context=this sourcelabel="subtitlessourcelabel"' +
                            ' addcomponentlabel="addsubtitlestrack"}}' +
                    '</div>' +
                    '<div class="tab-pane {{CSS.TRACK_CAPTIONS}}" id="{{id}}_{{CSS.TRACK_CAPTIONS}}">' +
                        '{{renderPartial "form_components.track" context=this sourcelabel="captionssourcelabel"' +
                            ' addcomponentlabel="addcaptionstrack"}}' +
                    '</div>' +
                    '<div class="tab-pane {{CSS.TRACK_DESCRIPTIONS}}" id="{{id}}_{{CSS.TRACK_DESCRIPTIONS}}">' +
                        '{{renderPartial "form_components.track" context=this sourcelabel="descriptionssourcelabel"' +
                            ' addcomponentlabel="adddescriptionstrack"}}' +
                    '</div>' +
                    '<div class="tab-pane {{CSS.TRACK_CHAPTERS}}" id="{{id}}_{{CSS.TRACK_CHAPTERS}}">' +
                    '{{renderPartial "form_components.track" context=this sourcelabel="chapterssourcelabel"' +
                        ' addcomponentlabel="addchapterstrack"}}' +
                    '</div>' +
                    '<div class="tab-pane {{CSS.TRACK_METADATA}}" id="{{id}}_{{CSS.TRACK_METADATA}}">' +
                    '{{renderPartial "form_components.track" context=this sourcelabel="metadatasourcelabel"' +
                        ' addcomponentlabel="addmetadatatrack"}}' +
                    '</div>' +
                '</div>',
            TRACK: '' +
                '<div class="{{CSS.TRACK}}">' +
                    '{{renderPartial "form_components.source" context=this id=CSS.TRACK_SOURCE entersourcelabel=sourcelabel}}' +
                    '<label>' +
                        '<span>{{get_string "srclang" component}}</span>' +
                        '<select class="{{CSS.TRACK_LANG_INPUT}}">' +
                            '<optgroup label="{{get_string "languagesinstalled" component}}">' +
                                '{{#langsinstalled}}<option value="{{code}}">{{lang}}</option>{{/langsinstalled}}' +
                            '</optgroup>' +
                            '<optgroup label="{{get_string "languagesavailable" component}} ">' +
                                '{{#langsavailable}}<option value="{{code}}">{{lang}}</option>{{/langsavailable}}' +
                            '</optgroup>' +
                        '</select>' +
                    '</label>' +
                    '<label>' +
                        '<span>{{get_string "label" component}}</span>' +
                        '<input class="{{CSS.TRACK_LABEL_INPUT}}" type="text"/>' +
                    '</label>' +
                    '<label>' +
                        '<input type="checkbox" class="{{CSS.TRACK_DEFAULT_SELECT}}"/>' +
                        '{{get_string "default" component}}' +
                    '</label>' +
                    '{{renderPartial "form_components.add_component" context=this label=addcomponentlabel}}' +
                '</div>'
        },
        HTML_MEDIA: {
            VIDEO: '' +
                '&nbsp;<video ' +
                    '{{#width}}width="{{../width}}" {{/width}}' +
                    '{{#height}}height="{{../height}}" {{/height}}' +
                    '{{#poster}}poster="{{../poster}}" {{/poster}}' +
                    '{{#showControls}}controls="true" {{/showControls}}' +
                    '{{#loop}}loop="true" {{/loop}}' +
                    '{{#muted}}muted="true" {{/muted}}' +
                    '{{#autoplay}}autoplay="true" {{/autoplay}}' +
                '>' +
                    '{{#sources}}<source src="{{source}}">{{/sources}}' +
                    '{{#tracks}}' +
                        '<track src="{{track}}" kind="{{kind}}" srclang="{{srclang}}" label="{{label}}"' +
                            ' {{#defaultTrack}}default="true"{{/defaultTrack}}>' +
                    '{{/tracks}}' +
                    '{{#description}}{{description}}{{/description}}' +
                '</video>&nbsp',
            AUDIO: '' +
                '&nbsp;<audio ' +
                    '{{#showControls}}controls="true" {{/showControls}}' +
                    '{{#loop}}loop="true" {{/loop}}' +
                    '{{#muted}}muted="true" {{/muted}}' +
                    '{{#autoplay}}autoplay="true" {{/autoplay}}' +
                '>' +
                    '{{#sources}}<source src="{{source}}">{{/sources}}' +
                    '{{#tracks}}' +
                        '<track src="{{track}}" kind="{{kind}}" srclang="{{srclang}}" label="{{label}}"' +
                            ' {{#defaultTrack}}default="true"{{/defaultTrack}}>' +
                    '{{/tracks}}' +
                    '{{#description}}{{description}}{{/description}}' +
                '</audio>&nbsp',
            LINK: '' +
                '<a href="{{url}}" ' +
                    '{{#width}}data-width="{{../width}}" {{/width}}' +
                    '{{#height}}data-height="{{../height}}"{{/height}}' +
                '>{{#name}}{{../name}}{{/name}}{{^name}}{{../url}}{{/name}}</a>'
         }
    };

Y.namespace('M.atto_media').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {

    initializer: function() {
        if (this.get('host').canShowFilepicker('media')) {
            this.editor.delegate('dblclick', this._displayDialogue, 'video', this);
            this.editor.delegate('click', this._handleClick, 'video', this);

            (function smashPartials(chain, obj) {
                Y.each(obj, function(v, i) {
                    chain.push(i);
                    if (typeof v !== "object") {
                        Y.Handlebars.registerPartial(chain.join('.').toLowerCase(), v);
                    } else {
                        smashPartials(chain, v);
                    }
                    chain.pop();
                });
            })([], TEMPLATES);

            Y.Handlebars.registerHelper('renderPartial', function(partialName, options) {
                if (!partialName) {
                    return '';
                }
                var partial = Y.Handlebars.partials[partialName];
                var context = Object.keys(options.hash).reduce(function(carry, key) {
                    if (key === 'context') {
                        return carry;
                    }
                    carry[key] = options.hash[key];
                    return carry;
                }, options.hash.context ? Object.create(options.hash.context) : {});

                if (!partial) {
                    return '';
                }
                return new Y.Handlebars.SafeString(Y.Handlebars.compile(partial)(context));
            });

            this.addButton({
                icon: 'e/insert_edit_video',
                callback: this._displayDialogue,
                tags: 'video, audio',
                tagMatchRequiresAll: false
            });
        }
    },

    /**
     * Gets the root context for all templates, with extra supplied context.
     *
     * @method _getContext
     * @param  {Object} extra The extra context to add
     * @return {Object}
     * @private
     */
    _getContext: function(extra) {
        var context = Object.create({
            elementid: this.get('host').get('elementid'),
            component: COMPONENTNAME,
            langsinstalled: this.get('langs').installed,
            langsavailable: this.get('langs').available,
            CSS: CSS
        });

        if (!extra) {
            return context;
        }

        Object.keys(extra).forEach(function(v) {
            context[v] = extra[v];
        });

        return context;
    },

    /**
     * Handles a click on a media element.
     *
     * @method _handleClick
     * @param  {EventFacade} e
     * @private
     */
    _handleClick: function(e) {
        var medium = e.target;

        var selection = this.get('host').getSelectionFromNode(medium);
        if (this.get('host').getSelection() !== selection) {
            this.get('host').setSelection(selection);
        }
    },

    /**
     * Display the media editing tool.
     *
     * @method _displayDialogue
     * @private
     */
    _displayDialogue: function() {
        if (this.get('host').getSelection() === false) {
            return;
        }

        var dialogue = this.getDialogue({
            headerContent: M.util.get_string('createmedia', COMPONENTNAME),
            focusAfterHide: true,
            width: 660,
            focusOnShowSelector: SELECTORS.URL_INPUT
        });

        // Set the dialogue content, and then show the dialogue.
        dialogue.set('bodyContent', this._getDialogueContent(this.get('host').getSelection())).show();
        // Nasty hack to move the dialogue up so that behat can see things.
        var modal = Y.one('#atto_media_form').ancestor('.moodle-dialogue');
        modal.setStyle('top', modal.getXY()[1] - 200 + 'px');
        M.form.shortforms({formid: 'atto_media_form'});
    },

    /**
     * Returns the dialogue content for the tool.
     *
     * @method _getDialogueContent
     * @param  {WrappedRange[]} selection Current editor selection
     * @return {Y.Node}
     * @private
     */
    _getDialogueContent: function(selection) {
        var content = Y.Node.create(
            Y.Handlebars.compile(TEMPLATES.ROOT)(this._getContext())
        );

        var media = this.get('host').getSelectedNodes().get(0).filter(function(el) {
            return el.test('video') || el.test('audio');
        });

        var attachEvents = function(content) {
            return this._attachEvents(content, selection);
        }.bind(this);
        var applyMediumProperties = function(content) {
            return this._applyMediumProperties(content, media[0] ? this._getMediumProperties(media[0]) : false);
        }.bind(this);
        var applyDefaultValues = this._applyDefaultValues.bind(this);

        return attachEvents(applyDefaultValues(applyMediumProperties(content)));
    },

    /**
     * Attaches required events to the content node.
     *
     * @method _attachEvents
     * @param  {Y.Node}         content The content to which events will be attached
     * @param  {WrappedRange[]} selection Current editor selection
     * @return {Y.Node}
     * @private
     */
    _attachEvents: function(content, selection) {
        content.all(SELECTORS.MEDIA_SOURCE + ' .addcomponent').each(function(elem) {
            elem.on('click', this._getAddMediaSourceCallback(elem), this);
        }, this);

        content.all(SELECTORS.TRACK + ' .addcomponent').each(function(elem) {
            elem.on('click', this._getAddTrackCallback(elem), this);
        }, this);

        // This is another nasty hack. Basically we are using BS4 markup for the tabs
        // but it isn't completely backwards compatible with BS2. The main problem is
        // that the "active" class goes on a different node. So the idea is to put it
        // the node for BS4, and then use CSS to make it look right in BS2. However,
        // once another tab is clicked, everything sorts itself out, more or less. Except
        // that the original "active" tab hasn't had the BS4 "active" class removed
        // (so the styles will still apply to it). So we need to remove the "active"
        // class on the BS4 node so that BS2 is happy.
        //
        // This doesn't upset BS4 since it removes this class anyway when clicking on
        // another tab.
        content.all('.nav-item').on('click', function(elem) {
            elem.currentTarget.siblings('.nav-item').each(function(node) {
                var aNode = node.one('.active');
                if (aNode) {
                    aNode.removeClass('active');
                }
            });
        });

        content.one('.submit').on('click', function(e) {
            e.preventDefault();
            var mediaHTML = this._getMediaHTML(e.currentTarget.ancestor('.atto_form')),
                host = this.get('host');
            this.getDialogue({
                focusAfterHide: null
            }).hide();
            if (mediaHTML) {
                host.setSelection(selection);
                host.insertContentAtFocusPoint(mediaHTML);
                this.markUpdated();
            }
        }, this);

        content.all('.openmediabrowser').each(this._attachClickEventForFilepicker, this);
        content.all(SELECTORS.TRACK_DEFAULT_SELECT).each(
            function(sel) {
                this._attachClickEventForTrackDefaultToggle(sel);
            }, this);

        return content;
    },

    /**
     * Applies default values to the content node.
     *
     * @method _applyDefaultValues
     * @param  {Y.Node} content The content to which defaults will be applied
     * @return {Y.Node}
     * @private
     */
    _applyDefaultValues: function(content) {
        // Doing this in the template didn't work for some reason.
        content.all(SELECTORS.MEDIA_CONTROLS_TOGGLE).set('checked', 'checked');
        return content;
    },

    /**
     * Applies medium properties to the content node.
     *
     * @method _applyMediumProperties
     * @param  {Y.Node} content The content to apply the properties to
     * @param  {object} properties The medium properties to apply
     * @return {Y.Node}
     * @private
     */
    _applyMediumProperties: function(content, properties) {
        var trackDefaults = {src: '', srclang: '', label: '', defaultTrack: false};
        if (!properties) {
            return content;
        }

        // Previously .bind and passing of `this` all over the place was used to get the closures
        // to work properly. It just seems neater to assign `this` to a new variable I can use in the
        // closures. The gears are toast.
        var self = this;

        var applyTrackProperties = function(track, properties) {
            track.one(SELECTORS.TRACK_SOURCE + ' ' + SELECTORS.URL_INPUT).set('value', properties.src);
            track.one(SELECTORS.TRACK_LANG_INPUT).set('value', properties.srclang);
            track.one(SELECTORS.TRACK_LABEL_INPUT).set('value', properties.label);
            track.one(SELECTORS.TRACK_DEFAULT_SELECT).set('checked', properties.defaultTrack);
        };

        var setActiveTab = function(tabPane) {
            var tab = tabPane.ancestor('.tab-content').siblings('.nav-tabs').pop().one('.' + tabPane.get('id'));

            tabPane.siblings('.active').removeClass('active');
            tabPane.addClass('active');
            tab.siblings('.active').removeClass('active');
            tab.addClass('active');
        };

        var populateSources = function(content) {
            content.one(SELECTORS.MEDIA_SOURCE + ' ' + SELECTORS.URL_INPUT).set('value', properties.sources[0]);
            properties.sources.slice(1).forEach(function(source) {
                self._getAddMediaSourceCallback(content.one(SELECTORS.MEDIA_SOURCE + ' .addcomponent'),
                                           function(newComponent) {
                                               newComponent.one(SELECTORS.URL_INPUT).set('value', source);
                                           }
                                          ).call(self);
            });
        };

        var populateTracks = function(content) {
            Object.keys(properties.tracks).forEach(function(key) {
                var trackData = properties.tracks[key].length ? properties.tracks[key] : [trackDefaults];
                applyTrackProperties(content.one('.' + key + ' ' + SELECTORS.TRACK), trackData[0]);
                trackData.slice(1).forEach(function(track) {
                    self._getAddTrackCallback(content.one(SELECTORS.TRACK + ' .addcomponent'),
                                              function(newComponent) {
                                                  applyTrackProperties(newComponent, track);
                                              }
                    ).call(self);
                });
            });
        };

        var populateValues = function(content) {
            var populate = function(selector, attribute, value) {
                if (content.one(selector)) {
                    content.one(selector).set(attribute, value);
                }
            };

            populate(SELECTORS.POSTER_SOURCE + ' ' + SELECTORS.URL_INPUT, 'value', properties.poster);
            populate(SELECTORS.WIDTH_INPUT, 'value', properties.width);
            populate(SELECTORS.HEIGHT_INPUT, 'value', properties.height);
            populate(SELECTORS.MEDIA_CONTROLS_TOGGLE, 'checked', properties.controls);
            populate(SELECTORS.MEDIA_AUTOPLAY_TOGGLE, 'checked', properties.autoplay);
            populate(SELECTORS.MEDIA_MUTE_TOGGLE, 'checked', properties.muted);
            populate(SELECTORS.MEDIA_LOOP_TOGGLE, 'checked', properties.loop);
        };

        var tab = content.one('.root.tab-content > .tab-pane#' + properties.type.toLowerCase());
        setActiveTab(tab);
        populateSources(tab);
        populateTracks(tab);
        populateValues(tab);

        // Remove left over events (_attachEvents will add the correct ones)
        content.all('.addcomponent').detach('click');

        return content;
    },

    /**
     * Extracts medium properties.
     *
     * @method _getMediumProperties
     * @param  {Y.Node} medium The medium node from which to extract
     * @return {Object}
     * @private
     */
    _getMediumProperties: function(medium) {
        var boolAttr = function(elem, attr) {
            return elem.getAttribute(attr) ? true : false;
        };
        return {
            type: medium.test('video') ? MEDIA_TYPES.VIDEO : MEDIA_TYPES.AUDIO,
            sources: medium.all('source').get(0).reduce(function(carry, source) {
                return carry.concat([source.getAttribute('src')]);
            }, []),
            poster: medium.getAttribute('poster'),
            width: medium.getAttribute('width'),
            height: medium.getAttribute('height'),
            autoplay: boolAttr(medium, 'autoplay'),
            loop: boolAttr(medium, 'loop'),
            muted: boolAttr(medium, 'muted'),
            controls: boolAttr(medium, 'controls'),
            tracks: medium.all('track').get(0).reduce(function(carry, track) {
                carry[track.getAttribute('kind')] = carry[track.getAttribute('kind')].concat([{
                    src: track.getAttribute('src'),
                    srclang: track.getAttribute('srclang'),
                    label: track.getAttribute('label'),
                    defaultTrack: boolAttr(track, 'default')
                }]);
                return carry;
            }, {subtitles: [], captions: [], descriptions: [], chapters: [], metadata: []})
        };
    },

    /**
     * Returns the callback for when an "Add source" button is pressed
     *
     * @method _getAddMediaSourceCallback
     * @param  {Y.Node}   element    The element which triggered the callback
     * @param  {Function} [callback] Function to be called when the new source element is added
     *     @param  {Y.Node}    callback.newComponent The compiled component
     * @return {Function} The function to be used as a callback by event handlers
     * @private
     */
    _getAddMediaSourceCallback: function(element, callback) {
        var mediumType = element.ancestor('.tab-pane').getAttribute('id');
        var context = this._getContext({
            multisource: true,
            id: CSS.MEDIA_SOURCE,
            entersourcelabel: mediumType + 'sourcelabel',
            addcomponentlabel: 'add' + mediumType + 'source'
        });
        return this._getAddComponentCallback(element, TEMPLATES.FORM_COMPONENTS.SOURCE, SELECTORS.MEDIA_SOURCE, context,
                                             function(newComponent) {
                                                 this._attachClickEventForFilepicker(newComponent.one('.openmediabrowser'));
                                                 if (callback) {
                                                     callback.call(this, newComponent);
                                                 }
                                             });
    },

    /**
     * Returns the callback for when an "Add track" button is pressed.
     *
     * @method _getAddTrackCallback
     * @param  {Y.Node}   element    The element which triggered the callback
     * @param  {Function} [callback] Function to be called when the new track element is added
     *     @param  {Y.Node}    callback.newComponent The compiled component
     * @return {Function} The function to be used as a callback by event handlers
     * @private
     */
    _getAddTrackCallback: function(element, callback) {
        var trackType = element.ancestor('.tab-pane')
                               .getAttribute('class')
                               .split(' ').filter(function(c) {
                                   return !!TRACK_KINDS[c.toUpperCase()];
                               })[0];
        var context = this._getContext({
            sourcelabel: trackType + 'sourcelabel',
            addcomponentlabel: 'add' + trackType + 'track'
        });
        return this._getAddComponentCallback(element, TEMPLATES.FORM_COMPONENTS.TRACK, SELECTORS.TRACK, context,
                                             function(newComponent) {
                                                 this._attachClickEventForFilepicker(newComponent.one('.openmediabrowser'));
                                                 this._attachClickEventForTrackDefaultToggle(
                                                     newComponent.one(SELECTORS.TRACK_DEFAULT_SELECT));
                                                 if (callback) {
                                                     callback.call(this, newComponent);
                                                 }
                                             });
    },

    /**
     * Returns the callback for adding an arbitrary form component.
     *
     * The callback returned from this function will compile and add the provided component in the supplied
     * 'ancestor' container. It will also add links to add/remove the relevant components, attaching the
     * necessary events.
     *
     * @method _getAddComponentCallback
     * @param  {Y.Node}   element    The element which triggered the callback
     * @param  {String}   component  The component to compile and add
     * @param  {String}   ancestor   A selector used to find an ancestor of 'component', to which
     *                               the compiled component will be appended
     * @param  {Object}   context    The context with which to render the component
     * @param  {Function} [callback] Function to be called when the new track element is added
     *     @param  {Y.Node}    callback.newComponent The compiled component
     * @return {Function} The function to be used as a callback by event handlers
     * @private
     */
    _getAddComponentCallback: function(element, component, ancestor, context, callback) {
        return function(e) {
            var currentComponent = element.ancestor(ancestor),
                newComponent = Y.Node.create(Y.Handlebars.compile(component)(context)),
                removeNodeContext = this._getContext(context);

            removeNodeContext.label = "remove";
            var removeNode = Y.Node.create(Y.Handlebars.compile(TEMPLATES.FORM_COMPONENTS.REMOVE_COMPONENT)(removeNodeContext));

            if (e) {
                e.preventDefault();
            }
            removeNode.on('click', function(e) {
                e.preventDefault();
                currentComponent.remove(true);
            });
            if (callback) {
                callback.call(this, newComponent);
            }
            newComponent.one('.addcomponent').on(
                'click', this._getAddComponentCallback(newComponent.one('.addcomponent'), component, ancestor, context,
                                                       callback), this);
            currentComponent.insert(newComponent, 'after');
            element.ancestor().insert(removeNode, 'after');
            element.ancestor().remove(true);
        };
    },

    /**
     * Attaches the click event for file picker buttons.
     *
     * @method _attachClickEventForFilepicker
     * @param {Y.Node} element The element to which the click event will be attached
     * @private
     */
    _attachClickEventForFilepicker: function(element) {
        element.on('click', function(e) {
            var fptype = (element.ancestor(SELECTORS.POSTER_SOURCE) && 'image') ||
                         (element.ancestor(SELECTORS.TRACK_SOURCE) && 'subtitle') ||
                         'media';
            e.preventDefault();
            this.get('host').showFilepicker(fptype, this._getFilepickerCallback(element, fptype), this);
        }, this);
    },

    /**
     * Attaches the click event for track default checkboxes.
     *
     * @param {Y.Node} element The element to which the click event will be attached
     * @private
     */
    _attachClickEventForTrackDefaultToggle: function(element) {
        element.on('click', function() {
            if (element.get('checked')) {
                var getKind = function(el) {
                    return el.ancestor('.tab-pane').get('id').split('_')[1];
                };
                element.ancestor('.root.tab-content').all(SELECTORS.TRACK_DEFAULT_SELECT).each(function(select) {
                    if (select !== element && getKind(element) === getKind(select)) {
                        select.set('checked', false);
                    }
                });
            }
        });
    },

    /**
     * Returns the callback for the file picker to call after a file has been selected.
     *
     * @method _getFilepickerCallback
     * @param  {Y.Node} element The element which triggered the callback
     * @param  {String} fptype  The file pickertype (as would be passed to `showFilePicker`)
     * @return {Function} The function to be used as a callback when the file picker returns the file
     * @private
     */
    _getFilepickerCallback: function(element, fptype) {
        return function(params) {
            if (params.url !== '') {
                var tabPane = element.ancestor('.tab-pane');
                element.ancestor(SELECTORS.SOURCE).one(SELECTORS.URL_INPUT).set('value', params.url);

                // Links (and only links) have a name field.
                if (tabPane.get('id') === CSS.LINK) {
                    tabPane.one(SELECTORS.NAME_INPUT).set('value', params.file);
                }

                if (fptype === 'subtitle') {
                    var subtitleLang = params.file.split('.vtt')[0].split('-').slice(-1)[0];
                    var langObj = this.get('langs').available.reduce(function(carry, lang) {
                        return lang.code === subtitleLang ? lang : carry;
                    }, false);
                    if (langObj) {
                        element.ancestor(SELECTORS.TRACK).one(SELECTORS.TRACK_LABEL_INPUT).set('value',
                                langObj.lang.substr(0, langObj.lang.lastIndexOf(' ')));
                        element.ancestor(SELECTORS.TRACK).one(SELECTORS.TRACK_LANG_INPUT).set('value', langObj.code);
                    }
                }
            }
        };
    },

    /**
     * Returns the HTML to be inserted to the text area.
     *
     * @method _getMediaHTML
     * @param  {Y.Node} form The form from which to extract data
     * @return {String} The compiled markup
     * @private
     */
    _getMediaHTML: function(form) {
        var mediaType = form.one('.root.tab-content > .tab-pane.active').get('id').toUpperCase(),
            tabContent = form.one(SELECTORS[mediaType + '_TAB_PANE']);

        return this['_getMediaHTML' + mediaType[0] + mediaType.substr(1).toLowerCase()](tabContent);
    },

    /**
     * Returns the HTML to be inserted to the text area for the link tab.
     *
     * @method _getMediaHTMLLink
     * @param  {Y.Node} tab The tab from which to extract data
     * @return {String} The compiled markup
     * @private
     */
    _getMediaHTMLLink: function(tab) {
        var context = {
            url: tab.one(SELECTORS.URL_INPUT).get('value'),
            name: tab.one(SELECTORS.NAME_INPUT).get('value') || false
        };

        return context.url ? Y.Handlebars.compile(TEMPLATES.HTML_MEDIA.LINK)(context) : '';
    },

    /**
     * Returns the HTML to be inserted to the text area for the video tab.
     *
     * @method _getMediaHTMLVideo
     * @param  {Y.Node} tab The tab from which to extract data
     * @return {String} The compiled markup
     * @private
     */
    _getMediaHTMLVideo: function(tab) {
        var context = this._getContextForMediaHTML(tab);
        context.width = tab.one(SELECTORS.WIDTH_INPUT).get('value') || false;
        context.height = tab.one(SELECTORS.HEIGHT_INPUT).get('value') || false;
        context.poster = tab.one(SELECTORS.POSTER_SOURCE + ' ' + SELECTORS.URL_INPUT).get('value') || false;

        return context.sources.filter(function(source) {
            return source.source;
        }).length ? Y.Handlebars.compile(TEMPLATES.HTML_MEDIA.VIDEO)(context) : '';
    },

    /**
     * Returns the HTML to be inserted to the text area for the audio tab.
     *
     * @method _getMediaHTMLAudio
     * @param  {Y.Node} tab The tab from which to extract data
     * @return {String} The compiled markup
     * @private
     */
    _getMediaHTMLAudio: function(tab) {
        var context = this._getContextForMediaHTML(tab);

        return context.sources.filter(function(source) {
            return source.source;
        }).length ? Y.Handlebars.compile(TEMPLATES.HTML_MEDIA.AUDIO)(context) : '';
    },

    /**
     * Returns the context with which to render a media template.
     *
     * @method _getContextForMediaHTML
     * @param  {Y.Node} tab The tab from which to extract data
     * @return {Object}
     * @private
     */
    _getContextForMediaHTML: function(tab) {
        var context = {
            sources: tab.all(SELECTORS.MEDIA_SOURCE + ' ' + SELECTORS.URL_INPUT).get(0).reduce(function(carry, source) {
                return source.get('value') ? carry.concat([{source: source.get('value')}]) : carry;
            }, []),
            tracks: tab.all(SELECTORS.TRACK).get(0).reduce(function(carry, track) {
                return track.one(SELECTORS.TRACK_SOURCE + ' ' + SELECTORS.URL_INPUT).get('value') ?
                    carry.concat([{
                        track: track.one(SELECTORS.TRACK_SOURCE + ' ' + SELECTORS.URL_INPUT).get('value'),
                        kind: track.ancestor('.tab-pane').get('id').split('_')[1],
                        label: track.one(SELECTORS.TRACK_LABEL_INPUT).get('value') ||
                               track.one(SELECTORS.TRACK_LANG_INPUT).get('value'),
                        srclang: track.one(SELECTORS.TRACK_LANG_INPUT).get('value'),
                        defaultTrack: track.one(SELECTORS.TRACK_DEFAULT_SELECT).get('checked') ? "true" : null
                    }]) : carry;
                }, []),
            showControls: tab.one(SELECTORS.MEDIA_CONTROLS_TOGGLE).get('checked'),
            autoplay: tab.one(SELECTORS.MEDIA_AUTOPLAY_TOGGLE).get('checked'),
            muted: tab.one(SELECTORS.MEDIA_MUTE_TOGGLE).get('checked'),
            loop: tab.one(SELECTORS.MEDIA_LOOP_TOGGLE).get('checked')
        };

        return context;
    }
}, {
    ATTRS: {
        langs: {}
    }
});
