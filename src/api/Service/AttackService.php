<?php

namespace Palshin\PswCrack\Service;

interface AttackService
{
    const SALT = 'ThisIs-A-Salt123';

    public function attack(
        string $hash,
        $firstHalfTable = null,
        $secondHalfTable = null
    ): ?string;
}