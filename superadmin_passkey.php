<?php
try {
    require_once 'config.php';
    require_once 'helpers.php';
    require_once 'vendor_manual/WebAuthn/WebAuthn.php';

    $domain = parse_url(BASE_URL, PHP_URL_HOST);
    if (!is_string($domain) || !applicationHostIsAllowed($domain, $appEnvironment)) {
        throw new RuntimeException('Passkey relying party is not configured.');
    }
    $WebAuthn = new \lbuchs\WebAuthn\WebAuthn('AKRAB Superadmin Vault', $domain, ['android-key', 'android-safetynet', 'apple', 'fido-u2f', 'none', 'packed', 'tpm'], true);

    $stmt = $pdo->prepare("SELECT id, username, password_hash, nama, role, status FROM users WHERE role = 'superadmin' LIMIT 1");
    $stmt->execute();
    $superadmin = $stmt->fetch();

    if (!$superadmin || !userCanAuthenticate($superadmin)) {
        throw new RuntimeException('Passkey authentication is unavailable.');
    }

    if (isset($_GET['action'])) {
        header('Content-Type: application/json');

        $action = (string) $_GET['action'];
        if (!in_array($action, ['get_create_args', 'process_create', 'get_get_args', 'process_get'], true)) {
            http_response_code(404);
            throw new RuntimeException('Unknown passkey action.');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            throw new RuntimeException('Passkey actions require POST.');
        }

        $post = json_decode(file_get_contents('php://input'), true);
        $post = is_array($post) ? $post : [];

        if ($action === 'get_create_args') {
            if (!AuthAttemptLimiter::allows($_SESSION, 'passkey-enrollment')) {
                throw new RuntimeException('Passkey enrollment is temporarily limited.');
            }
            if (!password_verify($post['password'] ?? '', $superadmin['password_hash'])) {
                AuthAttemptLimiter::record($_SESSION, 'passkey-enrollment');
                throw new RuntimeException('Passkey enrollment was rejected.');
            }
            AuthAttemptLimiter::clear($_SESSION, 'passkey-enrollment');
            $createArgs = $WebAuthn->getCreateArgs((string)$superadmin['id'], $superadmin['username'], $superadmin['nama'], 20, true, 'required');
            $_SESSION['webauthn_challenge'] = $WebAuthn->getChallenge();
            echo json_encode($createArgs);
            exit;
        }

        if ($action === 'process_create') {
            $clientDataJSON = base64_decode(strtr((string) ($post['clientDataJSON'] ?? ''), '-_', '+/'), true);
            $attestationObject = base64_decode(strtr((string) ($post['attestationObject'] ?? ''), '-_', '+/'), true);
            $challenge = $_SESSION['webauthn_challenge'] ?? null;
            unset($_SESSION['webauthn_challenge']);
            if ($clientDataJSON === false || $attestationObject === false || $challenge === null) {
                throw new RuntimeException('Invalid passkey enrollment payload.');
            }
            $data = $WebAuthn->processCreate($clientDataJSON, $attestationObject, $challenge, true, true, false);

            $stmt = $pdo->prepare("INSERT INTO webauthn_credentials (user_id, credential_id, public_key, sign_count) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $superadmin['id'],
                base64_encode($data->credentialId),
                $data->credentialPublicKey,
                (int) ($data->signatureCounter ?? 0),
            ]);
            recordAuditEvent(
                $pdo,
                (int) $superadmin['id'],
                'passkey.registered',
                'webauthn_credential',
                (int) $pdo->lastInsertId(),
                ['outcome' => 'success']
            );
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'get_get_args') {
            if (!AuthAttemptLimiter::allows($_SESSION, 'passkey-login')) {
                throw new RuntimeException('Passkey login is temporarily limited.');
            }
            $stmt = $pdo->prepare("SELECT credential_id FROM webauthn_credentials WHERE user_id = ?");
            $stmt->execute([$superadmin['id']]);
            $creds = $stmt->fetchAll();
            $credentialIds = [];
            foreach ($creds as $c) {
                $credentialIds[] = base64_decode($c['credential_id']);
            }
            $getArgs = $WebAuthn->getGetArgs($credentialIds, 20, true, true, true, true, true, 'required');
            $_SESSION['webauthn_challenge'] = $WebAuthn->getChallenge();
            echo json_encode($getArgs);
            exit;
        }

        if ($action === 'process_get') {
            if (!AuthAttemptLimiter::allows($_SESSION, 'passkey-login')) {
                throw new RuntimeException('Passkey login is temporarily limited.');
            }
            try {
                $clientDataJSON = base64_decode(strtr((string) ($post['clientDataJSON'] ?? ''), '-_', '+/'), true);
                $authenticatorData = base64_decode(strtr((string) ($post['authenticatorData'] ?? ''), '-_', '+/'), true);
                $signature = base64_decode(strtr((string) ($post['signature'] ?? ''), '-_', '+/'), true);
                $id = base64_decode(strtr((string) ($post['id'] ?? ''), '-_', '+/'), true);
                $challenge = $_SESSION['webauthn_challenge'] ?? null;
                unset($_SESSION['webauthn_challenge']);
                if (
                    $clientDataJSON === false
                    || $authenticatorData === false
                    || $signature === false
                    || $id === false
                    || $challenge === null
                ) {
                    throw new RuntimeException('Invalid passkey login payload.');
                }

                $stmt = $pdo->prepare("SELECT id, public_key, sign_count FROM webauthn_credentials WHERE credential_id = ? AND user_id = ?");
                $stmt->execute([base64_encode($id), $superadmin['id']]);
                $cred = $stmt->fetch();

                if (!$cred) {
                    throw new RuntimeException('Passkey credential was rejected.');
                }

                $WebAuthn->processGet(
                    $clientDataJSON,
                    $authenticatorData,
                    $signature,
                    $cred['public_key'],
                    $challenge,
                    (int) $cred['sign_count'],
                    true,
                    true
                );
                $signatureCounter = $WebAuthn->getSignatureCounter();
                if (is_int($signatureCounter)) {
                    $stmt = $pdo->prepare("UPDATE webauthn_credentials SET sign_count = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([$signatureCounter, $cred['id'], $superadmin['id']]);
                }
            } catch (Throwable $authenticationException) {
                AuthAttemptLimiter::record($_SESSION, 'passkey-login');
                throw $authenticationException;
            }

            AuthAttemptLimiter::clear($_SESSION, 'passkey-login');
            regenerateAuthenticatedSession();
            $_SESSION['user_id'] = (int) $superadmin['id'];
            $_SESSION['username'] = $superadmin['username'];
            $_SESSION['role'] = 'superadmin';
            recordAuditEvent(
                $pdo,
                (int) $superadmin['id'],
                'auth.passkey_login_succeeded',
                'session',
                null,
                ['outcome' => 'success', 'actor_role' => 'superadmin']
            );
            session_write_close();

            echo json_encode(['success' => true]);
            exit;
        }
    }

    $stmt = $pdo->prepare("SELECT count(*) FROM webauthn_credentials WHERE user_id = ?");
    $stmt->execute([$superadmin['id']]);
    $hasPasskey = $stmt->fetchColumn() > 0;
} catch (Throwable $e) {
    if (function_exists('akrabLog')) {
        akrabLog('error', 'passkey_request_failed', [
            'exception_class' => get_class($e),
        ]);
    }
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'Proses Passkey gagal. Muat ulang halaman lalu coba kembali.']);
        exit;
    }
    http_response_code(500);
    exit('Layanan Passkey tidak tersedia sementara.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Vault - AKRAB</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
    <style>
        body {
            background-color: #0f172a;
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .vault-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .biometric-icon {
            width: 80px;
            height: 80px;
            color: #38bdf8;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        .btn-glow {
            background: #0284c7;
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 0 15px rgba(2, 132, 199, 0.5);
        }
        .btn-glow:hover {
            background: #0369a1;
            box-shadow: 0 0 25px rgba(2, 132, 199, 0.8);
            color: white;
        }
    </style>
</head>
<body>

<div class="vault-card">
    <i data-lucide="scan-face" class="biometric-icon"></i>
    <h3 class="mb-1">Superadmin Vault</h3>
    <p class="text-secondary small mb-4">Akses eksklusif melalui Passkey</p>

    <div id="alertBox" class="alert alert-danger d-none small"></div>

    <?php if (!$hasPasskey): ?>
        <div id="registerSection">
            <p class="small text-light">Perangkat ini belum didaftarkan. Masukkan password Superadmin untuk mengaktifkan Passkey (Sidik Jari / Face ID).</p>
            <input type="password" id="adminPassword" class="form-control bg-dark text-white border-secondary mb-3" placeholder="Password Superadmin">
            <button onclick="registerPasskey()" class="btn btn-glow w-100" id="btnReg">Daftarkan Perangkat Ini</button>
        </div>
    <?php else: ?>
        <div id="loginSection">
            <p class="small text-light mb-4">Gunakan Sidik Jari atau Face ID Anda untuk masuk.</p>
            <button onclick="loginPasskey()" class="btn btn-glow w-100" id="btnLog">Scan Biometrik</button>
        </div>
    <?php endif; ?>
</div>

<script>
lucide.createIcons();
const csrfTokenValue = <?= json_encode(
    csrfToken(),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;
const csrfHeaders = {
    'Content-Type': 'application/json',
    'X-CSRF-Token': csrfTokenValue
};

function bufferDecode(value) {
    return Uint8Array.from(atob(value.replace(/-/g, "+").replace(/_/g, "/")), c => c.charCodeAt(0));
}
function bufferEncode(value) {
    return btoa(String.fromCharCode.apply(null, new Uint8Array(value))).replace(/\+/g, "-").replace(/\//g, "_").replace(/=/g, "");
}

function showError(msg) {
    const box = document.getElementById('alertBox');
    box.textContent = msg;
    box.classList.remove('d-none');
}

async function registerPasskey() {
    const pwd = document.getElementById('adminPassword').value;
    if(!pwd) return showError('Password wajib diisi!');

    try {
        const res = await fetch('superadmin_passkey.php?action=get_create_args', {
            method: 'POST',
            credentials: 'include',
            headers: csrfHeaders,
            body: JSON.stringify({password: pwd})
        });
        const makeCredReq = await res.json();
        if(makeCredReq.error) throw new Error(makeCredReq.error);

        const req = makeCredReq.publicKey || makeCredReq;
        req.challenge = bufferDecode(req.challenge);
        req.user.id = bufferDecode(req.user.id);

        const credential = await navigator.credentials.create({ publicKey: req });

        const clientDataJSON = bufferEncode(credential.response.clientDataJSON);
        const attestationObject = bufferEncode(credential.response.attestationObject);

        const res2 = await fetch('superadmin_passkey.php?action=process_create', {
            method: 'POST',
            credentials: 'include',
            headers: csrfHeaders,
            body: JSON.stringify({
                clientDataJSON: clientDataJSON,
                attestationObject: attestationObject
            })
        });
        const out = await res2.json();
        if(out.success) {
            alert('Passkey berhasil didaftarkan! Halaman akan dimuat ulang.');
            location.reload();
        } else {
            throw new Error(out.error);
        }
    } catch (e) {
        showError(e.message || 'Gagal mendaftarkan Passkey. Pastikan perangkat mendukung.');
    }
}

async function loginPasskey() {
    try {
        const res = await fetch('superadmin_passkey.php?action=get_get_args', {
            method: 'POST',
            credentials: 'include',
            headers: csrfHeaders,
            body: '{}'
        });
        const getCredReq = await res.json();
        if(getCredReq.error) throw new Error(getCredReq.error);

        const req = getCredReq.publicKey || getCredReq;
        req.challenge = bufferDecode(req.challenge);
        if(req.allowCredentials) {
            for(let i=0; i<req.allowCredentials.length; i++) {
                req.allowCredentials[i].id = bufferDecode(req.allowCredentials[i].id);
            }
        }

        const credential = await navigator.credentials.get({ publicKey: req });

        const res2 = await fetch('superadmin_passkey.php?action=process_get', {
            method: 'POST',
            credentials: 'include',
            headers: csrfHeaders,
            body: JSON.stringify({
                id: bufferEncode(credential.rawId),
                clientDataJSON: bufferEncode(credential.response.clientDataJSON),
                authenticatorData: bufferEncode(credential.response.authenticatorData),
                signature: bufferEncode(credential.response.signature),
                userHandle: credential.response.userHandle ? bufferEncode(credential.response.userHandle) : null
            })
        });
        const out = await res2.json();
        if(out.success) {
            window.location.href = 'superadmin/dashboard.php';
        } else {
            throw new Error(out.error);
        }
    } catch (e) {
        showError(e.message || 'Gagal mengenali Passkey. Coba lagi.');
    }
}
</script>
</body>
</html>
