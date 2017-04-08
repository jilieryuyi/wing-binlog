<?php
$user_name = $_GET["name"];
$user_info = \Seals\Web\Logic\User::getInfo($user_name);
include  __DIR__."/include/nav.php";
?>

  <!-- page content -->
        <div class="right_col" role="main">
          <div class="">

            <div class="page-title">
              <div class="title_left">
                <h3>__LANG(User edit)</h3>
              </div>
            </div>
            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2><?php echo $user_name; ?></h2>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <br>
                    <form id="demo-form2" data-parsley-validate="" class="form-horizontal form-label-left" novalidate="">

                      <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">__LANG(User Name) <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <input type="text" id="user_name" data="<?php echo $user_name; ?>" name="user_name" required="required" class="form-control col-md-7 col-xs-12" value="<?php echo $user_name; ?>">
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="last-name">__LANG(Password) <span class="required">*</span>
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

                      <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">__LANG(Role) <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <select id="user_role" class="form-control">
                            <?php $roles = \Seals\Web\Logic\User::getAllRoles();
                            foreach ($roles as $role) {
                            ?>
                            <option><?php echo $role["name"]; ?></option>
                            <?php } ?>
                          </select>
                        </div>
                      </div>
                      <div class="ln_solid"></div>
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                          <button style="float: left;" type="button" onclick="updateUser(this)" class="btn btn-success">__LANG(Save Update)</button>
                          <a style="float: left;margin-top: 15px;margin-left: 12px;" href="role.add.php">__LANG(Add Role)</a>

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
    var role      = $("#user_role option:selected").html();

    console.log({
      old_user  : old_user,
      user_name : user_name,
      old_pass  : old_pass,
      password  : password,
      role      : role
    });
    $.ajax({
      type: "POST",
      data : {
        old_user  : old_user,
        user_name : user_name,
        old_pass  : old_pass,
        password  : password,
        role      : role
      },
      url : "/services/user/update",
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
<?php include  __DIR__."/include/footer.php";?>