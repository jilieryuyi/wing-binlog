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
<?php include  __DIR__."/include/nav.php"; ?>

<script>
    var group_id   = "<?php echo $_GET["group_id"]; ?>";
    var session_id = "<?php echo $_GET["session_id"]; ?>";
</script>
<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3>节点配置</h3>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="row">
            <div class="col-md-6 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>进程运行时配置 <small>just configure it</small></h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">

                        <!-- start form for validation -->
                        <div class="c-item form-horizontal form-label-left">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">进程数量</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control workers" value="<?php echo $node_info["workers"]; ?>" placeholder="Workers Num">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">调试模式</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <div class="">
                                        <label>
                                            <input type="checkbox" class="js-switch debug" <?php if($node_info["debug"])echo "checked";?> data-switchery="true" style="display: none;">
                                            启用
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button type="button" onclick="setRuntimeConfig(this)" class="btn btn-success">保存配置</button>
                                </div>
                            </div>
                        </div>
                        <!-- end form for validations -->

                    </div>
                </div>
                <div class="x_panel">
                    <div class="x_title">
                        <h2>事件通知配置 <small>just configure it</small></h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <div class="c-item form-horizontal form-label-left">
                            <div class="form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">通知方式</label>
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
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">参数1</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="param1 form-control" type="text" value="<?php echo $node_info["notify"]["params"][0]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">参数2</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="param2 form-control" type="text" value="<?php if (isset($node_info["notify"]["params"][1]))
                                            echo $node_info["notify"]["params"][1]; ?>"/>
                                </div>
                            </div>
<!--                                <div><span onclick="setNotifyConfig(this)" class="button button-small button-local">更新配置</span></div>-->
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button type="button" onclick="setNotifyConfig(this)" class="btn btn-success">保存配置</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Rabbitmq configure-->
                <div class="x_panel data-target-config rabbitmq-config" style="<?php if ($node_info["notify"]["handler"] != "Seals\\Notify\\Rabbitmq") echo 'display: none;';?>">
                    <div class="x_title">
                        <h2>Rabbitmq配置 <small>just configure it</small></h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <div class="c-item form-horizontal form-label-left">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Ip</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="host form-control" type="text" value="<?php echo $node_info["rabbitmq"]["host"]; ?>" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">端口</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="port form-control" type="text" value="<?php echo $node_info["rabbitmq"]["port"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">用户</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="user form-control" type="text" value="<?php echo $node_info["rabbitmq"]["user"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">密码</label>
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
                                    <button type="button" onclick="setRabbitmqConfig(this)" class="btn btn-success">保存配置</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Event redis configure-->
                <div class="x_panel data-target-config event-redis-config" style="<?php if ($node_info["notify"]["handler"] != "Seals\\Notify\\Redis") echo 'display: none;'?>">
                    <div class="x_title">
                        <h2>事件redis配置</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <div class="c-item form-horizontal form-label-left">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Ip</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="host form-control" type="text" value="<?php echo $node_info["redis_config"]["host"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">端口</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="port form-control" type="text" value="<?php echo $node_info["redis_config"]["port"]; ?>"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">密码</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="password form-control" type="text" value=""/>
                                    <ul class="parsley-errors-list filled" id="parsley-id-5">
                                        <li class="parsley-required">可以使用:null将密码设置为null</li>
                                    </ul>
                                </div>
                            </div>
                            <!--                            <div><span onclick="setRedisConfig(this)" class="button button-small button-local">更新配置</span></div>-->
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button type="button" onclick="setRedisConfig(this)" class="btn btn-success">保存配置</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Database configure-->
                <div class="x_panel">
                    <div class="x_title">
                        <h2>数据库配置</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <div class="c-item form-horizontal form-label-left">

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Ip</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="host form-control" type="text" value="<?php echo $node_info["db_config"]["host"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">端口</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="port form-control" type="text" value="<?php echo $node_info["db_config"]["port"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">用户</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="user form-control" type="text" value="<?php echo $node_info["db_config"]["user"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">密码</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="password form-control" type="text" value=""/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">数据库</label>
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
                                    <button type="button" onclick="setDbConfig(this)" class="btn btn-success">保存配置</button>
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
                        <h2>群集配置</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <div class="c-item form-horizontal form-label-left">

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">群ID</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="group_id form-control" type="text" value="<?php echo $node_info["zookeeper"]["group_id"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Ip</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="host form-control" type="text" value="<?php echo $node_info["zookeeper"]["host"]; ?>"/>
                                </div>
                                </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">端口</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="port form-control" type="text" value="<?php echo $node_info["zookeeper"]["port"]; ?>"/>
                                </div>
                                </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">密码</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="password form-control" type="text" value=""/>
                                    <ul class="parsley-errors-list filled" id="parsley-id-5">
                                        <li class="parsley-required">可以使用:null将密码设置为null</li>
                                    </ul>
                                </div>
                                </div>
<!--                        <div><span onclick="setZookeeperConfig(this)" class="button button-small button-local">更新配置</span></div>-->
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button type="button" onclick="setZookeeperConfig(this)" class="btn btn-success">保存配置</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Local redis configure-->
                <div class="x_panel">
                    <div class="x_title">
                        <h2>本地redis配置</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <div class="c-item form-horizontal form-label-left">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Ip</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="host form-control" type="text" value="<?php echo $node_info["redis_local"]["host"]; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">端口</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="port form-control" type="text" value="<?php echo $node_info["redis_local"]["port"]; ?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">密码</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="password form-control" type="text" value=""/>
                                    <ul class="parsley-errors-list filled" id="parsley-id-5">
                                        <li class="parsley-required">可以使用:null将密码设置为null</li>
                                    </ul>
                                </div>
                            </div>
                            <!--                                <div><span onclick="setLocalRedisConfig(this)" class="button button-small button-local">更新配置</span></div>-->
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button type="button" onclick="setLocalRedisConfig(this)" class="btn btn-success">保存配置</button>
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
<?php include  __DIR__."/include/footer.php";?>
