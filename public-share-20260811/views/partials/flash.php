<?php

declare(strict_types=1);

if (!empty($successMessage)): ?>
    <div class="alert alert-success alert-auto-dismiss"><?= escape_output($successMessage) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-auto-dismiss"><?= escape_output($error) ?></div>
<?php endif; ?>
