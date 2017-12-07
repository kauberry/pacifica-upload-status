<?php
// @codingStandardsIgnoreFile
?>
    <!-- start global inserts -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <!-- <link rel="shortcut icon" href="/favicon.ico" type="image/vnd.microsoft.icon" /> -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
    <link rel="manifest" href="/manifest.json">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="theme-color" content="#ffffff">

      <!-- Base jquery -->
    <?php if($_SERVER['CI_ENV'] == 'development') : ?>
      <script src="/resources/scripts/jquery/jquery-3.2.1.js" type="text/javascript"></script>
      <script src="/resources/scripts/jquery-ui/jquery-ui.js" type="text/javascript"></script>
      <link rel="stylesheet" type="text/css" href="/resources/scripts/jquery-ui/jquery-ui.css" />
      <script src="/resources/scripts/moment.js" type="text/javascript"></script>
      <script src="/project_resources/scripts/moment-timezone-with-data-2012-2022.js" type="text/javascript"></script>
    <?php else: ?>
      <script src="/resources/scripts/jquery/jquery-3.2.1.min.js" type="text/javascript"></script>
      <script src="/resources/scripts/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
      <link rel="stylesheet" type="text/css" href="/resources/scripts/jquery-ui/jquery-ui.min.css" />
      <script src="/resources/scripts/moment.min.js" type="text/javascript"></script>
      <script src="/project_resources/scripts/moment-timezone-with-data-2012-2022.min.js" type="text/javascript"></script>
    <?php endif; ?>
      <!-- local JS -->
      <script type="text/javascript" src="/resources/scripts/utility_functions.js"></script>

      <!-- jquery plugins -->
      <script src="/resources/scripts/jquery-cookie/jquery.cookie.js" type="text/javascript"></script>

      <!--[if gte IE 5.5]>
      <link rel="stylesheet" type="text/css" href="/stylesheets/ie-specific.css" />
      <![endif]-->
    <!-- end global inserts -->
