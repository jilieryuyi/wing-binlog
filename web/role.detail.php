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
                    <h2>Role detail</h2><small>
                      <a href="roles.php" style="text-decoration: underline;     float: left;
    margin-top: 7px;
    margin-left: 12px;">Roles manager</a>
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
                          <span class="form-control col-md-7 col-xs-12" style="border: none;box-shadow: none;"><?php echo $role; ?></span>
                        </div>
                      </div>

                      <div class="form-group">
                        <label class="col-md-3 col-sm-3 col-xs-12 control-label">
                          Powers <span class="required">*</span>
                        </label>

                        <div class="col-md-9 col-sm-9 col-xs-12">


                          <?php
                          $pages = \Seals\Web\Logic\User::roleInfo($role);
                          foreach ($pages as $page) {
                          ?>
                          <div class="checkbox">
                            <label style="padding-left: 0;">
                              <span class="page"><?php echo $page; ?></span>
                            </label>
                          </div>

                            <?php } ?>
                        </div>
                      </div>

                      <div class="ln_solid"></div>
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                          <a href="roles.php" style="text-decoration: underline; ">Roles manager</a>
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

    var pages = [];
    $(".p-item:checked").each(function(i,v){
      pages.push($(v).parent().find(".page").text());
    });

    $.ajax({
      type : "POST",
      data : {
        role_name : encodeURIComponent(role_name),
        pages : (JSON.stringify(pages))
      },
      url  : "/services/user/role/add",
      success : function(msg) {

      }
    });

  }
  $(document).ready(function(){
    $(".select-all").on("click", function(){
        $(".p-item").prop("checked", $(this).prop("checked"));
    });
  });
</script>
<?php include "include/footer.php";?>