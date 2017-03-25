<?php
if (!isset($_GET["group_id"]) || !isset($_GET["session_id"])) {
    echo "params error";
    return;
}
$group_id   = $_GET["group_id"];
$session_id = $_GET["session_id"];

$node_info = \Seals\Web\Logic\Node::getInfo($group_id, $session_id);
//var_dump($node_info);

$databases = \Seals\Web\Logic\Node::getDatabases($session_id);
?>
<?php include "include/nav.php"; ?>

<script>
    var group_id   = "<?php echo $_GET["group_id"]; ?>";
    var session_id = "<?php echo $_GET["session_id"]; ?>";
</script>
<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3>Node Configure</h3>
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
            <div class="col-md-6 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Process Runtime Configure <small>just configure it</small></h2>
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

                        <!-- start form for validation -->
                        <div class="c-item form-horizontal form-label-left">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Workers Num</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control workers" value="<?php echo $node_info["workers"]; ?>" placeholder="Workers Num">
                                </div>
                            </div>
<!--                            <div>进程数量<input class="workers" type="text" value="--><?php //echo $node_info["workers"]; ?><!--"/></div>-->
<!--                            <div><label>开启debug<input class="debug" type="checkbox" --><?php //if($node_info["debug"]) echo "checked";?><!--/></label></div>-->
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Debug</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <div class="">
                                        <label>
                                            <input type="checkbox" class="js-switch debug" <?php if($node_info["debug"])echo "checked";?> data-switchery="true" style="display: none;">
<!--                                            <span class="switchery switchery-default" style="background-color: rgb(38, 185, 154); border-color: rgb(38, 185, 154); box-shadow: rgb(38, 185, 154) 0px 0px 0px 11px inset; transition: border 0.4s, box-shadow 0.4s, background-color 1.2s;">-->
<!--                                                <small style="left: 12px; transition: background-color 0.4s, left 0.2s; background-color: rgb(255, 255, 255);"></small>-->
<!--                                            </span> Enable-->
                                            Enable
                                        </label>
                                    </div>
                                </div>
                            </div>
<!--                            <div>-->
<!--                                <span class="button button-small button-local" onclick="setRuntimeConfig(this)">更新配置</span>-->
<!--                            </div>-->
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
<!--                                    <button type="button" class="btn btn-primary">Cancel</button>-->
<!--                                    <button class="btn btn-primary" type="reset">Reset</button>-->
                                    <button type="button" onclick="setRuntimeConfig(this)" class="btn btn-success">Update Configure</button>
                                </div>
                            </div>
                        </div>
                        <!-- end form for validations -->

                    </div>
                </div>
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Notify Configure <small>just configure it</small></h2>
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
                        <div class="c-item form-horizontal form-label-left">
<!--                                <div style="font-size: 12px;">关于参数<br/><span class="c-red">如果是redis，只需要填写第一个，第一个为事件队列名称<br/>-->
<!--            如果是http，第一个参数为url，第二个参数为自定义数据<br/>-->
<!--                如果是rabbitmq，第一个参数为交换机名称，第二个参数为队列名称-->
<!--            </span></div>-->
                            <div class="form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Notify Mode</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                        <select class="notify-class form-control" onchange="onNotifySelect(this)">
                                        <option data-config-class="event-redis-config" data-param-1="<?php
                                        if ($node_info["notify"]["handler"] == "Seals\\Notify\\Redis")
                                            echo $node_info["notify"]["params"][0];
                                        else
                                            echo "seals:event:list";
                                        ?>"
                                                data-param-2=""
                                                value="Seals\\Notify\\Redis"
                                            <?php if ($node_info["notify"]["handler"] == "Seals\\Notify\\Redis") echo "selected"; ?>
                                        >redis queue</option>
                                        <option  data-config-class=""
                                            data-param-1="<?php
                                            if ($node_info["notify"]["handler"] == "Seals\\Notify\\Http")
                                                echo $node_info["notify"]["params"][0];
                                            else
                                                echo "http://127.0.0.1:9998/";
                                            ?>"
                                            data-param-2="<?php
                                            if ($node_info["notify"]["handler"] == "Seals\\Notify\\Http" &&
                                                isset($node_info["notify"]["params"][1]))
                                                echo $node_info["notify"]["params"][1];
                                            else
                                                echo "author:yuyi,email:297341015@qq.com";
                                            ?>"
                                            value="Seals\\Notify\\Http"
                                            <?php if ($node_info["notify"]["handler"] == "Seals\\Notify\\Http") echo "selected"; ?>
                                        >http</option>
                                        <option  data-config-class="rabbitmq-config"
                                            data-param-1="<?php
                                            if ($node_info["notify"]["handler"] == "Seals\\Notify\\Rabbitmq")
                                                echo $node_info["notify"]["params"][0];
                                            else
                                                echo "wing-binlog-exchange";
                                            ?>"
                                            data-param-2="<?php
                                            if ($node_info["notify"]["handler"] == "Seals\\Notify\\Rabbitmq" &&
                                                isset($node_info["notify"]["params"][1]))
                                                echo $node_info["notify"]["params"][1];
                                            else
                                                echo "wing-binlog-queue";
                                            ?>"
                                            value="Seals\\Notify\\Rabbitmq"
                                            <?php if ($node_info["notify"]["handler"] == "Seals\\Notify\\Rabbitmq") echo "selected"; ?>
                                        >rabbitmq</option>
                                    </select>
                                    </div>
                                </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Param 1</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="param1 form-control" type="text" value="<?php echo $node_info["notify"]["params"][0]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Param 2</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="param2 form-control" type="text" value="<?php if (isset($node_info["notify"]["params"][1]))
                                            echo $node_info["notify"]["params"][1]; ?>"/>
                                </div>
                            </div>
<!--                                <div><span onclick="setNotifyConfig(this)" class="button button-small button-local">更新配置</span></div>-->
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button type="button" onclick="setNotifyConfig(this)" class="btn btn-success">Update Configure</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Rabbitmq configure-->
                <div class="x_panel data-target-config rabbitmq-config" style="<?php if ($node_info["notify"]["handler"] != "Seals\\Notify\\Rabbitmq") echo 'display: none;';?>">
                    <div class="x_title">
                        <h2>Rabbitmq Configure <small>just configure it</small></h2>
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
                        <div class="c-item form-horizontal form-label-left">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Host</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="host form-control" type="text" value="<?php echo $node_info["rabbitmq"]["host"]; ?>" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Port</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="port form-control" type="text" value="<?php echo $node_info["rabbitmq"]["port"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">User</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="user form-control" type="text" value="<?php echo $node_info["rabbitmq"]["user"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Password</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="password form-control" type="text" value=""/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Vhost</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="vhost form-control" type="text" value="<?php echo $node_info["rabbitmq"]["vhost"]; ?>"/>
                                </div>
                            </div>
                            <!--                            <div><span onclick="setRabbitmqConfig(this)" class="button button-small button-local">更新配置</span></div>-->
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button type="button" onclick="setRabbitmqConfig(this)" class="btn btn-success">Update Configure</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Event redis configure-->
                <div class="x_panel data-target-config event-redis-config" style="<?php if ($node_info["notify"]["handler"] != "Seals\\Notify\\Redis") echo 'display: none;'?>">
                    <div class="x_title">
                        <h2>Event Redis Configure <small>just configure it</small></h2>
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
                        <div class="c-item form-horizontal form-label-left">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Host</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="host form-control" type="text" value="<?php echo $node_info["redis_config"]["host"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Port</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="port form-control" type="text" value="<?php echo $node_info["redis_config"]["port"]; ?>"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Password</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="password form-control" type="text" value=""/>
                                    <ul class="parsley-errors-list filled" id="parsley-id-5">
                                        <li class="parsley-required">you can use :null to set the password as null</li>
                                    </ul>
                                </div>
                            </div>
                            <!--                            <div><span onclick="setRedisConfig(this)" class="button button-small button-local">更新配置</span></div>-->
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button type="button" onclick="setRedisConfig(this)" class="btn btn-success">Update Configure</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Database configure-->
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Database Configure <small>just configure it</small></h2>
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
                        <div class="c-item form-horizontal form-label-left">

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Host</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">

                                    <input class="host form-control" type="text" value="<?php echo $node_info["db_config"]["host"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Port</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="port form-control" type="text" value="<?php echo $node_info["db_config"]["port"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">User</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="user form-control" type="text" value="<?php echo $node_info["db_config"]["user"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Password</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="password form-control" type="text" value=""/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Database</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">

                                    <select class="db_name form-control">
                                        <?php foreach ($databases as $database){
                                            $selected = $node_info["db_config"]["db_name"] == $database ? "selected" : "";
                                            ?>
                                            <option <?php echo $selected; ?> value="<?php echo $database; ?>"><?php echo $database; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <!--            <input class="db_name" type="text"  value="--><?php //echo $node_info["db_config"]["db_name"]; ?><!--"/>-->
                            </div>
                            <!--                        <div><span onclick="setDbConfig(this)" class="button button-small button-local">更新配置</span></div>-->
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button type="button" onclick="setDbConfig(this)" class="btn btn-success">Update Configure</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-md-6 col-xs-12">

                <!--Group configure-->
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Group Configure <small>just configure it</small></h2>
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
                        <div class="c-item form-horizontal form-label-left">

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Group ID</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="group_id form-control" type="text" value="<?php echo $node_info["zookeeper"]["group_id"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Host</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="host form-control" type="text" value="<?php echo $node_info["zookeeper"]["host"]; ?>"/>
                                </div>
                                </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Port</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="port form-control" type="text" value="<?php echo $node_info["zookeeper"]["port"]; ?>"/>
                                </div>
                                </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Password</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="password form-control" type="text" value=""/>
                                    <ul class="parsley-errors-list filled" id="parsley-id-5">
                                        <li class="parsley-required">you can use :null to set the password as null</li>
                                    </ul>
                                </div>
                                </div>
<!--                        <div><span onclick="setZookeeperConfig(this)" class="button button-small button-local">更新配置</span></div>-->
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button type="button" onclick="setZookeeperConfig(this)" class="btn btn-success">Update Configure</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Local redis configure-->
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Local Redis Configure <small>just update it</small></h2>
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
                        <div class="c-item form-horizontal form-label-left">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Host</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="host form-control" type="text" value="<?php echo $node_info["redis_local"]["host"]; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Port</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="port form-control" type="text" value="<?php echo $node_info["redis_local"]["port"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Port</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="password form-control" type="text" value=""/>
                                    <ul class="parsley-errors-list filled" id="parsley-id-5">
                                        <li class="parsley-required">you can use :null to set the password as null</li>
                                    </ul>
                                </div>
                            </div>
                            <!--                                <div><span onclick="setLocalRedisConfig(this)" class="button button-small button-local">更新配置</span></div>-->
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button type="button" onclick="setLocalRedisConfig(this)" class="btn btn-success">Update Configure</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                </div>
        </div>
    </div>
</div>
<script src="js/config.js"></script>
<?php include "include/footer.php";?>
