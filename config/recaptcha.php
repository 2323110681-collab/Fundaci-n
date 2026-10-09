<?php

declare(strict_types=1);

const RECAPTCHA_SITE_KEY = '6Lfhi9stAAAAACmOZxHToHrH37NrplgPgQNoCU8d';
const RECAPTCHA_SECRET_KEY = '6Lfhi9stAAAAAPEUM2Qdszt3n_7N_PnrW9ajtbG6';
const RECAPTCHA_LOCAL_SITE_KEY = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';

function isLocalDevelopmentEnvironment(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    return $host === 'localhost'
        || str_contains($host, 'localhost')
        || str_contains($host, '127.0.0.1')
        || str_contains($host, '.local');
}

function verifyRecaptcha(string $response, ?string $remoteIp = null): bool
{
    if (isLocalDevelopmentEnvironment()) {
        return true;
    }

    if ($response === '') {
        return false;
    }

    $verificationData = array(
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $response,
    );
    if ($remoteIp !== null && $remoteIp !== '') {
        $verificationData['remoteip'] = $remoteIp;
    }

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($verificationData),
            'timeout' => 8,
            'ignore_errors' => true,
        ),
    ));
    $result = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    if ($result === false) {
        return false;
    }

    $verification = json_decode($result, true);
    return is_array($verification) && ($verification['success'] ?? false) === true;
}

function getRecaptchaSiteKey(): string
{
    return isLocalDevelopmentEnvironment() ? RECAPTCHA_LOCAL_SITE_KEY : RECAPTCHA_SITE_KEY;
}