<?php
$session_id = isset($_GET["session_id"])?$_GET["session_id"]:null;
$page       = isset($_GET["page"])?$_GET["page"]:1;
$page       = intval($page);
$level      = isset($_GET["level"])?$_GET["level"]:"";

if ($page <= 0) {
    $page = 1;
}
$limit = 20;

$count = \Seals\Logger\Local::getAllCount($session_id, $level);

$all_page  = ceil($count/($limit+1));
$next_page = $page+1;

if ($next_page > $all_page) {
    $next_page = 1;
}

$prev_page = $page-1;
if ($prev_page < 1) {
    $prev_page = $all_page;
}

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

              <div class="title_right" style="display: none;">
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
                    <select title="logs level" onchange="logLevelChange(this)" class="form-control" style="width: 130px;display: inline-block;height: 22px;">
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="" <?php if(!$level) echo "selected";?>>--All Logs--</option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::ALERT;?>"     <?php if($level == \Psr\Log\LogLevel::ALERT) echo "selected";?>><?php echo \Psr\Log\LogLevel::ALERT;?></option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::CRITICAL;?>"  <?php if($level == \Psr\Log\LogLevel::CRITICAL) echo "selected";?>><?php echo \Psr\Log\LogLevel::CRITICAL;?></option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::DEBUG;?>"     <?php if($level == \Psr\Log\LogLevel::DEBUG) echo "selected";?>><?php echo \Psr\Log\LogLevel::DEBUG;?></option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::EMERGENCY;?>" <?php if($level == \Psr\Log\LogLevel::EMERGENCY) echo "selected";?>><?php echo \Psr\Log\LogLevel::EMERGENCY;?></option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::ERROR;?>"     <?php if($level == \Psr\Log\LogLevel::ERROR) echo "selected";?>><?php echo \Psr\Log\LogLevel::ERROR;?></option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::INFO;?>"      <?php if($level == \Psr\Log\LogLevel::INFO) echo "selected";?>><?php echo \Psr\Log\LogLevel::INFO;?></option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::NOTICE;?>"    <?php if($level == \Psr\Log\LogLevel::NOTICE) echo "selected";?>><?php echo \Psr\Log\LogLevel::NOTICE;?></option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::WARNING;?>"   <?php if($level == \Psr\Log\LogLevel::WARNING) echo "selected";?>><?php echo \Psr\Log\LogLevel::WARNING;?> </option>
                    </select>
                    <a class="page-item" href="logs.php?page=<?php
                    echo $prev_page;
                    if ($session_id)
                      echo "&session_id=".$session_id;
                    ?>">上一页</a>
                    <a class="page-item" href="logs.php?page=1<?php  if ($session_id)
                      echo "&session_id=".$session_id; ?>">首页</a>
                    <a class="page-item">第<?php echo $page; ?>/<?php echo $all_page; ?>页</a>
                    <span class="page-item">
                      第<input title="jump to page" style="width: 36px; text-align: center; height: 17px;" type="text" value="<?php echo $page; ?>"/>页
                      <a class="jump-to" onclick="jumpTo(this)">跳转</a>
                    </span>
                    <a class="page-item" href="logs.php?page=<?php echo $all_page; if ($session_id)
                      echo "&session_id=".$session_id;?>">最后一页</a>
                    <a class="page-item" href="logs.php?page=<?php echo $next_page;if ($session_id)
                      echo "&session_id=".$session_id; ?>">下一页</a>
                  </div>
                  <table class="table table-striped"><!--  jambo_table bulk_action-->
                    <thead style="    background: #26B99A !important;color: #fff;">
                    <tr>
<!--                      <th>-->
<!--                        <input title="select all" type="checkbox" id="check-all" class="flat">-->
<!--                      </th>-->
<!--                      <th>Index</th>-->
                      <th>Level</th>
                      <th>Message</th>
                      <th>Context</th>
                      <th>Time</th>
<!--                      <th>Operate</th>-->
                    </tr>
                    </thead>
                    <tbody class="report-list">
                    <?php
//                    if ($session_id)
//                      $logs = \Seals\Logger\Local::getNodeLogs($session_id, $page, $limit);
//                    else
                      $logs = \Seals\Logger\Local::getAll($page, $limit, $session_id, $level);
                    foreach ($logs as $index => $log) {
                    ?>
                    <tr>
<!--                      <td class="a-center">-->
<!--                        <input title="select" type="checkbox" class="flat" name="table_records">-->
<!--                      </td>-->
<!--                      <td>--><?php //echo ($index+1); ?><!--</td>-->
                      <td><?php echo $log["level"]; ?></td>
                      <td style="word-wrap: break-word;word-break: break-all;"><?php echo $log["message"]; ?></td>
                      <td style="word-wrap: break-word;word-break: break-all;">
                        <?php print_r($log["context"]); ?>
                      </td>
                      <td><?php echo date("Y-m-d H:i:s", $log["time"]); ?></td>
<!--                      <td>-->
<!--                        <a class="btn btn-danger btn-sm" onclick="deleteLog(this)">Delete</a>-->
<!--                      </td>-->
                    </tr>
                    <?php } ?>
                    </tbody>
                  </table>

                  <div style="text-align: right;">
                    <select title="logs level" onchange="logLevelChange(this)" class="form-control" style="width: 130px;display: inline-block;height: 22px;">
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="" <?php if(!$level) echo "selected";?>>--All Logs--</option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::ALERT;?>"     <?php if($level == \Psr\Log\LogLevel::ALERT) echo "selected";?>><?php echo \Psr\Log\LogLevel::ALERT;?></option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::CRITICAL;?>"  <?php if($level == \Psr\Log\LogLevel::CRITICAL) echo "selected";?>><?php echo \Psr\Log\LogLevel::CRITICAL;?></option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::DEBUG;?>"     <?php if($level == \Psr\Log\LogLevel::DEBUG) echo "selected";?>><?php echo \Psr\Log\LogLevel::DEBUG;?></option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::EMERGENCY;?>" <?php if($level == \Psr\Log\LogLevel::EMERGENCY) echo "selected";?>><?php echo \Psr\Log\LogLevel::EMERGENCY;?></option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::ERROR;?>"     <?php if($level == \Psr\Log\LogLevel::ERROR) echo "selected";?>><?php echo \Psr\Log\LogLevel::ERROR;?></option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::INFO;?>"      <?php if($level == \Psr\Log\LogLevel::INFO) echo "selected";?>><?php echo \Psr\Log\LogLevel::INFO;?></option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::NOTICE;?>"    <?php if($level == \Psr\Log\LogLevel::NOTICE) echo "selected";?>><?php echo \Psr\Log\LogLevel::NOTICE;?></option>
                      <option data-page="<?php echo $page; ?>" data-session-id="<?php if($session_id)echo $session_id; ?>" data-level="<?php echo \Psr\Log\LogLevel::WARNING;?>"   <?php if($level == \Psr\Log\LogLevel::WARNING) echo "selected";?>><?php echo \Psr\Log\LogLevel::WARNING;?> </option>
                    </select>
                    <a class="page-item" href="logs.php?page=<?php
                    echo $prev_page;
                    if ($session_id)
                      echo "&session_id=".$session_id;
                    ?>">上一页</a>
                    <a class="page-item" href="logs.php?page=1<?php  if ($session_id)
                      echo "&session_id=".$session_id; ?>">首页</a>
                    <a class="page-item">第<?php echo $page; ?>/<?php echo $all_page; ?>页</a>
                    <span class="page-item">
                      第<input title="jump to page" style="width: 36px; text-align: center; height: 17px;" type="text" value="<?php echo $page; ?>"/>页
                      <a class="jump-to" onclick="jumpTo(this)">跳转</a>
                    </span>
                    <a class="page-item" href="logs.php?page=<?php echo $all_page; if ($session_id)
                      echo "&session_id=".$session_id;?>">最后一页</a>
                    <a class="page-item" href="logs.php?page=<?php echo $next_page;if ($session_id)
                      echo "&session_id=".$session_id; ?>">下一页</a>
                  </div>
                </div>
              </div>
              </div>
            </div>
          </div>
        </div>
        <!-- /page content -->
<script>
  function logLevelChange(dom) {
    var selected   = $(dom).children("option:selected");
    var page       = selected.attr("data-page");
    var session_id = selected.attr("data-session-id");
    var level      = selected.attr("data-level");

    var href = "logs.php?page="+page;
    if (session_id != "")
      href += "&session_id="+session_id;
    if (level != "")
      href += "&level="+level;

    window.location.href = href;
  }
  function deleteLog(dom)
  {

      if (!Wing.lock())
        return;

      showDoing(dom);

//      $.ajax({
//        type : "POST",
//        url : "/services/user/delete",
//        data : {
//          user_name : user_name
//        },
//        success:function(msg){
//          $(dom).parents("tr").remove();
//        }
//      });
  }
  function jumpTo(dom)
  {
    var href = "logs.php?page="+ $(dom).parent().find("input").val();
    var session_id = "<?php if($session_id) echo $session_id; else echo ""; ?>";
    if (session_id != "")
      href += "&session_id="+session_id;
      window.location.href= href;
  }
</script>
<?php include "include/footer.php";?>