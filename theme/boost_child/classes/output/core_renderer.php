<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace theme_boost_child\output;

/**
 * Core renderer overrides for boost_child.
 *
 * @package   theme_boost_child
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {
    /**
     * Adds Turnstile context to login form template rendering.
     *
     * @param \core_auth\output\login $form
     * @return string
     */
    public function render_login(\core_auth\output\login $form) {
        $context = $form->export_for_template($this);

        $context->errorformatted = $this->error_text($context->error);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }

        $context->logourl = $url;
        $context->sitename = format_string(
            get_site()->fullname,
            true,
            ['context' => \context_course::instance(SITEID), 'escape' => false]
        );

        $sitekey = $this->get_turnstile_sitekey();
        $context->turnstileenabled = !empty($sitekey);
        $context->turnstilesitekey = $sitekey;

        return $this->render_from_template('core/loginform', $context);
    }

    /**
     * Adds Turnstile context and widget injection for signup form template rendering.
     *
     * @param \moodleform $form
     * @return string
     */
    public function render_login_signup_form($form) {
        $context = $form->export_for_template($this);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }

        $context['logourl'] = $url;
        $context['sitename'] = format_string(
            get_site()->fullname,
            true,
            ['context' => \context_course::instance(SITEID), 'escape' => false]
        );

        $sitekey = $this->get_turnstile_sitekey();
        $context['turnstileenabled'] = !empty($sitekey);
        $context['turnstilesitekey'] = $sitekey;

        if (!empty($sitekey) && !empty($context['formhtml'])) {
            $widget = '<div class="login-form-turnstile mb-3">'
                . '<div class="cf-turnstile" data-sitekey="' . s($sitekey) . '"></div>'
                . '</div>';

            $updated = preg_replace('/(<(?:button|input)[^>]*type=["\']submit["\'][^>]*>)/i', $widget . '$1', $context['formhtml'], 1);
            if ($updated === null || $updated === $context['formhtml']) {
                $updated = str_replace('</form>', $widget . '</form>', $context['formhtml']);
            }
            $context['formhtml'] = $updated;
        }

        return $this->render_from_template('core/signup_form_layout', $context);
    }

    /**
     * Turnstile site key for widget rendering.
     *
     * @return string
     */
    protected function get_turnstile_sitekey(): string {
        return (string)(get_config('auth_turnstile', 'sitekey') ?? '');
    }
}
