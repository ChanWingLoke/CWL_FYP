<?php 
require_once 'app/init.php'; 

// Redirect if already logged in
if ($Ouser->is_login()) {
  header("location:index.php");
  exit;
}

// Capture error and clear it immediately (Flash message behavior)
$error = $_SESSION['login_error'] ?? null;
if ($error) {
    unset($_SESSION['login_error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in form</title>
    
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/style.css" type="text/css" />
</head>
<body>
    <div class="header text-center mb-5">
        <div class="container-fluid">
            <div class="login-form-bx">
                <div class="row">
                    
                    <div class="col-md-6 cards">
                        <div class="authincation-content">
                            
                            <a class="login-logo" href="#">
                                <img src="dist/img/log.jpg" alt="Logo" height="200" style="width: auto;">
                            </a>
                            
                            <div class="mb-4"></div>

                            <form action="app/action/login.php" method="post">
                                
                                <?php if ($error): ?>
                                    <div class='alert alert-danger text-center'>
                                        <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="form-group">    
                                    <label class="mb-2 tag">
                                        <strong>Name</strong>
                                    </label>
                                    <input type="text" name="username" class="form-control input" placeholder="Enter your username" required />
                                </div>

                                <div class="form-group">
                                    <label class="mb-2 tag">
                                        <strong>Password</strong>
                                    </label>
                                    <input type="password" name="password" class="form-control input" placeholder="Enter your password" required />
                                </div>

                                <div class="form-row d-flex justify-content-between mt-4 mb-2">
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox ml-1">
                                            <input type="checkbox" class="form-check-input" id="basic_checkbox_1">
                                            <label class="form-check-label" for="basic_checkbox_1">Remember me</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center">
                                    <button type="submit" name="admin_login" class="btn btn-primary btn-block">Login</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-5 d-flex box-skew1">
                        </div>

                </div></div></div></div></body>
</html>