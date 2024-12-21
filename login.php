<?php
require_once 'config/config.php';

// Redirect to index.php with login modal open
header('Location: index.php?action=login');
exit; 