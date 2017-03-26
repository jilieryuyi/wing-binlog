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
<!--                      <th>Show</th>-->
                      <th>Insert Times/Rows</th>
                      <th>Delete Times/Rows</th>
                      <th>Update Times/Rows</th>
                      <th>Select</th>
                      <th>Highest Read</th>
                      <th>Highest Write</th>
                      <th>Total Read</th>
                      <th>Total Write</th>
                      <th>Operate</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $reports = \Seals\Web\Logic\Node::getNodeDayReport($session_id, date("Ymd", time()-3*86400), date("Ymd"));
                    //var_dump($reports);
                    foreach ($reports as $day => $report) {
                    ?>
                    <tr>
                      <th scope="row"><?php echo $day; ?></th>
<!--                      <td>--><?php //echo $report["show"]; ?><!--</td>-->
                      <td><?php echo $report["insert"]; ?>/<?php echo $report["write_rows"]; ?></td>
                      <td><?php echo $report["delete"]; ?>/<?php echo $report["delete_rows"]; ?></td>
                      <td><?php echo $report["update"]; ?>/<?php echo $report["update_rows"]; ?></td>
                      <td><?php echo $report["select"]; ?></td>
                      <td><?php echo $report["read_max"]; ?></td>
                      <td><?php echo $report["write_max"]; ?></td>
                      <td><?php echo $report["read_total"]; ?></td>
                      <td><?php echo $report["write_total"]; ?></td>
                      <td><a class="r-detail" href="#">Detail</a></td>
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
  function init_daterangepicker() {

    if( typeof ($.fn.daterangepicker) === 'undefined'){ return; }
    console.log('init_daterangepicker');

    var cb = function(start, end, label) {
      console.log(start.toISOString(), end.toISOString(), label);
      $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
    };

    var __max_date = new WingDate("d/m/Y").toString();
    console.log("max date",__max_date);

    var __time = new Date().getTime()/1000;
    var __min_date = new WingDate("d/m/Y",__time-86400*365*10).toString();
    console.log("min date",__min_date);
    var optionSet1 = {
      startDate: moment().subtract(29, 'days'),
      endDate: moment(),
      minDate: "01/01/2010",
      maxDate: __max_date,
      dateLimit: {
        days: 60
      },
      showDropdowns: true,
      showWeekNumbers: true,
      timePicker: false,
      timePickerIncrement: 1,
      timePicker12Hour: true,
      ranges: {
        'Today': [moment(), moment()],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
      },
      opens: 'right',
      buttonClasses: ['btn btn-default'],
      applyClass: 'btn-small btn-primary',
      cancelClass: 'btn-small',
      format: 'MM/DD/YYYY',
      separator: ' to ',
      locale: {
        applyLabel: 'Submit',
        cancelLabel: 'Clear',
        fromLabel: 'From',
        toLabel: 'To',
        customRangeLabel: 'Custom',
        daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        firstDay: 1
      }
    };

    $('#reportrange span').html(moment().subtract(29, 'days').format('MMMM D, YYYY') + ' - ' + moment().format('MMMM D, YYYY'));
    $('#reportrange').daterangepicker(optionSet1, cb);
    $('#reportrange').on('show.daterangepicker', function() {
      console.log("show event fired");
    });
    $('#reportrange').on('hide.daterangepicker', function() {
      console.log("hide event fired");
    });
    $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
      console.log("apply event fired, start/end dates are " + picker.startDate.format('YYYY-MM-DD') + " to " + picker.endDate.format('YYYY-MM-DD'));
    });
    $('#reportrange').on('cancel.daterangepicker', function(ev, picker) {
      console.log("cancel event fired");
    });
//    $('#options1').click(function() {
//      $('#reportrange').data('daterangepicker').setOptions(optionSet1, cb);
//    });
//    $('#options2').click(function() {
//      $('#reportrange').data('daterangepicker').setOptions(optionSet2, cb);
//    });
    $('#destroy').click(function() {
      $('#reportrange').data('daterangepicker').remove();
    });

  }
$(document).ready(function(){
  init_daterangepicker();
});
</script>
        <?php include "include/footer.php";?>