<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Settings for auth_turnstile.
 *
 * @package    auth_turnstile
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'auth_turnstile/description',
        '',
        new lang_string('auth_turnstiledescription', 'auth_turnstile')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'auth_turnstile/enabled',
        new lang_string('enabled', 'auth_turnstile'),
        new lang_string('enabled_desc', 'auth_turnstile'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'auth_turnstile/sitekey',
        new lang_string('sitekey', 'auth_turnstile'),
        new lang_string('sitekey_desc', 'auth_turnstile'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'auth_turnstile/secretkey',
        new lang_string('secretkey', 'auth_turnstile'),
        new lang_string('secretkey_desc', 'auth_turnstile'),
        ''
    ));
}
