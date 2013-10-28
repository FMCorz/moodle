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
 * Box.net client.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/oauthlib.php');

/**
 * Box.net client class.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class boxnet_client extends oauth2_client {

    /** @const API URL */
    const API = 'https://api.box.com/2.0';

    /**
     * Return authorize URL.
     *
     * @return string
     */
    protected function auth_url() {
        return 'https://www.box.com/api/oauth2/authorize';
    }

    /**
     * Download the file.
     *
     * @param int $fileid File ID.
     * @param string $path Path to download the file to.
     * @return bool Success or not.
     */
    public function download_file($fileid, $path) {
        $result = $this->download_one($this->make_url("/files/$fileid/content"), array(), array('filepath' => $path));
        return ($result === true && $this->info['http_code'] === 200);
    }

    /**
     * Get info of a file.
     *
     * @param int $fileid File ID.
     * @return object
     */
    public function get_file_info($fileid) {
        $result = $this->request($this->make_url("/files/$fileid"));
        return json_decode($result);
    }

    /**
     * Get a folder content.
     *
     * @param int $folderid Folder ID.
     * @return object
     */
    public function get_folder_items($folderid = 0) {
        $result = $this->request($this->make_url("/folders/$folderid/items",
            array('fields' => 'id,name,type,modified_at,size,owned_by')));
        return json_decode($result);
    }

    /**
     * Log out.
     *
     * @return void
     */
    public function log_out() {
        if ($this->accesstoken) {
            $params = array(
                'client_id' => $this->clientid,
                'client_secret' => $this->clientsecret,
                'token' => $this->accesstoken->token
            );
            $this->post($this->get_revoke_url(), $params);
        }
        parent::log_out();
    }

    /**
     * Build a request URL.
     *
     * @param string $uri The URI to request.
     * @param array $params Query string parameters.
     * @return string
     */
    protected function make_url($uri, $params = array()) {
        $url = new moodle_url(self::API . '/' . ltrim($uri, '/'), $params);
        return $url->out(false);
    }

    /**
     * Return the revoke URL.
     *
     * @return string
     */
    protected function revoke_url() {
        return 'https://www.box.com/api/oauth2/revoke';
    }

    /**
     * Share a file and return the link to it.
     *
     * @param string $fileid The file ID.
     * @param bool $businesscheck Whether or not to check if the user can share files, has a business account.
     * @return object
     */
    public function share_file($fileid, $businesscheck = true) {
        // Sharing the file, this requires a PUT request with data within it. We cannot use
        // the standard PUT request 'CURLOPT_PUT' because it expects a file.
        $data = array('shared_link' => array('access' => 'open', 'permissions' =>
            array('can_download' => true, 'can_preview' => true)));
        $options = array(
            'CURLOPT_CUSTOMREQUEST' => 'PUT',
            'CURLOPT_POSTFIELDS' => json_encode($data)
        );
        $result = $this->request($this->make_url("/files/$fileid"), $options);
        $result = json_decode($result);

        if ($businesscheck) {
            // Checks that the user has the right to share the file. If not, throw an exception.
            $this->resetopt();
            $this->resetHeader();
            $this->head($result->shared_link->download_url);
            $info = $this->get_info();
            if ($info['http_code'] == 403) {
                throw new moodle_exception('No permission to share the file');
            }
        }

        return $result->shared_link;
    }

    /**
     * Search.
     *
     * @return object
     */
    public function search($query) {
        $result = $this->request($this->make_url('/search', array('query' => $query, 'limit' => 50, 'offset' => 0)));
        return json_decode($result);
    }

    /**
     * Return token URL.
     *
     * @return string
     */
    protected function token_url() {
        return 'https://www.box.com/api/oauth2/token';
    }

}
