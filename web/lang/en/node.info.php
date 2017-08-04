<?php
if (!isset($_GET["session_id"])) {
  echo "params error";
  return;
}
$session_id = $_GET["session_id"];
include  __DIR__."/include/nav.php";
?>

  <!-- page content -->
        <div class="right_col" role="main">
          <div class="">

            <div class="page-title">
              <div class="title_left">
                <h3>Node info</h3>
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
                      <th>Cpu usage </th>
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
<?php include  __DIR__."/include/footer.php";?>