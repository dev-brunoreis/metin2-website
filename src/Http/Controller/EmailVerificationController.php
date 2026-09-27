<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller;

use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Mail\MailerInterface;
use Metin2Website\Service\AccountEmailService;
use Metin2Website\Theme\ThemeEngine;

class EmailVerificationController extends Controller
{
    public function __construct(
        ThemeEngine $theme,
        Auth $auth,
        Csrf $csrf,
        Translator $translator,
        private AccountEmailService $emails,
        private MailerInterface $mailer,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator);
    }

    public function verify(string $token): Response
    {
        if ($this->emails->verify($token)) {
            $this->flash('success', $this->t('auth.email_verified'));

            return $this->redirect($this->auth->check() ? '/account' : '/login');
        }

        $this->flash('error', $this->t('auth.verify_invalid'));

        return $this->redirect('/login');
    }

    public function resend(): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if (!$this->assertCsrf()) {
            $this->flash('error', $this->t('auth.invalid_csrf'));

            return $this->redirect('/account');
        }

        if (!$this->mailer->isConfigured()) {
            $this->flash('error', $this->t('auth.mail_not_configured'));

            return $this->redirect('/account');
        }

        $accountId = $this->auth->id();
        $email = $this->auth->user()['email'] ?? null;

        if ($accountId === null || !is_string($email) || $email === '') {
            $this->flash('error', $this->t('auth.no_email'));

            return $this->redirect('/account');
        }

        try {
            $this->emails->sendVerification($accountId, $email);
            $this->flash('success', $this->t('auth.verify_sent'));
        } catch (\RuntimeException) {
            $this->flash('error', $this->t('auth.mail_send_failed'));
        }

        return $this->redirect('/account');
    }
}
