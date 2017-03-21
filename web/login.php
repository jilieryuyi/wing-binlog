<?php
$error_info = $appid = $token = "";
if (isset($_POST["user_name"]) && isset($_POST["password"])) {
    $user_name = $_POST["user_name"];
    $password  = $_POST["password"];
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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>


    <!-- General meta information -->
    <title>Login Wing-binlog</title>
    <meta name="keywords" content="" />
    <meta name="description" content="" />
    <meta name="robots" content="index, follow" />
    <meta charset="utf-8" />
    <!-- // General meta information -->


    <!-- Load Javascript -->
    <script type="text/javascript" src="js/jquery-3.1.1.min.js"></script>
    <script type="text/javascript" src="js/rainbows.js"></script>
    <script type="text/javascript" src="js/js.cookie.js"></script>
    <!-- // Load Javascipt -->

    <!-- Load stylesheets -->
    <link type="text/css" rel="stylesheet" href="css/style.css" media="screen" />
    <!-- // Load stylesheets -->

    <script type="text/javascript">


        $(document).ready(function(){

            $("#submit1").hover(
                function() {
                    $(this).animate({"opacity": "0"}, "slow");
                },
                function() {
                    $(this).animate({"opacity": "1"}, "slow");
                });
        });
        var appid = "<?php echo $appid; ?>";
        var token = "<?php echo $token; ?>";

        if (appid != "" && token != "") {
            Cookies.set('wing-binlog-appid', appid, { expires: 1 });
            Cookies.set('wing-binlog-token', token, { expires: 1 });
            window.location.href = "/";
        } else {
            appid = Cookies.get('wing-binlog-appid');
            token = Cookies.get('wing-binlog-token');

            if (appid && token && typeof appid != "undefined" && typeof token != "undefined")
                window.location.href = "/";
        }


    </script>

</head>
<body>
<form action="" method="post">
<div id="wrapper">
    <div id="wrappertop"></div>

    <div id="wrappermiddle">

        <h2>登录<label style="font-size: 12px; color: #f00; margin-left: 6px;"><?php echo $error_info; ?></label></h2>

        <div id="username_input">

            <div id="username_inputleft"></div>

            <div id="username_inputmiddle">

                    <input type="text" name="user_name" id="url" placeholder="用户名" />
                    <img id="url_user" src="./images/mailicon.png" alt="">
            </div>

            <div id="username_inputright"></div>

        </div>

        <div id="password_input">

            <div id="password_inputleft"></div>

            <div id="password_inputmiddle">
                    <input type="password" name="password" id="url" placeholder="密码" />
                    <img id="url_password" src="./images/passicon.png" alt="">
            </div>

            <div id="password_inputright"></div>

        </div>

        <div id="submit">
                <input type="submit" style="width: 300px; height: 40px; background: #4B780A; color: #fff; font-weight: bold; font-size: 20px;" id="submit2" value="登 录">
        </div>


        <div id="links_left">

            <a>忘记密码？</a>

        </div>

        <div id="links_right"><a>注册新用户</a></div>

    </div>

    <div id="wrapperbottom"></div>

    <div id="powered">
        <p>Powered by <a href="http://www.itdfy.com/">yuyi</a></p>
    </div>
</div>
</form>
</body>
</html>