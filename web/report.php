<?php
$session_id = $_GET["session_id"];
include "include/nav.php";
?>


<!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="row top_tiles">
              <div class="animated flipInY col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="tile-stats">
                  <div class="icon"><i class="fa fa-caret-square-o-right"></i></div>
                  <div class="count"><?php echo
                    \Seals\Web\Logic\Node::getHistoryReadMax($session_id);
                    ?></div>
                  <h3>Read</h3>
                  <p>History highest read concurrency</p>
                </div>
              </div>
              <div class="animated flipInY col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="tile-stats">
                  <div class="icon"><i class="fa fa-comments-o"></i></div>
                  <div class="count"><?php echo \Seals\Web\Logic\Node::getHistoryWriteMax($session_id); ?></div>
                  <h3>Write</h3>
                  <p>History highest write concurrency</p>
                </div>
              </div>
              <div class="animated flipInY col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="tile-stats">
                  <div class="icon"><i class="fa fa-sort-amount-desc"></i></div>
                  <div class="count"><?php echo \Seals\Web\Logic\Node::getDayReadMax($session_id, date("Ymd")); ?></div>
                  <h3>Read</h3>
                  <p>Today highest read concurrency</p>
                </div>
              </div>
              <div class="animated flipInY col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="tile-stats">
                  <div class="icon"><i class="fa fa-check-square-o"></i></div>
                  <div class="count"><?php echo \Seals\Web\Logic\Node::getDayWriteMax($session_id, date("Ymd")); ?></div>
                  <h3>Write</h3>
                  <p>Today highest write concurrency</p>
                </div>
              </div>
            </div>

            <div class="row" style="margin-bottom: 12px;">
              <div class="col-md-12">
                <div id="reportrange" style="background: #fff;  cursor: pointer; padding: 5px 10px; border:1px solid #E6E9ED;">
                  <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                  <span>December 30, 2014 - January 28, 2015</span> <b class="caret"></b>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-12">
              <div class="x_panel">
                <div class="x_title">
                  <h2>Statistical report <small>day detail</small></h2>
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
                      <th>Day</th>
                      <th>Show</th>
                      <th>Insert</th>
                      <th>Delete</th>
                      <th>Update</th>
                      <th>Select</th>
                      <th>Highest read</th>
                      <th>Highest write</th>
                      <th>Total read</th>
                      <th>Total write</th>
                      <th>Operate</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                      <th scope="row">2017-03-01</th>
                      <td>1</td>
                      <td>1</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td><a class="r-detail" href="#">Detail</a></td>
                    </tr>
                    <tr>
                      <th scope="row">2017-03-02</th>
                      <td>1</td>
                      <td>1</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td><a class="r-detail" href="#">Detail</a></td>
                    </tr>
                    <tr>
                      <th scope="row">2017-03-03</th>
                      <td>1</td>
                      <td>1</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td>100</td>
                      <td><a class="r-detail" href="#">Detail</a></td>
                    </tr>
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