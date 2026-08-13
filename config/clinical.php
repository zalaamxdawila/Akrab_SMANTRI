<?php

declare(strict_types=1);

const AKRAB_CLINICAL_DISCLAIMER = 'Hasil ini adalah skrining risiko, bukan diagnosis dan tidak menggantikan pemeriksaan tenaga kesehatan.';
const AKRAB_EMERGENCY_GUIDANCE = 'Jika mengalami sesak napas berat, pingsan, perdarahan banyak, nyeri dada, atau kondisi darurat lain, segera hubungi layanan darurat atau fasilitas kesehatan terdekat.';

function clinicalApprovalGatePassed(): bool
{
    $feature = filter_var(environmentValue('CLINICAL_RISK_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
    $owner = filter_var(environmentValue('CLINICAL_OWNER_APPROVED') ?: 'false', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
    $model = filter_var(environmentValue('CLINICAL_MODEL_APPROVED') ?: 'false', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
    $specVersion = trim((string) (environmentValue('CLINICAL_SPEC_VERSION') ?: ''));
    $modelVersion = trim((string) (environmentValue('CLINICAL_MODEL_VERSION') ?: ''));
    $checksum = trim((string) (environmentValue('CLINICAL_MODEL_CHECKSUM') ?: ''));
    return $feature && $owner && $model && $specVersion !== '' && $modelVersion !== '' && $checksum !== '';
}

function researchSimulationGatePassed(): bool
{
    return filter_var(
        environmentValue('AKRAB_RESEARCH_MODEL_ENABLED') ?: 'false',
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE
    ) === true
        && trim((string) (environmentValue('CLINICAL_MODEL_VERSION') ?: '')) !== ''
        && trim((string) (environmentValue('CLINICAL_MODEL_CHECKSUM') ?: '')) !== '';
}

function modelExecutionGatePassed(): bool
{
    return clinicalApprovalGatePassed() || researchSimulationGatePassed();
}
