<?php
if (!isset($_GET["session_id"])) {
  echo "params error";
  return;
}
$session_id = $_GET["session_id"];
include "include/nav.php";
?>

  <!-- page content -->
        <div class="right_col" role="main">
          <div class="">

            <div class="page-title">
              <div class="title_left">
                <h3>Node info</h3>
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
                  <h2 style="width: 120px; float: left;">Process List</h2> <small style="float: left; margin-top: 8px;">
                    <?php $sysinfo = \Seals\Library\Worker::getSystemInfo(); ?>
                    Total memory <?php echo $sysinfo["memory_total"] ?>M, usage <?php echo $sysinfo["memory_usage"]; ?>M, <?php echo bcdiv($sysinfo["memory_usage"]*100/$sysinfo["memory_total"],1,2); ?>%, Cpu usage <?php echo $sysinfo["cpu_usage"]; ?>%
                  </small>
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
                      <th>Process ID</th>
                      <th>User</th>
                      <th>Memory Peak Usage</th>
                      <th>Memory Usage</th>
                      <th>Cpu Usage</th>
                      <th>Status</th>
                    </tr>
                    </thead>
                    <tbody class="report-list">
                    <?php
                    $processes = \Seals\Library\Worker::getInfo($session_id);
                    $index     = 0;
                    foreach ($processes as $process_id => $info) {
                      $is_master = $info["is_master"];
                    ?>
                    <tr>
                      <td style="<?php if($is_master) echo 'color: #f00; font-weight: bold;'?>" title="<?php if($is_master) echo "parent process"; ?>"><?php echo (++$index); ?></td>
                      <td><?php echo $process_id; ?></td>
                      <td><?php echo $info["user"]; ?></td>
                      <td><?php echo $info["memory_peak_usage"]/1024; ?>k</td>
                      <td><?php echo $info["memory_usage"]/1024; ?>k</td>
                      <td><?php echo $info["cpu"]; ?></td>
                      <td><?php echo $info["status"]; ?></td>
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
<?php include "include/footer.php";?>