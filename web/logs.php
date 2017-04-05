<?php
$page = isset($_GET["page"])?$_GET["page"]:1;
$page = intval($page);
if ($page<=0)
    $page = 1;
$limit = 20;

$count    = \Seals\Logger\Local::getAllCount();
$all_page = ceil($count/($limit+1));
$next_page = $page+1;
if ($next_page > $all_page)
  $next_page = 1;

$prev_page = $page-1;
if ($prev_page < 1)
  $prev_page = $all_page;

include "include/nav.php";
?>
  <style>
    .page-item{
      padding: 0 6px;
    }
  </style>
  <!-- page content -->
        <div class="right_col" role="main">
          <div class="">

            <div class="page-title">
              <div class="title_left">
                <h3>Logs</h3>
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
<!--                <div class="x_title">-->
<!--                  <h2 style="width: 60px;">List</h2>-->
<!--                  <div class="clearfix"></div>-->
<!--                </div>-->
                <div class="x_content">

                  <div style="text-align: right;">
                    <a class="page-item" href="logs.php?page=<?php echo $prev_page; ?>">上一页</a>
                    <a class="page-item" href="logs.php?page=1">首页</a>
                    <a class="page-item">第<?php echo $page; ?>/<?php echo $all_page; ?>页</a>
                    <span class="page-item">
                      第<input style="width: 36px; text-align: center; height: 17px;" type="text" value="<?php echo $page; ?>"/>页
                      <a class="jump-to" onclick="jumpTo(this)">跳转</a>
                    </span>
                    <a class="page-item" href="logs.php?page=<?php echo $all_page; ?>">最后一页</a>
                    <a class="page-item" href="logs.php?page=<?php echo $next_page; ?>">下一页</a>
                  </div>

                  <table class="table table-striped  jambo_table bulk_action">
                    <thead style="    background: #26B99A !important;color: #fff;">
                    <tr>
                      <th>
                        <input type="checkbox" id="check-all" class="flat">
                      </th>
<!--                      <th>Index</th>-->
                      <th>Level</th>
                      <th>Message</th>
                      <th>Context</th>
                      <th>Time</th>
                      <th>Operate</th>
                    </tr>
                    </thead>
                    <tbody class="report-list">
                    <?php
                    $logs = \Seals\Logger\Local::getAll($page, $limit);
                    foreach ($logs as $index => $log) {
                    ?>
                    <tr>
                      <td class="a-center">
                        <input type="checkbox" class="flat" name="table_records">
                      </td>
<!--                      <td>--><?php //echo ($index+1); ?><!--</td>-->
                      <td><?php echo $log["level"]; ?></td>
                      <td style="word-wrap: break-word;word-break: break-all;"><?php echo $log["message"]; ?></td>
                      <td style="word-wrap: break-word;word-break: break-all;">
                        <?php print_r($log["context"]); ?>
                      </td>
                      <td><?php echo date("Y-m-d H:i:s", $log["time"]); ?></td>
                      <td>
                        <a class="btn btn-danger btn-sm" onclick="deleteUser(this)">Delete</a>
                      </td>
                    </tr>
                    <?php } ?>
                    </tbody>
                  </table>

                  <div style="text-align: right;">
                    <a class="page-item" href="logs.php?page=<?php echo $prev_page; ?>">上一页</a>
                    <a class="page-item" href="logs.php?page=1">首页</a>
                    <a class="page-item">第<?php echo $page; ?>/<?php echo $all_page; ?>页</a>
                    <span class="page-item">
                      第<input style="width: 36px; text-align: center; height: 17px;" type="text" value="<?php echo $page; ?>"/>页
                      <a class="jump-to" onclick="jumpTo(this)">跳转</a>
                    </span>
                    <a class="page-item" href="logs.php?page=<?php echo $all_page; ?>">最后一页</a>
                    <a class="page-item" href="logs.php?page=<?php echo $next_page; ?>">下一页</a>
                  </div>
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
  function jumpTo(dom)
  {
    window.location.href="logs.php?page="+ $(dom).parent().find("input").val();
  }
</script>
<?php include "include/footer.php";?>