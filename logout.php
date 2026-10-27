<?php
// 1. Initialize session memory
session_start();

// 2. Unset all session variables ($_SESSION['user_id'], $_SESSION['role'], etc.)
$_SESSION = array();

// 3. Delete the session cookie from the browser if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 4. Destroy the session on the server
session_destroy();

// 5. Redirect user to the login page
header("Location: login.php");
exit();
?>