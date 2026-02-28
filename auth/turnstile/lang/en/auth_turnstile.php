<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Language strings for auth_turnstile.
 *
 * @package    auth_turnstile
 */

$string['pluginname'] = 'Turnstile login validation';
$string['auth_turnstiledescription'] = 'Validates Cloudflare Turnstile responses before username/password authentication is attempted.';
$string['enabled'] = 'Enable Turnstile validation';
$string['enabled_desc'] = 'When enabled, Turnstile is required for all normal login attempts.';
$string['sitekey'] = 'Turnstile site key';
$string['sitekey_desc'] = 'Cloudflare Turnstile site key used to render the widget on login and signup forms.';
$string['secretkey'] = 'Turnstile secret key';
$string['secretkey_desc'] = 'Cloudflare Turnstile secret key used for server-side /siteverify checks.';
$string['missingsecret'] = 'Turnstile secret key is not configured. Contact an administrator.';
$string['validationfailed'] = 'Turnstile verification failed. Please try again.';
