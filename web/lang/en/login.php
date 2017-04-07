<?php
$error_info = $appid = $token = "";
if (isset($_POST["Username"]) && isset($_POST["Password"])) {
  $user_name = $_POST["Username"];
  $password  = $_POST["Password"];
  $user      = new \Seals\Web\Logic\User($user_name);
  $success   = $user->checkPassword($password);
  if ($success) {
    list($appid, $token) = $user->setToken();
  } else {
    $error_info = "用户名或密码错误";
  }
  unset($user, $user_name, $password);
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Wing Binlog</title>

    <!-- Bootstrap -->
    <link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- Animate.css -->
    <link href="vendors/animate.css/animate.min.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="build/css/custom.min.css" rel="stylesheet">
    <link href="css/public.css" rel="stylesheet">

    <script type="text/javascript" src="js/js.cookie.js"></script>
    <script type="text/javascript" src="js/history.js"></script>

    <script>
      var appid = "<?php echo $appid; ?>";
      var token = "<?php echo $token; ?>";

      if (appid != "" && token != "") {
        Cookies.set('wing-binlog-appid', appid, { expires: 1 });
        Cookies.set('wing-binlog-token', token, { expires: 1 });
        //window.location.href = "index.php";
        History.back();
      }
      var error_info = "<?php echo $error_info; ?>";
      if (error_info != "")
        alert(error_info);
    </script>
  </head>

  <body class="login">
    <div>
      <a class="hiddenanchor" id="signup"></a>
      <a class="hiddenanchor" id="signin"></a>

      <div class="login_wrapper">
        <div class="animate form login_form">
          <section class="login_content">
            <form id="form" action="login.php" method="post">
              <h1>Login Form</h1>
              <div>
                <input type="text" class="form-control" placeholder="Username" name="Username" required="" />
              </div>
              <div>
                <input type="password" class="form-control" placeholder="Password" name="Password" required="" />
              </div>
              <div>
                <a class="btn btn-default submit" onclick="document.getElementById('form').submit();">Log in</a>
                <a class="reset_pass" href="#">Lost your password?</a>
              </div>

              <div class="clearfix"></div>

              <div class="separator">
                <p class="change_link">New to site?
                  <a href="#signup" class="to_register"> Create Account </a>
                </p>

                <div class="clearfix"></div>
                <br />

                <div>
                  <h1><i style="    border: #ccc solid 1px;
    border-radius: 30px;
    width: 30px;
    height: 30px;" class="fa fa-paw"></i> Wing Binlog</h1>
                  <p>©<?php echo date("Y-m-d"); ?> All Rights Reserved. Wing Binlog</p>
                </div>
              </div>
            </form>
          </section>
        </div>

        <div id="register" class="animate form registration_form">
          <section class="login_content">
            <form action="">
              <h1>Create Account</h1>
              <div>
                <input type="text" class="form-control" placeholder="Username" required="" />
              </div>
              <div>
                <input type="email" class="form-control" placeholder="Email" required="" />
              </div>
              <div>
                <input type="password" class="form-control" placeholder="Password" required="" />
              </div>
              <div>
                <a class="btn btn-default submit" href="##">Submit</a>
              </div>

              <div class="clearfix"></div>

              <div class="separator">
                <p class="change_link">Already a member ?
                  <a href="#signin" class="to_register"> Log in </a>
                </p>

                <div class="clearfix"></div>
                <br />

                <div>
                  <h1><i class="fa fa-paw"></i> Gentelella Alela!</h1>
                  <p>©2016 All Rights Reserved. Gentelella Alela! is a Bootstrap 3 template. Privacy and Terms</p>
                </div>
              </div>
            </form>
          </section>
        </div>
      </div>
    </div>
  </body>
</html>
