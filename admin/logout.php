<?php
// admin/logout.php - Logout Handler
require_once('config.php');

if (isAdminLoggedIn()) {
    logAdminActivity($_SESSION['admin_id'], 'logout', null, null, 'Admin logged out');
}

adminLogout();
header('Location: login.php?logout=1');
exit;
?>
