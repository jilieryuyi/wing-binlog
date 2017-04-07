<?php
include  __DIR__."/include/nav.php";
?>

  <!-- page content -->
        <div class="right_col" role="main">
          <div class="">

            <div class="page-title">
              <div class="title_left">
                <h3>角色管理</h3>
              </div>

<!--              <div class="title_right">-->
<!--                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">-->
<!--                  <div class="input-group">-->
<!--                    <input type="text" class="form-control" placeholder="Search for...">-->
<!--                    <span class="input-group-btn">-->
<!--                      <button class="btn btn-default" type="button">Go!</button>-->
<!--                    </span>-->
<!--                  </div>-->
<!--                </div>-->
<!--              </div>-->
            </div>
            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-12">
              <div class="x_panel">
                <div class="x_title">
                  <h2 style="width: 60px;">角色</h2>
                  <a class="btn btn-success btn-sm" href="role.add.php">添加</a>
                  <div class="clearfix"></div>
                </div>
                <div class="x_content">

                  <table class="table table-striped">
                    <thead>
                    <tr>
                      <th>序号</th>
                      <th>角色</th>
                      <th>权限 (has/all)</th>
                      <th>创建时间</th>
                      <th>操作</th>
                    </tr>
                    </thead>
                    <tbody class="report-list">
                    <?php
                    $roles = \Seals\Web\Logic\User::getAllRoles();
                    $pages = count(\Seals\Web\Route::getRoutes()["post"])+count(\Seals\Web\Route::getAllPage());
//                    var_dump($roles);
                    foreach ($roles as $index => $role) {
                    ?>
                    <tr>
                      <th scope="row"><?php echo ($index+1); ?></th>
                      <td><?php echo $role["name"]; ?></td>
                      <td><?php echo count($role["pages"])."/".$pages; ?></td>
                      <td><?php echo $role["created"]; ?></td>
                      <td>
                        <a class="btn btn-primary btn-sm" href="role.detail.php?role=<?php echo urlencode($role["name"]); ?>">详情</a>
                        <a class="btn btn-primary btn-sm" href="role.edit.php?role=<?php echo urlencode($role["name"]); ?>">编辑</a>
                        <a class="btn btn-danger btn-sm" onclick="deleteRole(this)" data-role="<?php echo $role["name"]; ?>">删除</a>
                      </td>
                    </tr>
                    <?php } ?>
<!--                    <tr>-->
<!--                      <th scope="row">2017-03-02</th>-->
<!--                      <td>1</td>-->
<!--                      <td>1/1000</td>-->
<!--                      <td>100/1000</td>-->
<!--                      <td>100/1000</td>-->
<!--                      <td>100</td>-->
<!--                      <td>100</td>-->
<!--                      <td>100</td>-->
<!--                      <td>100</td>-->
<!--                      <td>100</td>-->
<!--                      <td><a class="r-detail" href="#">Detail</a></td>-->
<!--                    </tr>-->
<!--                    <tr>-->
<!--                      <th scope="row">2017-03-03</th>-->
<!--                      <td>1</td>-->
<!--                      <td>1/1000</td>-->
<!--                      <td>100/1000</td>-->
<!--                      <td>100/1000</td>-->
<!--                      <td>100</td>-->
<!--                      <td>100</td>-->
<!--                      <td>100</td>-->
<!--                      <td>100</td>-->
<!--                      <td>100</td>-->
<!--                      <td><a class="r-detail" href="#">Detail</a></td>-->
<!--                    </tr>-->
                    </tbody>
                  </table>

                </div>
              </div>
              </div>
            </div>
          </div>
        </div>
        <!-- /page content -->
<script>
  function deleteRole(dom) {

    var role = $(dom).attr("data-role");
    if (!window.confirm("Confirm delete role <"+role+"> ?"))
      return;

    if (!Wing.lock())
      return;

    showDoing(dom);

    $.ajax({
      type : "POST",
      url : "/services/role/delete",
      data : {
        role : role
      },
      success:function(msg){
        $(dom).parents("tr").remove();
      }
    });
  }
</script>
<?php include  __DIR__."/include/footer.php";?>