        <?php include "include/nav.php"; ?>

        <!-- page content -->
        <div class="right_col" role="main">
          <!-- top tiles -->
          <div class="row tile_count">
            <div class="col-md-4 col-sm-4 col-xs-6 tile_stats_count">
              <span class="count_top"><i class="fa fa-clock-o"></i> Total Servers</span>
              <div class="count total-servers"><?php
                echo \Seals\Web\Logic\Server::serversNum();
                ?></div>
            </div>
            <div class="col-md-4 col-sm-4 col-xs-6 tile_stats_count">
              <span class="count_top"><i class="fa fa-user"></i> Total Events</span>
              <div class="count green total-events"><?php
                echo \Seals\Library\Report::getEventsCount();
                  ?></div>
              <span class="count_bottom">
                <?php
                $yestoday = date("Ymd",time()-86400);
                $day      = date("Ymd");

                $todayc    = \Seals\Library\Report::getDayEventsCount($day);
                $yestodayc = \Seals\Library\Report::getDayEventsCount($yestoday);

                if ($yestodayc > 0) {
                  $incr = bcdiv(($todayc - $yestodayc)*100,$yestodayc,2);
                } else {
                  $incr = 100;
                }

                ?>
                <i class="<?php if ($incr > 0) echo "green"; else echo "red"; ?>">
                  <i class="fa <?php if ($incr > 0) echo "fa-sort-asc";
                  else echo "fa-sort-desc";
                  ?>"></i><?php echo abs($incr); ?>%</i> From last Day</span>
            </div>
            <div class="col-md-4 col-sm-4 col-xs-6 tile_stats_count">
              <span class="count_top"><i class="fa fa-user"></i> Total Logs</span>
              <div class="count"><?php echo \Seals\Web\Logic\Logs::countAll();
                $yestoday = date("Ymd",time()-86400);
                $day      = date("Ymd");

                $yestoday_qc = \Seals\Web\Logic\Logs::countDay($yestoday);
                $day_qc      = \Seals\Web\Logic\Logs::countDay($day);

                if ($yestoday_qc > 0) {
                  $incr = bcdiv(($day_qc - $yestoday_qc)*100,$yestoday_qc,2);
                } else {
                  $incr = 100;
                }
                ?></div>
              <span class="count_bottom"><i class="<?php if ($incr > 0) echo "green"; else echo "red"; ?>">
                  <i class="fa <?php if ($incr > 0) echo "fa-sort-asc";
                  else echo "fa-sort-desc";
                  ?>"></i><?php echo abs($incr); ?>% </i> From last Day</span>
            </div>
          </div>
          <!-- /top tiles -->
          <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12" style="text-align: right;">
              <label class="login-timeout" style="display: none;"><label style="color: #f00;">登录超时，请重新</label><a href="login.php">登录</a></label>
              <span class="btn btn-success" onclick="restartMaster(this)">Restart Master Process</span>
              <span class="btn btn-success update-btn" onclick="updateMaster(this)">Update Master<?php
                if (\Seals\Library\Master::checkUpdate())
                echo '<label>1</label>';
                ?></span>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
              <div class="dashboard_graph">

                <div class="row x_title">
                  <div class="col-md-6">
                    <h3>Groups And Servers <small>manager</small></h3>
                  </div>
                </div>
                <div class="col-md-12 col-sm-9 col-xs-12">
                  <ul class="groups">
                    <li class="title" style="height: 25px;">
                      <span class="group-id col-md-2">Group</span>
                      <span class="node-count col-md-2">Nodes Count</span>
                      <span class="group-edit edit  col-md-8">Operate</span>
                    </li>
                  </ul>
                  </div>

                <div class="clearfix"></div>
              </div>
            </div>

          </div>
          <br />

        </div>
        <!-- /page content -->
        <script src="js/index.js"></script>
        <?php include "include/footer.php";?>
