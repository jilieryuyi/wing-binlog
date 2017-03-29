<?php
$user_name = $_GET["name"];
$user_info = \Seals\Web\Logic\User::getInfo($user_name);
include "include/nav.php";
?>

  <!-- page content -->
        <div class="right_col" role="main">
          <div class="">

            <div class="page-title">
              <div class="title_left">
                <h3>User edit</h3>
              </div>
            </div>
            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2><?php echo $user_name; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <br>
                    <form id="demo-form2" data-parsley-validate="" class="form-horizontal form-label-left" novalidate="">

                      <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">User Name <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <input type="text" id="user_name" data="<?php echo $user_name; ?>" name="user_name" required="required" class="form-control col-md-7 col-xs-12" value="<?php echo $user_name; ?>">
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="last-name">Password <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <input data-is-encode="1" id="password" data="<?php echo $user_info["password"]; ?>" value="" type="text"  name="password" required="required" class="form-control col-md-7 col-xs-12">
                        </div>
                      </div>
<!--                      <div class="form-group">-->
<!--                        <label for="middle-name" class="control-label col-md-3 col-sm-3 col-xs-12">Middle Name / Initial</label>-->
<!--                        <div class="col-md-6 col-sm-6 col-xs-12">-->
<!--                          <input id="middle-name" class="form-control col-md-7 col-xs-12" type="text" name="middle-name">-->
<!--                        </div>-->
<!--                      </div>-->

                      <div class="ln_solid"></div>
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                          <button style="float: left;" type="button" onclick="updateUser(this)" class="btn btn-success">Save Update</button>
                          <a style="float: left;margin-top: 15px;margin-left: 12px;" href="role.add.php">Add Role</a>

                        </div>
                      </div>

                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- /page content -->
<script>
  function updateUser(dom)
  {
      if (!Wing.lock())
        return;

    showDoing(dom);

    var old_user  = $("#user_name").attr("data");
    var user_name = $("#user_name").val();
    var old_pass  = $("#password").attr("data");
    var password  = $("#password").val();

    $.ajax({
      type: "POST",
      data : {
        old_user  : old_user,
        user_name : user_name,
        old_pass  : old_pass,
        password  : password
      },
      url : "/services/user/self/update",
      success:function(msg){
        //window.location.reload();
      }
    });

  }
  $(document).ready(function(){
    window.setTimeout(function(){
      $('[name="password"]').attr("type","password");
      $('[name="password"]').val($('[name="password"]').attr("data"));
    },200);
  });
</script>
<?php include "include/footer.php";?>