<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Login — Shopee Profit Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo e(asset('css/hub.css')); ?>" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #4a0e1a 0%, #7f1d35 45%, #f8f9fb 45%);
            padding: 1.5rem;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(74, 14, 26, 0.25);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #6b1528, #b83256);
            color: #fff;
            padding: 2rem 1.75rem;
        }
        .login-header h1 { font-size: 1.35rem; font-weight: 700; margin: 0; }
        .login-body { padding: 1.75rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-chart-pie fa-2x mb-3 opacity-75"></i>
            <h1>Shopee Profit Hub</h1>
            <p class="small mb-0 opacity-85">Monitoring & kelola data toko Shopee</p>
        </div>
        <div class="login-body">
            <?php if($errors->any()): ?>
                <div class="alert alert-danger py-2 small"><?php echo e($errors->first()); ?></div>
            <?php endif; ?>
            <form method="POST" action="<?php echo e(route('login.post')); ?>">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="hub-form-label">Username</label>
                    <input type="text" name="username" class="hub-form-control" value="<?php echo e(old('username')); ?>" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="hub-form-label">Password</label>
                    <input type="password" name="password" class="hub-form-control" required>
                </div>
                <button type="submit" class="hub-btn hub-btn-primary w-100">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </button>
            </form>
        </div>
    </div>
</body>
</html>
<?php /**PATH D:\A. SHOPEE-7\resources\views/auth/login.blade.php ENDPATH**/ ?>