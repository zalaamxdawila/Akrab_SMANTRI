<?php

declare(strict_types=1);

const AKRAB_RISK_CATEGORIES = ['rendah', 'sedang', 'tinggi'];

function canonicalRiskCategory(string $category): string
{
    return in_array($category, AKRAB_RISK_CATEGORIES, true) ? $category : 'rendah';
}

function adviceCategoryForRisk(string $risk): string
{
    return match (canonicalRiskCategory($risk)) {
        'tinggi' => 'berat',
        'sedang' => 'sedang',
        default => 'tidak_anemia',
    };
}
