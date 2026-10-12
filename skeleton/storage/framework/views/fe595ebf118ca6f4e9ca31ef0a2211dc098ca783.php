<!doctype html>
<html lang="<?php echo e( config('app.locale', 'pt') ); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', config('app.name', 'Slendie')); ?></title>
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body>
    <?php echo $__env->yieldContent('content'); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH:C:\var\www\PHP\slendie\skeleton\resources\views/layouts/app.blade.php|HASH:c5bfd5c11083c110aaabf6a8a806868d7d976a39**/ ?>