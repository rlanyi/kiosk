<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title><?php echo (isset($config['title']) ? $config['title'] : 'Video Player'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Robert Lanyi">

    <link href="/css/main.css" rel="stylesheet">
    <link href="/css/bootstrap/bootstrap.css" rel="stylesheet">
    <link href="/css/bootstrap/bootstrap-responsive.css" rel="stylesheet">

    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <link rel="shortcut icon" href="../assets/ico/favicon.ico">
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="../assets/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="../assets/ico/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="../assets/ico/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="../assets/ico/apple-touch-icon-57-precomposed.png">
  </head>

  <body>
    <?php require(__DIR__ . '/topnav.html.php'); ?>

    <div class="container">
    <?php if (isset($_SESSION['msg']) && is_array($_SESSION['msg'])): ?>
      <p>&nbsp;</p>
      <?php foreach ($_SESSION['msg'] as $msg): ?>
      <p class="alert alert-<?php echo $msg['level'] ?>">
        <?php echo $msg['message'] ?>
      </p>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php
      if (isset($params['content'])):
        echo $params['content'];
      endif;
    ?>

    </div>

    <!-- Placed at the end of the document so the pages load faster -->
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="js/vendor/jquery/jquery-1.8.1.min.js"><\/script>')</script>

    <script src="/js/bootstrap/bootstrap-transition.js"></script>
    <script src="/js/bootstrap/bootstrap-transition.js"></script>
    <script src="/js/bootstrap/bootstrap-alert.js"></script>
    <script src="/js/bootstrap/bootstrap-modal.js"></script>
    <script src="/js/bootstrap/bootstrap-dropdown.js"></script>
    <script src="/js/bootstrap/bootstrap-scrollspy.js"></script>
    <script src="/js/bootstrap/bootstrap-tab.js"></script>
    <script src="/js/bootstrap/bootstrap-tooltip.js"></script>
    <script src="/js/bootstrap/bootstrap-popover.js"></script>
    <script src="/js/bootstrap/bootstrap-button.js"></script>
    <script src="/js/bootstrap/bootstrap-collapse.js"></script>
    <script src="/js/bootstrap/bootstrap-carousel.js"></script>
    <script src="/js/bootstrap/bootstrap-typeahead.js"></script>
    <script src="/js/global.js"></script>

    <script>
      $(function() {
        $('.btn').on('click', function () {
          var $btn = $(this).button('loading');
        });
      });
    </script>

  </body>
</html>
