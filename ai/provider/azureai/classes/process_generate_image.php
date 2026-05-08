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

namespace aiprovider_azureai;

use core\http_client;
use core_ai\ai_image;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class process image generation.
 *
 * @package    aiprovider_azureai
 * @copyright  2024 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_generate_image extends abstract_processor {
    /** @var int The number of images to generate dall-e-3 only supports 1 */
    private int $numberimages = 1;

    #[\Override]
    protected function get_endpoint(): UriInterface {
        $url = rtrim(get_config('aiprovider_azureai', 'endpoint'), '/')
            . '/openai/deployments/'
            . $this->get_deployment_name()
            . '/images/generations?api-version='
            . $this->get_api_version();

        return new Uri($url);
    }

    #[\Override]
    protected function get_deployment_name(): string {
        return get_config('aiprovider_azureai', 'action_generate_image_deployment');
    }

    #[\Override]
    protected function get_api_version(): string {
        return get_config('aiprovider_azureai', 'action_generate_image_apiversion');
    }

    #[\Override]
    protected function query_ai_api(): array {
        $response = parent::query_ai_api();

        if ($response['success']) {
            $userid = $this->action->get_configuration('userid');
            if (!empty($response['sourceurl'])) {
                $fileobj = $this->url_to_file($userid, $response['sourceurl']);
            } else if (!empty($response['b64data'])) {
                $fileobj = $this->b64_to_file($userid, $response['b64data']);
            } else {
                return [
                    'success' => false,
                    'errorcode' => 500,
                    'errormessage' => 'AzureAI image response had neither url nor b64_json',
                ];
            }
            $response['draftfile'] = $fileobj;
        }

        unset($response['b64data']);
        return $response;
    }

    /**
     * Convert the given aspect ratio to an image size
     * that is compatible with the azureai API.
     *
     * @param string $ratio The aspect ratio of the image.
     * @return string The size of the image.
     * @throws \coding_exception
     */
    private function calculate_size(string $ratio): string {
        if ($ratio === 'square') {
            $size = '1024x1024';
        } else if ($ratio === 'landscape') {
            $size = '1792x1024';
        } else if ($ratio === 'portrait') {
            $size = '1024x1792';
        } else {
            throw new \coding_exception('Invalid aspect ratio: ' . $ratio);
        }
        return $size;
    }

    #[\Override]
    protected function create_request_object(string $userid): RequestInterface {
        $deployment = strtolower($this->get_deployment_name());
        $isdalle = str_contains($deployment, 'dall');

        // Quality value vocabulary differs by model:
        // - DALL-E 3:    standard | hd
        // - gpt-image-1: low | medium | high | auto
        // Moodle's UI sends DALL-E values, so remap when the deployment isn't DALL-E.
        $quality = $this->action->get_configuration('quality');
        if (!$isdalle) {
            $quality = match ($quality) {
                'hd' => 'high',
                'standard' => 'medium',
                default => 'auto',
            };
        }

        $body = [
            'prompt' => $this->action->get_configuration('prompttext'),
            'n' => $this->numberimages,
            'quality' => $quality,
            'size' => $this->calculate_size($this->action->get_configuration('aspectratio')),
            'user' => $userid,
        ];

        // The `style` parameter (vivid/natural) is DALL-E 3 only — gpt-image-1 rejects
        // it with "Unknown parameter: 'style'.".
        if ($isdalle) {
            $body['style'] = $this->action->get_configuration('style');
        }

        return new Request(
            method: 'POST',
            uri: '',
            body: json_encode((object) $body),
            headers: [
                'Content-Type' => 'application/json',
            ],
        );
    }

    #[\Override]
    protected function handle_api_success(ResponseInterface $response): array {
        $bodyobj = json_decode($response->getBody()->getContents());
        $data = $bodyobj->data[0] ?? null;

        // DALL-E 3 returns a hosted image URL plus a model-rewritten prompt; gpt-image-1
        // returns the raw image as base64 (`b64_json`) and no revised_prompt.
        return [
            'success' => true,
            'sourceurl' => $data->url ?? '',
            'b64data' => $data->b64_json ?? '',
            'revisedprompt' => $data->revised_prompt ?? '',
        ];
    }

    /**
     * Convert the url for the image  to a file.
     *
     * Placements can't interact with the provider AI directly,
     * therefore we need to provide the image file in a format that can
     * be used by placements. So we use the file API.
     *
     * @param int $userid The user id.
     * @param string $url The URL to the image.
     * @return \stored_file The file object.
     */
    private function url_to_file(int $userid, string $url): \stored_file {
        global $CFG;

        require_once("{$CFG->libdir}/filelib.php");

        // Azure AI doesn't always return unique file names, but does return unique URLS.
        // Therefore, some processing is needed to get a unique filename.
        $parsedurl = parse_url($url, PHP_URL_PATH); // Parse the URL to get the path.
        $fileext = pathinfo($parsedurl, PATHINFO_EXTENSION); // Get the file extension.
        $filename = substr(hash('sha512', ($url . $userid)), 0, 16) . '.' . $fileext;

        $client = \core\di::get(http_client::class);

        // Download the image and add the watermark.
        $downloadtmpdir = make_request_directory();
        $tempdst = $downloadtmpdir . $filename;
        $client->get($url, [
            'sink' => $tempdst,
            'timeout' => $CFG->repositorygetfiletimeout,
        ]);
        $image = new ai_image($tempdst);
        $image->add_watermark()->save();

        // We put the file in the user draft area initially.
        // Placements (on behalf of the user) can then move it to the correct location.
        $fileinfo = new \stdClass();
        $fileinfo->contextid = \context_user::instance($userid)->id;
        $fileinfo->filearea = 'draft';
        $fileinfo->component = 'user';
        $fileinfo->itemid = file_get_unused_draft_itemid();
        $fileinfo->filepath = '/';
        $fileinfo->filename = $filename;

        $fs = get_file_storage();
        return $fs->create_file_from_string($fileinfo, file_get_contents($tempdst));
    }

    /**
     * Save a base64-encoded image (gpt-image-1's `b64_json`) to the user draft area.
     *
     * @param int $userid The user id.
     * @param string $b64data Base64-encoded image bytes (no `data:` prefix).
     * @return \stored_file The file object.
     */
    private function b64_to_file(int $userid, string $b64data): \stored_file {
        global $CFG;

        require_once("{$CFG->libdir}/filelib.php");

        $bytes = base64_decode($b64data, true);
        if ($bytes === false) {
            throw new \moodle_exception('error', 'aiprovider_azureai');
        }

        $filename = substr(hash('sha512', $b64data . $userid), 0, 16) . '.png';

        // ai_image::add_watermark() works on a file path, so write to a temp file first.
        $downloadtmpdir = make_request_directory();
        $tempdst = $downloadtmpdir . $filename;
        file_put_contents($tempdst, $bytes);
        $image = new ai_image($tempdst);
        $image->add_watermark()->save();

        $fileinfo = new \stdClass();
        $fileinfo->contextid = \context_user::instance($userid)->id;
        $fileinfo->filearea = 'draft';
        $fileinfo->component = 'user';
        $fileinfo->itemid = file_get_unused_draft_itemid();
        $fileinfo->filepath = '/';
        $fileinfo->filename = $filename;

        $fs = get_file_storage();
        return $fs->create_file_from_string($fileinfo, file_get_contents($tempdst));
    }
}
