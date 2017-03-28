<?php
include "include/nav.php";
?>

  <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>User add</h2>
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
                          <input type="text" id="user_name" name="user_name" required="required" class="form-control col-md-7 col-xs-12"/>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="last-name">Password <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <input data-is-encode="1" id="password" value="" type="text"  name="password" required="required" class="form-control col-md-7 col-xs-12">
                        </div>
                      </div>

                      <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Role <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <select id="role" class="form-control">
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
                          <button style="float: left;" type="button" onclick="addUser(this)" class="btn btn-success">Add User</button>
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
  function addUser(dom)
  {
      if (!Wing.lock())
        return;

    showDoing(dom);

    var user_name = $("#user_name").val();
    var password  = $("#password").val();
    var role      = $("#role option:selected").html();

    $.ajax({
      type: "POST",
      data : {
        user_name : user_name,
        password  : password,
        role      : role
      },
      url : "/services/user/add",
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