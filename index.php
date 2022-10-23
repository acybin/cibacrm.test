<?php

define('MAINTENANCE', false);

if (MAINTENANCE) {
    header('503 Service Unavailable');
    echo '<p>CIBA временно не работает, но скоро вернется к вам снова!</p>';
    exit();
}

require dirname(__FILE__) . '/path.php';