<!DOCTYPE html>
<html lang="en" manifest123="cache.manifest">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Wing Binlog </title>
    <link href="css/public.css" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">

    <!-- bootstrap-progressbar -->
    <link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
    <!-- JQVMap -->
    <link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
    <!-- bootstrap-daterangepicker -->
    <link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="build/css/custom.css" rel="stylesheet">
    <link href="css/index.css" rel="stylesheet">
    <link href="vendors/switchery/dist/switchery.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="vendors/jquery/dist/jquery.min.js"></script>

    <!-- Bootstrap -->
    <script src="vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- FastClick -->
    <script src="vendors/fastclick/lib/fastclick.js"></script>
    <!-- NProgress -->
    <script src="vendors/nprogress/nprogress.js"></script>
    <!-- Chart.js -->
    <script src="vendors/Chart.js/dist/Chart.min.js"></script>
    <!-- gauge.js -->
    <script src="vendors/gauge.js/dist/gauge.min.js"></script>
    <!-- bootstrap-progressbar -->
    <script src="vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>
    <!-- iCheck -->
    <script src="vendors/iCheck/icheck.min.js"></script>
    <!-- jQuery Sparklines -->
    <script src="vendors/jquery-sparkline/dist/jquery.sparkline.min.js"></script>
    <!-- Skycons -->
    <script src="vendors/skycons/skycons.js"></script>
    <!-- Flot -->
    <script src="vendors/Flot/jquery.flot.js"></script>
    <script src="vendors/Flot/jquery.flot.pie.js"></script>
    <script src="vendors/Flot/jquery.flot.time.js"></script>
    <script src="vendors/Flot/jquery.flot.stack.js"></script>
    <script src="vendors/Flot/jquery.flot.resize.js"></script>
    <!-- Flot plugins -->
    <script src="vendors/flot.orderbars/js/jquery.flot.orderBars.js"></script>
    <script src="vendors/flot-spline/js/jquery.flot.spline.min.js"></script>
    <script src="vendors/flot.curvedlines/curvedLines.js"></script>
    <!-- DateJS -->
    <script src="vendors/DateJS/build/date.js"></script>
    <!-- JQVMap -->
    <script src="vendors/jqvmap/dist/jquery.vmap.js"></script>
    <script src="vendors/jqvmap/dist/maps/jquery.vmap.world.js"></script>
    <script src="vendors/jqvmap/examples/js/jquery.vmap.sampledata.js"></script>
    <!-- bootstrap-daterangepicker -->
    <script src="vendors/moment/min/moment.min.js"></script>
    <script src="vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <!-- Switchery -->
    <script src="vendors/switchery/dist/switchery.min.js"></script>
    <script src="js/datetime.js"></script>
    <script src="js/js.cookie.js"></script>
    <script src="js/hack.js"></script>
    <script src="js/string.js"></script>

    <script src="js/warray.js"></script>
    <script src="js/wobject.js"></script>

    <script src="js/wing.js"></script>
    <script src="js/history.js"></script>
    <script src="js/full.js"></script>

    <script>
        function showDoing(dom) {
            var old_html = $(dom).html();
            $(dom).addClass("disable").html("Doing...");
            window.setTimeout(function(){
                $(dom).removeClass("disable").html("Success");
                //unlock after 3 seconds timeout
                Wing.unlock();
                window.setTimeout(function(){
                    $(dom).html(old_html);
                },1000);
            },3000);
        }

        function setFull() {
            screenfull && screenfull.toggle();
        }
    </script>
</head>

<body class="nav-md">
<div class="container body">
    <div class="main_container">
<div class="col-md-3 left_col" style="position: fixed; top:0px;">
    <div class="left_col scroll-view">
        <div class="navbar nav_title" style="border: 0;">
            <a href="/" class="site_title"><i class="fa fa-paw"></i> <span>Wing Binlog</span></a>
        </div>

        <div class="clearfix"></div>

        <!-- menu profile quick info -->
        <div class="profile clearfix">
            <div class="profile_pic">
                <img src="images/img.jpg" alt="..." class="img-circle profile_img">
            </div>
            <div class="profile_info">
                <span>Welcome,</span>
                <h2><?php echo \Seals\Web\Logic\User::getUserName(); ?></h2>
            </div>
        </div>
        <!-- /menu profile quick info -->

        <br />

        <!-- sidebar menu -->
        <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
            <div class="menu_section">
                <h3>General</h3>
                <ul class="nav side-menu">
                    <li class="active"><a><i class="fa fa-home"></i> Home <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu" style="display: block">
                            <li><a href="/">Servers</a></li>
                            <li><a href="/users.php">Users</a></li>
                            <li><a href="/roles.php">Roles</a></li>
                        </ul>
                    </li>
                    <li><a><i class="fa fa-edit"></i> Other <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="logs.php">Logs</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        <!-- /sidebar menu -->

        <!-- /menu footer buttons -->
        <div class="sidebar-footer hidden-small">
            <a href="user.update.php?name=<?php echo \Seals\Web\Logic\User::getUserName(); ?>" data-toggle="tooltip" data-placement="top" title="Settings">
                <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
            </a>
            <a data-toggle="tooltip" data-placement="top" title="FullScreen">
                <span class="glyphicon glyphicon-fullscreen" aria-hidden="true" onclick="setFull()"></span>
            </a>
            <a data-toggle="tooltip" data-placement="top" title="Lock">
                <span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span>
            </a>
            <a data-toggle="tooltip" data-placement="top" title="Logout" href="login.php">
                <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
            </a>
        </div>
        <!-- /menu footer buttons -->
    </div>
</div>

<div class="top_nav" style="position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    margin-left: 0;
    padding-left: 230px;
    z-index: 9999;">
    <div class="nav_menu">
        <nav>
            <div class="nav toggle">
                <a id="menu_toggle"><i class="fa fa-bars"></i></a>
            </div>

            <ul class="nav navbar-nav navbar-right">
                <li class="">
                    <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <img src="images/img.jpg" alt=""><?php echo \Seals\Web\Logic\User::getUserName(); ?>
                        <span class=" fa fa-angle-down"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-usermenu pull-right">
<!--                        <li><a href="javascript:;"> Profile</a></li>-->
                        <li>
                            <a href="user.update.php?name=<?php echo \Seals\Web\Logic\User::getUserName(); ?>">
<!--                                <span class="badge bg-red pull-right">50%</span>-->
<!--                                <span></span>-->
                                Settings
                            </a>
                        </li>
                        <li><a href="help.php">Help</a></li>
                        <li><a href="login.php"><i class="fa fa-sign-out pull-right"></i> Log Out</a></li>
                    </ul>
                </li>

<!--                <li role="presentation" class="dropdown">-->
<!--                    <a href="javascript:;" class="dropdown-toggle info-number" data-toggle="dropdown" aria-expanded="false">-->
<!--                        <i class="fa fa-envelope-o"></i>-->
<!--                        <span class="badge bg-green">6</span>-->
<!--                    </a>-->
<!--                    <ul id="menu1" class="dropdown-menu list-unstyled msg_list" role="menu">-->
<!--                        <li>-->
<!--                            <a>-->
<!--                                <span class="image"><img src="images/img.jpg" alt="Profile Image" /></span>-->
<!--                                <span>-->
<!--                          <span>John Smith</span>-->
<!--                          <span class="time">3 mins ago</span>-->
<!--                        </span>-->
<!--                                <span class="message">-->
<!--                          Film festivals used to be do-or-die moments for movie makers. They were where...-->
<!--                        </span>-->
<!--                            </a>-->
<!--                        </li>-->
<!--                        <li>-->
<!--                            <a>-->
<!--                                <span class="image"><img src="images/img.jpg" alt="Profile Image" /></span>-->
<!--                                <span>-->
<!--                          <span>John Smith</span>-->
<!--                          <span class="time">3 mins ago</span>-->
<!--                        </span>-->
<!--                                <span class="message">-->
<!--                          Film festivals used to be do-or-die moments for movie makers. They were where...-->
<!--                        </span>-->
<!--                            </a>-->
<!--                        </li>-->
<!--                        <li>-->
<!--                            <a>-->
<!--                                <span class="image"><img src="images/img.jpg" alt="Profile Image" /></span>-->
<!--                                <span>-->
<!--                          <span>John Smith</span>-->
<!--                          <span class="time">3 mins ago</span>-->
<!--                        </span>-->
<!--                                <span class="message">-->
<!--                          Film festivals used to be do-or-die moments for movie makers. They were where...-->
<!--                        </span>-->
<!--                            </a>-->
<!--                        </li>-->
<!--                        <li>-->
<!--                            <a>-->
<!--                                <span class="image"><img src="images/img.jpg" alt="Profile Image" /></span>-->
<!--                                <span>-->
<!--                          <span>John Smith</span>-->
<!--                          <span class="time">3 mins ago</span>-->
<!--                        </span>-->
<!--                                <span class="message">-->
<!--                          Film festivals used to be do-or-die moments for movie makers. They were where...-->
<!--                        </span>-->
<!--                            </a>-->
<!--                        </li>-->
<!--                        <li>-->
<!--                            <div class="text-center">-->
<!--                                <a>-->
<!--                                    <strong>See All Alerts</strong>-->
<!--                                    <i class="fa fa-angle-right"></i>-->
<!--                                </a>-->
<!--                            </div>-->
<!--                        </li>-->
<!--                    </ul>-->
<!--                </li>-->
            </ul>
        </nav>
    </div>
</div>