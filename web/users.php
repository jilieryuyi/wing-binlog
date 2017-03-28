<?php
include "include/nav.php";
?>

  <!-- page content -->
        <div class="right_col" role="main">
          <div class="">

            <div class="page-title">
              <div class="title_left">
                <h3>Users manager</h3>
              </div>

              <div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-12">
              <div class="x_panel">
                <div class="x_title">
                  <h2 style="width: 60px;">Users</h2> <a href="user.add.php" class="btn btn-success btn-sm">Add</a>
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

                  <table class="table table-striped">
                    <thead>
                    <tr>
                      <th>Index</th>
                      <th>User Name</th>
                      <th>Role</th>
                      <th>Created</th>
                      <th>Login Times</th>
                      <th>Last Login</th>
                      <th>Operate</th>
                    </tr>
                    </thead>
                    <tbody class="report-list">
                    <?php
                    $users = \Seals\Web\Logic\User::all();
                    foreach ($users as $index => $user) {
                    ?>
                    <tr>
                      <th scope="row"><?php echo ($index+1); ?></th>
                      <td><?php echo $user["name"]; ?></td>
                      <td><?php echo $user["role"]; ?></td>
                      <td><?php echo $user["created"]; ?></td>
                      <td><?php echo $user["times"]; ?></td>
                      <td><?php echo $user["last_login"]; ?></td>
                      <td>
                        <a class="btn btn-primary btn-sm" href="user.edit.php?name=<?php echo urlencode($user["name"]); ?>">Edit</a>
                        <a class="btn btn-danger btn-sm" data-user="<?php echo $user["name"]; ?>" onclick="deleteUser(this)">Delete</a>
                      </td>
                    </tr>
                    <?php } ?>
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
  function deleteUser(dom)
  {
    var user_name = $(dom).attr("data-user");

    if (!window.confirm("confirm delete user<"+user_name+"> ?"))
      return;

      if (!Wing.lock())
        return;

      showDoing(dom);

      $.ajax({
        type : "POST",
        url : "/services/user/delete",
        data : {
          user_name : user_name
        },
        success:function(msg){
          $(dom).parents("tr").remove();
        }
      });
  }
</script>
<?php include "include/footer.php";?>