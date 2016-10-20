Able player 3.0
---------------
https://github.com/ableplayer/ableplayer/
http://ableplayer.github.io/ableplayer/

Instructions to import Able player into Moodle:

1. copy 'build/ableplayer.js' into 'amd/src/ablewrapper.js' and prepend with:
     define(['jquery', 'media_able/js.cookie', 'media_able/modernizrwrapper'], function(jQuery, Cookies) {
   also close brackets in the end of the file.
2. copy 'thirdparty/js.cookie.js' into 'amd/src/js.cookie.js' , do not modify.
3. copy 'thirdparty/modernizr.custom.js' into 'amd/src/modernizrwrapper.js' and prepend with:
     define([], function() {
   also close brackets in the end of the file.
4. copy contents of 'button-icons/' into 'button-icons/' folder (except for fonts)
5. copy 'button-icons/fonts/' into 'fonts/' folder
6. copy contens of 'images/' folder into 'pix/' folder
7. copy 'styles/ableplayer.css' into 'styles.css' leaving the custom CSS in the beginning to resolve
   conflicts between moodle themes and able styles;
   replace url('../images/wingrip.png') -> url([[pix:media_ableplayer|wingrip.png]])
   url('../button-icons/fonts/able.eot?dqripi') -> url([[font:media_ableplayer|able.eot]])
   similarly for other fonts
8. copy 'LICENSE' into 'ableplayer_LICENSE'
9. add eslint-disable and stylelint-disable where needed and run grunt
