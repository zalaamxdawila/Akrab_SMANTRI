<?php
// helpers.php

require_once __DIR__ . '/app/Services/AnemiaRiskService.php';
require_once __DIR__ . '/app/Services/QuestionnaireAnswerSnapshot.php';
require_once __DIR__ . '/app/Services/QuestionnaireService.php';
require_once __DIR__ . '/app/Services/QuestionnaireLabService.php';
require_once __DIR__ . '/app/Services/QuestionnaireInsights.php';
require_once __DIR__ . '/app/Services/QuestionnaireEligibility.php';
require_once __DIR__ . '/app/Services/QuestionnaireResultPresenter.php';
require_once __DIR__ . '/app/Services/QuestionnaireAggregatePresenter.php';
require_once __DIR__ . '/app/Services/StagedScreeningScore.php';
require_once __DIR__ . '/app/Contracts/StagedScreeningStore.php';
require_once __DIR__ . '/app/Contracts/QuestionnaireRetakeStore.php';
require_once __DIR__ . '/app/Services/StagedScreeningSnapshot.php';
require_once __DIR__ . '/app/Services/StagedScreeningService.php';
require_once __DIR__ . '/app/Services/StagedScreeningResultPresenter.php';
require_once __DIR__ . '/app/Services/QuestionnaireRetakeService.php';
require_once __DIR__ . '/app/Repositories/DashboardRepository.php';
require_once __DIR__ . '/app/Repositories/PdoStagedScreeningStore.php';
require_once __DIR__ . '/app/Repositories/PdoQuestionnaireRetakeStore.php';
require_once __DIR__ . '/app/Repositories/QuestionnaireAnalyticsRepository.php';
require_once __DIR__ . '/views/partials/impersonation_banner.php';

/**
 * Clinical risk output is disabled unless production explicitly opts in.
 */
function isClinicalRiskEnabled()
{
    return modelExecutionGatePassed();
}

/**
 * Check if the user is logged in
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}

/**
 * Check if the logged-in user has a specific role
 */
function require_role($role) {
    check_login();
    $sessionRole = (string) ($_SESSION['role'] ?? '');
    if (!isApplicationRole($sessionRole) || $sessionRole !== $role) {
        http_response_code(403);
        echo 'Akses ditolak.';
        exit;
    }
    if ($role === 'siswa') {
        global $pdo;
        enforceStudentOnboarding($pdo);
    }
}

/**
 * Backward-compatible alias while endpoint calls migrate to the clearer name.
 */
function check_role($role) {
    require_role($role);
}

/** @param array<string, mixed> $state */
function studentOnboardingDestination(array $state): ?string
{
    if (empty($state['questionnaire_id'])) {
        return 'siswa/kuesioner.php';
    }
    return null;
}

function studentOnboardingState(PDO $pdo, int $studentId): array
{
    $statement = $pdo->prepare(
        'SELECT u.email, k.id questionnaire_id
         FROM users u
         LEFT JOIN kuesioner k ON k.id = (
             SELECT latest.id FROM kuesioner latest
             WHERE latest.user_id = u.id AND latest.archived_at IS NULL
               AND latest.history_only_at IS NULL
             ORDER BY latest.created_at DESC, latest.id DESC LIMIT 1
         )
         WHERE u.id = ? AND u.role = \'siswa\''
    );
    $statement->execute([$studentId]);
    return $statement->fetch() ?: [];
}

function enforceStudentOnboarding(PDO $pdo): void
{
    if (($_SESSION['role'] ?? '') !== 'siswa') return;
    $destination = studentOnboardingDestination(
        studentOnboardingState($pdo, (int) $_SESSION['user_id'])
    );
    if ($destination === null) return;
    $currentPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_ends_with($currentPath, '/' . $destination)) return;
    header('Location: ' . BASE_URL . $destination, true, 303);
    exit;
}

function requireActorAction(ActorContext $context, string $action): void
{
    if (!actionAllowedForActor($context, $action)) {
        http_response_code(403);
        throw new DomainException('Aksi tidak diizinkan.');
    }
}

/**
 * Determine risk category based on probability
 */
function getKategoriRisiko($probabilitas) {
    if ($probabilitas < 0.33) {
        return 'rendah';
    } elseif ($probabilitas < 0.66) {
        return 'sedang';
    } else {
        return 'tinggi';
    }
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    if (is_array($data) || is_object($data)) {
        return '';
    }
    // Normalize for storage/querying. Escape only at the HTML output boundary.
    return trim(strip_tags((string) $data));
}

function escape_output(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
