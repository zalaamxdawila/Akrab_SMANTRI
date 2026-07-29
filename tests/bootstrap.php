<?php

require_once dirname(__DIR__) . '/config/environment.php';
require_once dirname(__DIR__) . '/config/error_handling.php';
require_once dirname(__DIR__) . '/config/observability.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/database/MigrationRunner.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/config/csrf.php';
require_once dirname(__DIR__) . '/app/Security/ActorContext.php';
require_once dirname(__DIR__) . '/app/Security/ActorContextResolver.php';
require_once dirname(__DIR__) . '/app/Security/ImpersonationPolicy.php';
require_once dirname(__DIR__) . '/app/Security/ImpersonationMutationAudit.php';
require_once dirname(__DIR__) . '/app/Security/ImpersonationService.php';
require_once dirname(__DIR__) . '/config/authorization.php';
require_once dirname(__DIR__) . '/config/validation.php';
require_once dirname(__DIR__) . '/config/csv.php';
require_once dirname(__DIR__) . '/config/integrity.php';
require_once dirname(__DIR__) . '/config/risk.php';
require_once dirname(__DIR__) . '/config/clinical.php';
require_once __DIR__ . '/Integration/Sprint28Fixture.php';
