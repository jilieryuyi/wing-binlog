<?php
$role = urldecode($_GET["role"]);

include "include/nav.php";
?>

  <!-- page content -->
        <div class="right_col" role="main">
          <div class="">

            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Role edit</h2>
                    <small>
                      <a href="roles.php" style="text-decoration: underline;     float: left;margin-top: 7px;margin-left: 12px;">Roles manager</a>
                    </small>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <br>
                    <form id="demo-form2" data-parsley-validate="" class="form-horizontal form-label-left" novalidate="">

                      <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Role Name <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <input type="text" id="role-name" required="required" class="form-control col-md-7 col-xs-12" data-role="<?php echo $role; ?>" value="<?php echo $role; ?>">
                        </div>
                      </div>

                      <div class="form-group">
                        <label class="col-md-3 col-sm-3 col-xs-12 control-label">
                          Powers <span class="required">*</span>
                        </label>

                        <div class="col-md-9 col-sm-9 col-xs-12">

                          <div class="checkbox">
                            <label>
                              <input type="checkbox" class="select-all">
                              <span style="font-weight: bold; color: #000;">Select All</span>
                            </label>
                          </div>

                          <?php
                          $role_powsers = \Seals\Web\Logic\User::roleInfo($role);
                          $pages = \Seals\Web\Route::getAllPage();
                          foreach ($pages as $page) {
                          ?>
                          <div class="checkbox">
                            <label>
                              <input type="checkbox" <?php if (in_array($page,$role_powsers))echo "checked"; ?> value="" class="p-item"> <span class="page"><?php echo $page; ?></span>
                            </label>
                          </div>

                            <?php } ?>

                          <?php $routes = \Seals\Web\Route::getRoutes();
                          foreach ($routes as $_route) {

                              foreach ($_route as $route => $method) {
                          ?>
                          <div class="checkbox">
                            <label>
                              <input type="checkbox" <?php if (in_array($route,$role_powsers))echo "checked"; ?> value="" class="p-item"> <span class="page"><?php echo $route; ?></span>
                            </label>
                          </div>
                            <?php }} ?>

                          <div class="checkbox">
                            <label>
                              <input type="checkbox" class="select-all">
                              <span style="font-weight: bold; color: #000;">Select All</span>
                            </label>
                          </div>
                        </div>
                      </div>

                      <div class="ln_solid"></div>
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                          <button type="button" style="float: left;" onclick="addRole(this)" class="btn btn-success">Save Update</button>
                          <a href="roles.php" style="text-decoration: underline; float: left; margin: 15px 0 0 12px;">Roles manager</a>
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
  function addRole(dom)
  {
    if (!Wing.lock())
      return;

    showDoing(dom);

    var role_name = $("#role-name").val();
    var old_role  =  $("#role-name").attr("data-role");

    var pages = [];
    $(".p-item:checked").each(function(i,v){
      pages.push($(v).parent().find(".page").text());
    });

    $.ajax({
      type : "POST",
      data : {
        old_role  : old_role,
        role_name : role_name,
        pages     : JSON.stringify(pages)
      },
      url  : "/services/role/add",
      success : function(msg) {
          if (!msg || msg <= 0) {
            alert("保存失败");
          } else {

          }
      }
    });

  }
  $(document).ready(function(){
    $(".select-all").on("click", function(){
        $(".p-item").prop("checked", $(this).prop("checked"));
        $(".select-all").prop("checked", $(this).prop("checked"));
    });

    if ($(".p-item:checked").length == $(".p-item").length)
      $(".select-all").prop("checked", true);
  });
</script>
<?php include "include/footer.php";?>