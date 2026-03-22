<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Turnstile login validation auth plugin.
 *
 * @package    auth_turnstile
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Turnstile auth plugin class.
 */
class auth_plugin_turnstile extends auth_plugin_base {
    /** @var string Turnstile endpoint. */
    private const VERIFY_ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'turnstile';
        $this->config = get_config('auth_turnstile');
    }

    /**
     * This plugin does not authenticate credentials.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function user_login($username, $password) {
        return false;
    }

    /**
     * Hook into login page before credential authentication.
     */
    public function loginpage_hook() {
        global $errormsg, $errorcode;

        if (!is_enabled_auth($this->authtype)) {
            return;
        }

        if (empty($this->config->enabled)) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $data = data_submitted();
        if (empty($data) || !isset($data->username) || $data->username === 'guest') {
            return;
        }

        $secret = trim((string)($this->config->secretkey ?? ''));
        if ($secret === '') {
            $errormsg = get_string('missingsecret', 'auth_turnstile');
            $errorcode = AUTH_LOGIN_FAILED;
            return;
        }

        $token = trim((string)optional_param('cf-turnstile-response', '', PARAM_RAW_TRIMMED));
        if ($token === '') {
            $errormsg = get_string('validationfailed', 'auth_turnstile');
            $errorcode = AUTH_LOGIN_FAILED;
            return;
        }

        if (!$this->validate_turnstile_token($secret, $token)) {
            $errormsg = get_string('validationfailed', 'auth_turnstile');
            $errorcode = AUTH_LOGIN_FAILED;
        }
    }

    /**
     * Validate Turnstile token against Cloudflare.
     *
     * @param string $secret
     * @param string $token
     * @return bool
     */
    private function validate_turnstile_token(string $secret, string $token): bool {
        $curl = new curl();

        $payload = [
            'secret' => $secret,
            'response' => $token,
            'remoteip' => getremoteaddr(),
        ];

        $response = $curl->post(self::VERIFY_ENDPOINT, $payload, [
            'CURLOPT_TIMEOUT' => 10,
            'CURLOPT_CONNECTTIMEOUT' => 5,
        ]);

        if (empty($response)) {
            return false;
        }

        $decoded = json_decode($response);
        if (!is_object($decoded) || empty($decoded->success)) {
            return false;
        }

        return true;
    }

    /**
     * The plugin cannot be set on users.
     *
     * @return bool
     */
    public function can_be_manually_set() {
        return false;
    }

    /**
     * The plugin is not an authentication backend.
     *
     * @return bool
     */
    public function is_internal() {
        return false;
    }
}
