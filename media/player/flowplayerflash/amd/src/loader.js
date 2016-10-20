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
 * Add Flowplayer
 *
 * @module     media_flowplayerflash/loader
 * @package    media_flowplayerflash
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Disable no-restriced-properties because M.str is expected here:
/* eslint-disable no-restricted-properties */
/* eslint-disable camelcase */
/* global flowplayer */

define(['core/yui'], function(Y) {

    /** List of flv players to be loaded */
    var video_players = [];
    /** List of mp3 players to be loaded */
    var audio_players = [];

    /**
     * Initialise all audio and video player, must be called from page footer.
     */
    var load_flowplayer = function() {
        if (video_players.length == 0 && audio_players.length == 0) {
            return;
        }
        if (typeof(flowplayer) !== 'undefined') {
            // Already loaded.
            return;
        }

        var loaded = false;

        var embed_function = function() {
            if (loaded || typeof(flowplayer) == 'undefined') {
                return;
            }
            loaded = true;

            var controls = {
                url: M.cfg.wwwroot + '/media/player/flowplayerflash/flowplayer/flowplayer.controls-3.2.16.swf.php',
                autoHide: true
            };
            /* TODO: add CSS color overrides for the flv flow player */

            for(var i=0; i<video_players.length; i++) {
                var video = video_players[i];
                if (video.width > 0 && video.height > 0) {
                    var src = {src: M.cfg.wwwroot + '/media/player/flowplayerflash/flowplayer/flowplayer-3.2.18.swf.php',
                        width: video.width, height: video.height};
                } else {
                    var src = M.cfg.wwwroot + '/media/player/flowplayerflash/flowplayer/flowplayer-3.2.18.swf.php';
                }
                flowplayer(video.id, src, {
                    plugins: {controls: controls},
                    clip: {
                        url: video.fileurl, autoPlay: false, autoBuffering: true, scaling: 'fit', mvideo: video,
                        onMetaData: function(clip) {
                            if (clip.mvideo.autosize && !clip.mvideo.resized) {
                                clip.mvideo.resized = true;
                                //alert("metadata!!! "+clip.width+' '+clip.height+' '+JSON.stringify(clip.metaData));
                                if (typeof(clip.metaData.width) == 'undefined' || typeof(clip.metaData.height) == 'undefined') {
                                    // bad luck, we have to guess - we may not get metadata at all
                                    var width = clip.width;
                                    var height = clip.height;
                                } else {
                                    var width = clip.metaData.width;
                                    var height = clip.metaData.height;
                                }
                                var minwidth = 300; // controls are messed up in smaller objects
                                if (width < minwidth) {
                                    height = (height * minwidth) / width;
                                    width = minwidth;
                                }

                                var object = this._api();
                                object.width = width;
                                object.height = height;
                            }
                        }
                    }
                });
            }
            if (audio_players.length == 0) {
                return;
            }
            var controls = {
                url: M.cfg.wwwroot + '/media/player/flowplayerflash/flowplayer/flowplayer.controls-3.2.16.swf.php',
                autoHide: false,
                fullscreen: false,
                next: false,
                previous: false,
                scrubber: true,
                play: true,
                pause: true,
                volume: true,
                mute: false,
                backgroundGradient: [0.5,0,0.3]
            };

            var rule;
            for (var j=0; j < document.styleSheets.length; j++) {

                // To avoid javascript security violation accessing cross domain stylesheets
                var allrules = false;
                try {
                    if (typeof (document.styleSheets[j].rules) != 'undefined') {
                        allrules = document.styleSheets[j].rules;
                    } else if (typeof (document.styleSheets[j].cssRules) != 'undefined') {
                        allrules = document.styleSheets[j].cssRules;
                    } else {
                        // why??
                        continue;
                    }
                } catch (e) {
                    continue;
                }

                // On cross domain style sheets Chrome V8 allows access to rules but returns null
                if (!allrules) {
                    continue;
                }

                for(var i=0; i<allrules.length; i++) {
                    rule = '';
                    if (/^\.mp3flowplayer_.*Color$/.test(allrules[i].selectorText)) {
                        if (typeof(allrules[i].cssText) != 'undefined') {
                            rule = allrules[i].cssText;
                        } else if (typeof(allrules[i].style.cssText) != 'undefined') {
                            rule = allrules[i].style.cssText;
                        }
                        if (rule != '' && /.*color\s*:\s*([^;]+).*/gi.test(rule)) {
                            rule = rule.replace(/.*color\s*:\s*([^;]+).*/gi, '$1');
                            var colprop = allrules[i].selectorText.replace(/^\.mp3flowplayer_/, '');
                            controls[colprop] = rule;
                        }
                    }
                }
                allrules = false;
            }

            for(i=0; i<audio_players.length; i++) {
                var audio = audio_players[i];
                if (audio.small) {
                    controls.controlall = false;
                    controls.height = 15;
                    controls.time = false;
                } else {
                    controls.controlall = true;
                    controls.height = 25;
                    controls.time = true;
                }
                flowplayer(audio.id, M.cfg.wwwroot + '/media/player/flowplayerflash/flowplayer/flowplayer-3.2.18.swf.php', {
                    plugins: {controls: controls, audio: {url: M.cfg.wwwroot +
                    '/media/player/flowplayerflash/flowplayer/flowplayer.audio-3.2.11.swf.php'}},
                    clip: {url: audio.fileurl, provider: "audio", autoPlay: false}
                });
            }
        };

        if (M.cfg.jsrev == -1) {
            var jsurl = M.cfg.wwwroot + '/media/player/flowplayerflash/flowplayer/flowplayer-3.2.13.js';
        } else {
            var jsurl = M.cfg.wwwroot +
                '/lib/javascript.php?jsfile=/media/player/flowplayerflash/flowplayer/flowplayer-3.2.13.min.js&rev=' + M.cfg.jsrev;
        }
        var fileref = document.createElement('script');
        fileref.setAttribute('type','text/javascript');
        fileref.setAttribute('src', jsurl);
        fileref.onload = embed_function;
        fileref.onreadystatechange = embed_function;
        document.getElementsByTagName('head')[0].appendChild(fileref);
    };

    Y.on('domready', function() {
        load_flowplayer();
    });

    return /** @alias module:media_flowplayerflash/loader */ {

        /**
         * Add video player
         * @param id element id
         * @param fileurl media url
         * @param width
         * @param height
         * @param autosize true means detect size from media
         */
        add_video_player: function (id, fileurl, width, height, autosize) {
            video_players.push({id: id, fileurl: fileurl, width: width, height: height, autosize: autosize, resized: false});
        },

        /**
         * Add audio player.
         * @param id
         * @param fileurl
         * @param small
         */
        add_audio_player: function (id, fileurl, small) {
            audio_players.push({id: id, fileurl: fileurl, small: small});
        }

    };
});
