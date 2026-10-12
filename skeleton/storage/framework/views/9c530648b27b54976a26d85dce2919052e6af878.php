<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e( $status ); ?> — <?php echo e( $title ); ?></title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font: 16px/1.5 system-ui, sans-serif; background: #f8fafc; color: #0f172a; }
        main { text-align: center; padding: 2rem; }
        h1 { margin: 0; font-size: 4rem; font-weight: 700; color: #475569; }
        p { margin: .5rem 0 0; color: #64748b; }
        @media (prefers-color-scheme: dark) { body { background: #0f172a; color: #f1f5f9; } h1 { color: #94a3b8; } p { color: #94a3b8; } }
    </style>
</head>
<body>
<main>
    <h1><?php echo e( $status ); ?></h1>
    <p><?php echo $__env->yieldContent('message'); ?></p>
</main>
</body>
</html>
<?php /**PATH:C:\var\www\PHP\slendie\framework/resources/views/errors/layout.blade.php|HASH:c7cb73428ee262cc5521c7ab0f29c2ec9d505c6d**/ ?>