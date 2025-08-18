<?php

namespace Tests\Fakes;

class FakeGoogle2FA
{
    public function generateSecretKey($length = 32)
    {
        return str_repeat('A', $length);
    }

    public function getQRCodeUrl(...$args)
    {
        return 'otpauth://totp/...';
    }

    public function verifyKey($secret, $code)
    {
        return $secret === str_repeat('A', 32) && $code === '123456';
    }
}