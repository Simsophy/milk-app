<?php
declare(strict_types=1);

// Use a relative redirect so it works whether this project is hosted at
// domain root (/) or inside a subfolder (e.g. /Milk-app/).
header('Location: frontend/login.html');
exit;
