<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" value="<?php echo e(csrf_token()); ?>"/>
    <title><?php echo e(env('APP_NAME')); ?></title>
    <link href="<?php echo e(mix('css/app.css')); ?>" type="text/css" rel="stylesheet"/>
</head>
<body>
<?php if(Auth::check()): ?>
    <script>
        window.Laravel = <?php echo json_encode([
               'isLoggedin' => true,
               'user' => Auth::user()
           ]); ?>

    </script>
<?php else: ?>
    <script>
        window.Laravel = <?php echo json_encode([
                'isLoggedin' => false
            ]); ?>

    </script>
<?php endif; ?>
<div id="app">
</div>
<script src="<?php echo e(mix('js/app.js')); ?>" type="text/javascript"></script>
</body>
</html>
<?php /**PATH /Users/tejas/Desktop/Laravel_project/Jira/laravel-spa-vue3-auth-crud/resources/views/app.blade.php ENDPATH**/ ?>