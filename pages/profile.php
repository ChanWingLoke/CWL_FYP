<?php
// pages/profile.php

// 1. Initialize & Auth Check
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'];

// 2. Database Connection (Ensure $db/$pdo is available)
$db = null;
if (isset($pdo) && $pdo) { $db = $pdo; }
elseif (isset($obj) && isset($obj->pdo)) { $db = $obj->pdo; }

// 3. Handle Form Submission (Update Profile)
$flash = ['type' => null, 'msg' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_info') {
            // Update General Info
            $username = trim($_POST['username']);
            $photo    = trim($_POST['photo']);
            
            if (empty($username)) throw new Exception("Username cannot be empty.");

            // Check if username is taken by another user
            $chk = $db->prepare("SELECT COUNT(*) FROM user WHERE username = :u AND id != :id");
            $chk->execute([':u' => $username, ':id' => $user_id]);
            if ($chk->fetchColumn() > 0) throw new Exception("Username already exists.");

            $sql = "UPDATE user SET username = :u, photo = :p WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':u' => $username, ':p' => $photo, ':id' => $user_id]);

            $flash = ['type' => 'success', 'msg' => 'Profile details updated successfully.'];

        } elseif ($action === 'update_pass') {
            // Update Password
            $new_pass = $_POST['password'];
            $cnf_pass = $_POST['c_password'];

            if (strlen($new_pass) < 6) throw new Exception("Password must be at least 6 characters.");
            if ($new_pass !== $cnf_pass) throw new Exception("Passwords do not match.");

            // Note: Using MD5 to match your existing system. 
            // Ideally, migrate to password_hash() in the future.
            $sql = "UPDATE user SET password = MD5(:p) WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':p' => $new_pass, ':id' => $user_id]);

            $flash = ['type' => 'success', 'msg' => 'Password changed successfully.'];
        }

    } catch (Exception $e) {
        $flash = ['type' => 'danger', 'msg' => $e->getMessage()];
    }
}

// 4. Fetch User Data
$user = null;
if ($db) {
    $stmt = $db->prepare("SELECT * FROM user WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);
}

// 5. Resolve Avatar (Logic adapted from header.php)
$publicDefault = 'dist/img/log.jpg'; 
$avatar = $publicDefault;
if ($user && !empty($user->photo)) {
    // If it's a full URL (http...) use it, otherwise check local file
    if (filter_var($user->photo, FILTER_VALIDATE_URL)) {
        $avatar = $user->photo;
    } else {
        $photoRel = ltrim($user->photo, '/\\');
        $projectRoot = realpath(__DIR__ . '/..'); // Adjust path based on where profile.php sits
        $photoFs  = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $photoRel);
        // Simple check: if file doesn't exist locally, assume it might be a relative web path
        $avatar = $photoRel; 
    }
}
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6"><h1>User Profile</h1></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">User Profile</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <?php if ($flash['msg']): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($flash['msg']) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($user): ?>
            <div class="row">
                <div class="col-md-3">
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                            <div class="text-center">
                                <img class="profile-user-img img-fluid img-circle"
                                     src="<?= htmlspecialchars($avatar) ?>"
                                     alt="User profile picture"
                                     style="width:100px; height:100px; object-fit:cover;">
                            </div>

                            <h3 class="profile-username text-center mt-3"><?= htmlspecialchars($user->username) ?></h3>
                            <p class="text-muted text-center"><?= isset($user->user_role) ? ucfirst($user->user_role) : 'User' ?></p>

                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <b>User ID</b> <a class="float-right">#<?= $user->id ?></a>
                                </li>
                                <li class="list-group-item">
                                    <b>Joined</b> <a class="float-right"><?= isset($user->added_date) ? date('M Y', strtotime($user->added_date)) : 'N/A' ?></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Account Status</h3>
                        </div>
                        <div class="card-body">
                            <strong><i class="fas fa-user-shield mr-1"></i> Role</strong>
                            <p class="text-muted">
                                You are currently logged in as a 
                                <span class="badge badge-<?= (isset($user->user_role) && $user->user_role == 'admin') ? 'danger' : 'info' ?>">
                                    <?= isset($user->user_role) ? strtoupper($user->user_role) : 'USER' ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header p-2">
                            <ul class="nav nav-pills">
                                <li class="nav-item"><a class="nav-link active" href="#settings" data-toggle="tab">General Settings</a></li>
                                <li class="nav-item"><a class="nav-link" href="#security" data-toggle="tab">Security</a></li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                
                                <div class="active tab-pane" id="settings">
                                    <form class="form-horizontal" method="POST" action="">
                                        <input type="hidden" name="action" value="update_info">
                                        
                                        <div class="form-group row">
                                            <label for="inputName" class="col-sm-2 col-form-label">Username</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="inputName" name="username" value="<?= htmlspecialchars($user->username) ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group row">
                                            <label for="inputPhoto" class="col-sm-2 col-form-label">Photo URL</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="inputPhoto" name="photo" value="<?= htmlspecialchars($user->photo ?? '') ?>" placeholder="https://example.com/my-photo.jpg">
                                                <small class="text-muted">Paste a direct image link or a local path (e.g., dist/img/avatar.png)</small>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="offset-sm-2 col-sm-10">
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane" id="security">
                                    <form class="form-horizontal" method="POST" action="">
                                        <input type="hidden" name="action" value="update_pass">

                                        <div class="alert alert-info">
                                            <i class="icon fas fa-info"></i> Leaving this page will not save unsaved changes.
                                        </div>

                                        <div class="form-group row">
                                            <label for="newPass" class="col-sm-3 col-form-label">New Password</label>
                                            <div class="col-sm-9">
                                                <input type="password" class="form-control" id="newPass" name="password" placeholder="New Password" required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="confPass" class="col-sm-3 col-form-label">Confirm Password</label>
                                            <div class="col-sm-9">
                                                <input type="password" class="form-control" id="confPass" name="c_password" placeholder="Confirm New Password" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group row">
                                            <div class="offset-sm-3 col-sm-9">
                                                <button type="submit" class="btn btn-danger">Update Password</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-danger">User data could not be loaded.</div>
            <?php endif; ?>
        </div>
    </section>
</div>