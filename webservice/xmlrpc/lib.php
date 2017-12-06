<?php
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
 * Moodle XML-RPC library
 *
 * @package    webservice_xmlrpc
 * @copyright  2009 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Moodle XML-RPC client
 *
 * @package    webservice_xmlrpc
 * @copyright  2010 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webservice_xmlrpc_client {

    /** @var moodle_url The XML-RPC server url. */
    protected $serverurl;

    /** @var string The token for the XML-RPC call. */
    protected $token;

    /**
     * Constructor
     *
     * @param string $serverurl a Moodle URL
     * @param string $token the token used to do the web service call
     */
    public function __construct($serverurl, $token) {
        $this->serverurl = new moodle_url($serverurl);
        $this->token = $token;
    }

    /**
     * Set the token used to do the XML-RPC call
     *
     * @param string $token the token used to do the web service call
     */
    public function set_token($token) {
        $this->token = $token;
    }

    /**
     * Execute client WS request with token authentication
     *
     * @param string $functionname the function name
     * @param array $params An associative array containing the the parameters of the function being called.
     * @return mixed The decoded XML RPC response.
     * @throws moodle_exception
     */
    public function call($functionname, $params = array()) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        if ($this->token) {
            $this->serverurl->param('wstoken', $this->token);
        }

        $request = $this->encode_request($functionname, $params);

        // Set the headers.
        $headers = array(
            'Content-Length' => strlen($request),
            'Content-Type' => 'text/xml; charset=utf-8',
            'Host' => $this->serverurl->get_host(),
            'User-Agent' => 'Moodle XML-RPC Client/1.0',
        );

        // Get the response.
        $response = download_file_content($this->serverurl->out(false), $headers, $request);

        // Decode the response.
        $result = $this->decode_response($response);
        if (is_array($result) && xmlrpc_is_fault($result)) {
            throw new Exception($result['faultString'], $result['faultCode']);
        }

        return $result;
    }

    /**
     * Generates XML for a method request.
     *
     * @param string $functionname Name of the method to call.
     * @param mixed $params Method parameters compatible with the method signature.
     * @return string
     */
    protected function encode_request($functionname, $params) {

        $outputoptions = array(
            'encoding' => 'utf-8',
            'escaping' => 'markup',
        );

        // See MDL-53962 - needed for backwards compatibility on <= 3.0.
        $params = array_values($params);

        return xmlrpc_encode_request($functionname, $params, $outputoptions);
    }

    /**
     * Parses and decodes the response XML
     *
     * @param string $response
     * @return array
     */
    protected function decode_response($response) {
        $response = self::fix_encoding($response);
        // Unexplainable bug with xmlrpc_decode - sometimes it picks up the encoding correctly, sometimes not.
        if (preg_match('/^<\?xml [^>]*\bencoding="(.*?)"/', $response, $matches)) {
            $result = xmlrpc_decode($response, $matches[1]);
        } else {
            $result = xmlrpc_decode($response);
        }
        return $result;
    }

    /**
     * Fixes weird encoding introduced by xmlrpc_encode_request()
     *
     * In MDL-57775 we added option 'escaping'=>'markup' to xmlrpc_encode_request(). This was integrated in Moodle 3.2.5, 3.3.2 and up
     * In Moodle 3.0 and earlier Zend was taking care of xmlrpc properly.
     * However other sites that have neither Zend nor the patch will encode the request/response very weirdly. For example,
     * it affects all Moodle 3.1 sites.
     *
     * This method will fix encoding in the input string (either response or request). It should be called before
     * xmlrpc_decode_request() or xmlrpc_decode().
     *
     * @param string $xml
     * @return mixed
     */
    public static function fix_encoding($xml) {
        // Function xmlrpc_encode() weirdly converts UTF-8 characters with codes 128-1114111 - instead of using one &#1234;
        // it splits it into code for each byte in the character. Apart from that there is a bug with some 2-byte characters
        // that may still be present on some clients.
        // Regex $regex2 will find all combinations of two codes (i.e. "&#206;&#160;") that will be fixed back to
        // characters with codes 128-2047.
        // Regex $regex3 will find all combinations of three codes (i.e. "&#238;&#133;&#143;") that will be fixed back
        // to characters with codes 2048-65535.
        // Regex $regex3 will find all combinations of four codes (i.e. "&#243;&#176;&#161;&#152;") that will be
        // fixed back to characters with codes 65536-1114111.
        $ch64 = '\&\#(128|129|1[3-8]\d|190|191);'; // Matches "&#128;"-"&#191;" .
        $regex2 = '/\&\#(19[4-9]|20\d|21\d|22[0-3]);'.$ch64.'/';
        $regex3 = '/\&\#(22[4-9]|23\d);'.$ch64.$ch64.'/';
        $regex4 = '/\&\#(24[0-4]);'.$ch64.$ch64.$ch64.'/';

        $xml = preg_replace_callback([$regex2, $regex3, $regex4], function($matches) {
            $s = '';
            for ($i = 1; $i < count($matches); $i++) {
                $s .= chr($matches[$i]);
            }
            return $s;
        }, $xml);

        // Regex $regex2bug will find all combinations of two codes (i.e. "&#26;&#160;") that will be fixed back to
        // characters with codes 128-2047.
        // Some versions of xmlrpc encode incorrectly and use 26 instead of 206, 27 instead of 207, etc.
        // See php bug https://github.com/php/php-src/commit/98a6986d97fd2d09fef2c4b870f6d77b5d29efe0 .
        $regex2bug = '/\&\#(2\d);'.$ch64.'/';
        $xml = preg_replace_callback($regex2bug, function($matches) {
            return chr(((int)$matches[1]) + 180) . chr($matches[2]);
        }, $xml);

        return $xml;
    }
}
