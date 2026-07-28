<?php
// helpers.php

/**
 * Clinical risk output is disabled unless production explicitly opts in.
 */
function isClinicalRiskEnabled()
{
    return clinicalApprovalGatePassed();
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
}

/**
 * Backward-compatible alias while endpoint calls migrate to the clearer name.
 */
function check_role($role) {
    require_role($role);
}

/**
 * Function to predict anemia risk
 * Uses Kaggle dataset Logistic Regression weights if lab data is available.
 * Otherwise, falls back to a heuristic based on the questionnaire scores.
 */
function prediksiRisikoAnemia($input_data) {
    // Check if Lab Data is provided (Hemoglobin is the primary indicator)
    if (!empty($input_data['kadar_hb'])) {
        // Mock coefficients trained from Kaggle anemia-dataset (biswaranjanrao)
        // You can update these after running train_model.py
        $koefisien = [
            'b0' => 15.0, // Intercept
            'gender' => 0.5, // 0: Male, 1: Female (Assuming all respondents are female 'remaja putri', so 1)
            'hemoglobin' => -1.5, // Lower Hb = higher risk
            'mch' => -0.1,
            'mchc' => -0.1,
            'mcv' => -0.05
        ];

        $z = $koefisien['b0'];
        $z += $koefisien['gender'] * 1; 
        $z += $koefisien['hemoglobin'] * (float)$input_data['kadar_hb'];
        $z += $koefisien['mch'] * (isset($input_data['kadar_mch']) && $input_data['kadar_mch'] != '' ? (float)$input_data['kadar_mch'] : 29.5); // Default normal MCH
        $z += $koefisien['mchc'] * (isset($input_data['kadar_mchc']) && $input_data['kadar_mchc'] != '' ? (float)$input_data['kadar_mchc'] : 33.2); // Default normal MCHC
        $z += $koefisien['mcv'] * (isset($input_data['kadar_mcv']) && $input_data['kadar_mcv'] != '' ? (float)$input_data['kadar_mcv'] : 90.0); // Default normal MCV

        // Sigmoid function
        $probabilitas = 1 / (1 + exp(-$z));
        return $probabilitas;
    } 
    
    // Fallback: Heuristic based on the detailed questionnaire scores
    // Gejala (Max 100), Sikap (Max 40), Pengetahuan (Max 10), Makan (Max 18)
    $skor_gejala = isset($input_data['skor_gejala']) ? (int)$input_data['skor_gejala'] : 0;
    $skor_makan = isset($input_data['skor_makan']) ? (int)$input_data['skor_makan'] : 10;
    
    // Base risk
    $risk = 0.1;
    
    // High gejala increases risk significantly
    if ($skor_gejala > 50) $risk += 0.4;
    elseif ($skor_gejala > 25) $risk += 0.2;
    
    // Poor eating habits increases risk
    if ($skor_makan < 9) $risk += 0.3;
    elseif ($skor_makan < 14) $risk += 0.1;
    
    // Menstrual cycle factor
    if (isset($input_data['mens_teratur']) && $input_data['mens_teratur'] == 'tidak') {
        $risk += 0.15;
    }
    
    return min($risk, 0.99);
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
