<?php

declare(strict_types=1);

const AKRAB_CERTIFICATE_DAYS = 12;
const AKRAB_CERTIFICATE_WINDOW_DAYS = 90;

function isCertificateEligible(int $distinctDays): bool
{
    return $distinctDays >= AKRAB_CERTIFICATE_DAYS;
}
