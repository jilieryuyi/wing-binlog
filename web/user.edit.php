<?php
$user_name = $_GET["name"];
include "include/nav.php";
?>

  <!-- page content -->
        <div class="right_col" role="main">
          <div class="">

            <div class="page-title">
              <div class="title_left">
                <h3>User edit</h3>
              </div>
            </div>
            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2><?php echo $user_name; ?></h2>
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
                    <br>
                    <form id="demo-form2" data-parsley-validate="" class="form-horizontal form-label-left" novalidate="">

                      <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">User Name <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <input type="text" id="first-name" required="required" class="form-control col-md-7 col-xs-12" value="<?php echo $user_name; ?>">
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="last-name">Password <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <input type="text" id="last-name" name="last-name" required="required" class="form-control col-md-7 col-xs-12">
                        </div>
                      </div>
<!--                      <div class="form-group">-->
<!--                        <label for="middle-name" class="control-label col-md-3 col-sm-3 col-xs-12">Middle Name / Initial</label>-->
<!--                        <div class="col-md-6 col-sm-6 col-xs-12">-->
<!--                          <input id="middle-name" class="form-control col-md-7 col-xs-12" type="text" name="middle-name">-->
<!--                        </div>-->
<!--                      </div>-->

                      <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Role <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <select class="form-control">
                            <option>Choose option</option>
                            <option>Option one</option>
                            <option>Option two</option>
                            <option>Option three</option>
                            <option>Option four</option>
                          </select>
                        </div>
                      </div>
<!--                      <div class="form-group">-->
<!--                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Gender</label>-->
<!--                        <div class="col-md-6 col-sm-6 col-xs-12">-->
<!--                          <div id="gender" class="btn-group" data-toggle="buttons">-->
<!--                            <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">-->
<!--                              <input type="radio" name="gender" value="male" data-parsley-multiple="gender"> &nbsp; Male &nbsp;-->
<!--                            </label>-->
<!--                            <label class="btn btn-primary" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">-->
<!--                              <input type="radio" name="gender" value="female" data-parsley-multiple="gender"> Female-->
<!--                            </label>-->
<!--                          </div>-->
<!--                        </div>-->
<!--                      </div>-->
<!--                      <div class="form-group">-->
<!--                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Date Of Birth <span class="required">*</span>-->
<!--                        </label>-->
<!--                        <div class="col-md-6 col-sm-6 col-xs-12">-->
<!--                          <input id="birthday" class="date-picker form-control col-md-7 col-xs-12" required="required" type="text">-->
<!--                        </div>-->
<!--                      </div>-->
                      <div class="ln_solid"></div>
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
<!--                          <button class="btn btn-primary" type="button">Cancel</button>-->
<!--                          <button class="btn btn-primary" type="reset">Reset</button>-->
                          <button type="submit" class="btn btn-success">Save Update</button>
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
</script>
<?php include "include/footer.php";?>