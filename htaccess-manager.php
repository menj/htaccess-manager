<?php

// Start session to use session variables
session_start();

// === SECURITY FEATURES ===

// The secret password that grants access to the manager
$secretPassword = 'oozGHqmDq';  // This password can be changed as needed

// Generate and store a nonce for CSRF protection
if (!isset($_SESSION['nonce'])) {
    $_SESSION['nonce'] = bin2hex(random_bytes(32));
}

$errorMessage = null;

// Function to recursively list all .htaccess files
function listHtaccessFiles($dir, &$results = []) {
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            if ($value == '.htaccess') {
                $results[] = ['path' => $path, 'size' => filesize($path)];
            }
        } else if ($value != "." && $value != "..") {
            listHtaccessFiles($path, $results);
        }
    }

    return $results;
}

// Define the root directory to begin the search
$rootDirectory = $_SERVER['DOCUMENT_ROOT'];
$htaccessFiles = listHtaccessFiles($rootDirectory);

// Ensure the nonce matches to protect against CSRF attacks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['nonce']) || $_POST['nonce'] !== $_SESSION['nonce'])) {
    die('Invalid request.');
}

// Handle deletion based on action
if (isset($_GET['action']) && $_GET['action'] === 'delete_htaccess' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['executeDeletion'])) {
        $action = $_POST['deleteAction'];

        // Delete the .htaccess files
        if ($action === "Delete Only .htaccess Files" || $action === "Delete Both .htaccess Files and This Script") {
            foreach ($htaccessFiles as $file) {
                unlink($file['path']);
            }
        }

        // Delete this script itself
        if ($action === "Delete Only This Script" || $action === "Delete Both .htaccess Files and This Script") {
            unlink(__FILE__);
        }

        // Display the action result
        if ($action === "Delete Only .htaccess Files") {
            die('.htaccess files have been deleted.');
        } elseif ($action === "Delete Only This Script") {
            die('Script has been deleted.');
        } else {
            die('Both .htaccess files and script have been deleted.');
        }
    }
} else {
    // Password check for accessing manager
    if (isset($_POST['password'])) {
        if ($secretPassword !== $_POST['password']) {
            $errorMessage = "Incorrect password. Please try again.";
        } else {
            header('Location: ?action=delete_htaccess');
            exit;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>.htaccess Manager</title>
    <style>
        /* CSS styling for the page layout and appearance. Edit this for visual changes. */
        body {
            background-color: #f1f1f1;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 30px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login {
            background-color: #fff;
            padding: 24px 24px 46px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 1px 3px rgba(0,0,0,.13);
            border-radius: 5px;
        }
        /* Styling for the header */
        .login h2 {
            margin: 0 0 24px;
            font-size: 24px;
        }
        /* Styling for labels */
        .login label {
            font-weight: 700;
            margin-bottom: 4px;
        }
        /* Styling for input fields */
        .login input[type="password"], .login input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 24px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        /* Styling for the submit button */
        .login input[type="submit"] {
            background-color: #007cba;
            border: none;
            color: #fff;
            cursor: pointer;
            font-size: 14px;
        }
        /* Styling for error messages */
        .error-message {
            background-color: #feefef;
            border: 1px solid #c60c30;
            padding: 12px;
            color: #c60c30;
            margin-bottom: 24px;
            border-radius: 4px;
        }
        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        /* Styling for table data and headers */
        th, td {
            padding: 8px 12px;
            border: 1px solid #ddd;
        }
        /* Styling for table headers */
        th {
            background-color: #f7f7f7;
        }
    </style>
</head>
<body>

<?php 
// Display options based on the action
if (isset($_GET['action']) && $_GET['action'] === 'delete_htaccess'): 
?>

    <div class="login">
        <h2>Delete .htaccess files</h2>
        <form method="post">
            <?php 
            // Display all found .htaccess files
            if (count($htaccessFiles) > 0): 
            ?>
                <p>Found .htaccess files:</p>
                <table>
                    <thead>
                        <tr>
                            <th>Path</th>
                            <th>Size (bytes)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Iterate through and display each .htaccess file
                        foreach ($htaccessFiles as $file): 
                        ?>
                            <tr>
                                <td><?= htmlentities($file['path']) ?></td>
                                <td><?= number_format($file['size']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Options for what the user wants to delete -->
                <select name="deleteAction">
                    <option value="Delete Only .htaccess Files">Delete Only .htaccess Files</option>
                    <option value="Delete Only This Script">Delete Only This Script</option>
                    <option value="Delete Both .htaccess Files and This Script">Delete Both .htaccess Files and This Script</option>
                </select>
                <input type="hidden" name="executeDeletion" value="1">
                <input type="hidden" name="nonce" value="<?= $_SESSION['nonce'] ?>">
                <input type="submit" value="Execute Action">
            <?php 
            // If no .htaccess files are found, display a message
            else: 
            ?>
                <p>No .htaccess files found.</p>
            <?php endif; ?>
        </form>
    </div>
<?php 
// If not in delete action, display the login page
else: 
?>
    <div class="login">
        <h2>.htaccess Manager Login</h2>
        <?php 
        // Display error message if password is wrong
        if ($errorMessage): 
        ?>
            <div class="error-message">
                <?= htmlentities($errorMessage) ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
            <input type="hidden" name="nonce" value="<?= $_SESSION['nonce'] ?>">
            <input type="submit" value="Log In">
        </form>
    </div>
<?php endif; ?>

</body>
</html>
