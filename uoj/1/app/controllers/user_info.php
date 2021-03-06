<?php
$username = $_GET['username'];

requireLib('flot');
requirePHPLib('form');
global $group_exit_forms, $group_view_forms,$group_manage_forms;

if(Auth::check()&&Auth::id()==$username) {
    $group_manage_forms=array();
    $base_URL='/user/profile/'.$username;
    $group_exit_forms = array();//用于控制退出群组
    foreach (DB::selectAll("select * from group_info where username='{$username}'") as $each_row) {
        $group_name = $each_row['group_name'];
        $username = $each_row['username'];
        $cur_group = DB::selectFirst("select * from group_description where group_name='{$group_name}'");
        $count = count(DB::selectAll("select * from group_info where group_name= '{$group_name}'"));

        $form_name = "exit_group_{$group_name}_{$username}";
        $exit_form = new UOJForm($form_name);
        $exit_form->addHidden("group_name", "$group_name", function ($str, &$vdata) {
            $vdata['group_name'] = $str;
        }, null);
        $exit_form->addHidden("username", "$username", function ($str, &$vdata) {
            $vdata['username'] = $str;
        }, null);
        $exit_form->handle = function (&$vdata) {
            $group_name = $vdata['group_name'];
            $username = $vdata['username'];
            DB::delete("delete from group_info where username='{$username}' and group_name='{$group_name}'");
        };
        $exit_form->submit_button_config['class_str'] = "btn btn-success btn-sm";
        if ($each_row['state'] == 'in')
            $exit_form->submit_button_config['text'] = "退出群组";
        else
            $exit_form->submit_button_config['text'] = "放弃申请";
        $exit_form->submit_button_config['smart_confirm'] = "yes";
        $exit_form->runAtServer();
        $group_exit_forms[$group_name] = $exit_form;
    }

    /*$group_view_forms = array();//用于控制加入群组
    foreach (DB::selectAll("select * from group_description") as $each_group) {
        if ($each_group['group_type'] == "private")
            continue;//组不开放
        if (count(DB::selectAll("select * from group_info where group_name='{$each_group['group_name']}' and username='{$username}'")) > 0)
            continue;//已经在组中
        $group_name = $each_group['group_name'];
        $group_type = $each_group['group_type'];
        $form_name = "join_group_{$group_name}_{$username}";
        $join_form = new UOJForm($form_name);
        $join_form->addHidden("group_name", "$group_name", function ($str, &$vdata) {
            $vdata['group_name'] = $str;
        }, null);
        $join_form->addHidden("username", "$username", function ($str, &$vdata) {
            $vdata['username'] = $str;
        }, null);
        $join_form->addHidden("group_type", "$group_type", function ($str, &$vdata) {
            $vdata['group_type'] = $str;
        }, null);
        if ($each_group['group_type'] == 'public')
            $join_form->submit_button_config['text'] = "加入群组";
        else if ($each_group['group_type'] == 'protected')
            $join_form->submit_button_config['text'] = "申请加入";
        $join_form->handle = function ($vdata) {
            if ($vdata['group_type'] == 'public')
                DB::insert("insert into group_info (group_name, username,is_admin, state)   values ('{$vdata['group_name']}', '{$vdata['username']}','no','in')");
            else if ($vdata['group_type'] == 'protected')
                DB::insert("insert into group_info (group_name, username,is_admin, state)   values ('{$vdata['group_name']}', '{$vdata['username']}','no','waiting')");
        };
        $join_form->runAtServer();
        $group_view_forms[$group_name] = $join_form;

    }
    foreach (DB::selectAll("select * from group_info where username='{$username}' and is_admin='yes'") as $each_row) {
        $group_name = $each_row['group_name'];
        $username = $each_row['username'];
        $group_id = DB::selectFirst("select * from group_description where group_name='{$group_name}'")['group_id'];
        $filter_form_name = "filter_group_{$group_name}_{$username}";
        $filter_form = new UOJForm($filter_form_name);
        $filter_form->addHidden("group_name", "$group_name", function ($str, &$vdata) {
            $vdata['group_name'] = $str;
        }, null);
        $filter_form->addHidden("username", "$username", function ($str, &$vdata) {
            $vdata['username'] = $str;
        }, null);
        $filter_form->addSelect("state",array('all'=>'全部','in'=>'群组中','waiting'=>'等待审核'),'筛选',(isset($_GET['group_name']) && $_GET['group_name']==$group_name&&isset($_GET['state']) )?$_GET['state']:"all");
        $filter_form->addInput('filter_user','text','用户(部分)名称',(isset($_GET['group_name']) && $_GET['group_name']==$group_name)?$_GET['filter_user']:"",
            function ($str,&$vdata){
                $vdata['filter_user']=$str;
                return '';
            },null);
        $filter_form->handle=function (&$vdata) use ($filter_form,$base_URL){
            $filter_form->succ_href=$base_URL."?".'group_name='.$vdata['group_name'].'&'.'state='.$_POST['state'].'&'.'filter_user='.$vdata['filter_user'];
        };
        $filter_form->submit_button_config['class_str'] = "btn btn-success btn-sm";
        $filter_form->submit_button_config['text'] = "筛选";
        $filter_form->runAtServer();




        $operate_form_name = "operate_group_{$group_name}_{$username}";
        $operate_form=new  UOJForm($operate_form_name);
        $operate_form->succ_href=$base_URL."?group_name=".$group_name;
        $operate_form->appendHTML("<div class='table-responsive' width='100%'>");
        $operate_form->appendHTML("<div style=\"width:35%;display: inherit;float: left;\" name='$group_name' onchange=\"javascript:switch_group_operation(this,\"$group_name\")\">");
        $operate_form->addSelect("group_operation",array('add'=>'添加','del'=>'删除','modify'=>'授权'),'操作','add');
        $operate_form->appendHTML("</div>");
        $operate_form->appendHTML("<div style=\"width:45%;display:inherit;float: left;\" id='{$group_name}_suboperation_group_is_admin'> ");
        $operate_form->addSelect("group_is_admin",array(
            'yes'=>"群管理员",
            'no'=>"一般组员",
        ),"设置为","no");
        $operate_form->appendHTML("</div>");
        $operate_form->appendHTML("<div style=\"width:14%;display:inherit;float: left;\" id='{$group_name}_suboperation_group_form_users'> ");
        $operate_form->addTextArea("group_form_users", '用户', "", function($str, &$vdata){
            $users = array();
            foreach (explode("\n", $str) as $line_id => $raw_line) {
                $username = trim($raw_line);//移除空格等
                if ($username == '') {
                    continue;
                }
                //检查对象是否存在
                if(!queryUser($username)){
                    return "User {$username} 不存在，请检查输入！（出错：第{$line_id}行）";
                }
                $users[] = $username;
            }
            $vdata['users'] = $users;
            return '';
        },null);
        $operate_form->appendHTML("</div>");
        $operate_form->appendHTML("</div>");


        $operate_form->appendHTML('<div class="table-responsive" style="display: inherit;" width:"100%" >');
        $operate_form->appendHTML('<table class="table table-bordered table-hover table-striped table-text-center" style="display: inherit" width="100%">');
        $operate_form->appendHTML('<thead style="min-width:100%;">');
        $operate_form->appendHTML('<tr style="width:100%;">S');
        $operate_form->appendHTML('<th style="width:20em;"> rank </th>');
        $operate_form->appendHTML( '<th style="width:20em;">用户名</th>');
        $operate_form->appendHTML( '<th style="width:20em;">权限</th>');
        $operate_form->appendHTML( '<th style="width:20em;">状态</th>');
        $operate_form->appendHTML( '<th style="width:20em;">');
        $operate_form->appendHTML("<input type=\"checkbox\"  name=\"all_{$operate_form_name}\" onchange=\"javascript:select_group_all(this, '{$operate_form_name}')\">");
        $operate_form->appendHTML('</th>');
        $operate_form->appendHTML('</tr>');
        $operate_form->appendHTML('</thead>');
        $operate_form->appendHTML('<tbody>');



        $local_tmp_users=array();
        if(isset($_GET['group_name']) && $_GET['group_name']==$group_name){
            if(!isset($_GET['state']) ||$_GET['state']==='all'){
                $local_tmp_users=DB::selectAll('select * from group_info where group_name="'.$group_name.'" and username like "'.$_GET['filter_user'].'%"');
            }else{
                $local_tmp_users=DB::selectAll('select * from group_info where group_name="'.$group_name.'" and state=\''.$_GET['state'].'\' and username like "'.$_GET['filter_user'].'%"');
            }
        }
        else{
            $local_tmp_users=DB::selectAll('select * from group_info where group_name="'.$group_name.'"');
        }
        foreach ($local_tmp_users as $index => $local_tmp_user)
        {
            $operate_form->appendHTML('<tr>');
            $operate_form->appendHTML('<td>'.($index+1).'</td>');
            $operate_form->appendHTML('<td>'.getUserLink($local_tmp_user['username']).'</td>');
            $operate_form->appendHTML('<td>'.($local_tmp_user['state']=='in'?($local_tmp_user['is_admin']=='yes'?"管理员":"组员"):"").'</td>');
            $operate_form->appendHTML('<td>'.($local_tmp_user['state']=='in'?"已入组":"等待审核").'</td>');
            $operate_form->appendHTML('<td class="'.$operate_form_name.'_check_td">');
            $operate_form->addCheckBox("check_item_".$local_tmp_user['username'],'','');
            $operate_form->appendHTML('</td>');
            $operate_form->appendHTML('</tr>');
        }
        $operate_form->appendHTML('</tbody>');
        $operate_form->appendHTML('</table>');
        $operate_form->appendHTML('</div>');

        $operate_form->submit_button_config['text']='发送';
        $operate_form->handle=function ($vdata) use($group_name){
            $checked=array();
            foreach ($_POST as $key=>$each_item){
                if(substr($key,0,11)=='check_item_' && $each_item=='on'){
                    $checked[]=substr($key,11);

                };
            }
            switch ($_POST['group_operation']) {
                case 'add':
                    foreach ($vdata['users'] as  $eachusername){
                        DB::delete("delete from group_info where group_name = '{$group_name}' and username = '{$eachusername}'");
                        DB::insert("insert into group_info (group_name, username,is_admin, state)   values ('{$group_name}', '{$eachusername}','{$_POST['group_is_admin']}','in')");
                    }
                    break;
                case 'del':
                    foreach ($checked as  $eachusername){
                        DB::delete("delete from group_info where group_name = '{$group_name}' and username = '{$eachusername}'");
                    }
                    break;
                case 'modify':
                    foreach ($checked as  $eachusername){
                        DB::delete("delete from group_info where group_name = '{$group_name}' and username = '{$eachusername}'");
                        DB::insert("insert into group_info (group_name, username,is_admin, state)   values ('{$group_name}', '{$eachusername}','{$_POST['group_is_admin']}','in')");
                    }
                    break;
            }
        };
        $operate_form->runAtServer();



        $group_manage_forms[$group_name]=array('filter'=>$filter_form,'operate'=>$operate_form);
    }*/
}?>

<script type="text/javascript">
    function select_group_all(obj,group_name) {
        console.log(obj);
        console.log(obj.value);
        console.log(group_name + '_check_td');
        var c = document.getElementsByClassName(group_name + '_check_td');
        console.log(c.length);
        for (var i = 0; i < c.length; i++) {
            console.log(c[i].getElementsByTagName('input')[0]);
            console.log(c[i].getElementsByTagName('input')[0].value);
            c[i].getElementsByTagName('input')[0].checked = obj.checked;
            console.log(c[i].getElementsByTagName('input')[0].value);
        }
    }
</script>
<script type='text/javascript'>
    function operate_group(group_name){
        all_board=document.getElementsByClassName('operate_group_board');
        var open_board="operate_"+group_name+"_board";
        for(var i=0;i<all_board.length;i++){
            if(all_board[i].id===open_board){
                if(all_board[i].style.display==='block') {
                    all_board[i].style.display = 'none';
                    this.value="收起";
                }else {
                    all_board[i].style.display = 'block';
                    this.value="管理";
                }
            }else{
                all_board[i].style.display='none';
            }
        }
    }
</script>
<script type='text/javascript'>
    function switch_group_operation(obj,form_name) {
        var select=obj.getElementsByTagName('select')[0];
        var suboperation_group_is_admin=document.getElementById(form_name+'_suboperation_group_is_admin');
        var suboperation_group_form_users=document.getElementById(form_name+'_suboperation_group_form_users');
        var oper=select.options[select.selectedIndex].value;
        switch(oper){
            case "add":
                suboperation_group_is_admin.style.display="inherit";
                suboperation_group_form_users.style.display="inherit";
                break;
            case "del:
                suboperation_group_is_admin.style.display="none";
                suboperation_group_form_users.style.display="none";
                break;
            case "modify":
                suboperation_group_is_admin.style.display="inherit";
                suboperation_group_form_users.style.display="none";
                break;
        }
    }
</script>
<?php if (validateUsername($username) && ($user = queryUser($username))): ?>
    <?php
    echoUOJPageHeader($user['username'] . ' - ' . UOJLocale::get('user profile'))
    ?>
    <?php
    $esc_email = HTML::escape($user['email']);
    $esc_qq = HTML::escape($user['qq'] != 0 ? $user['qq'] : 'Unfilled');
    $esc_sex = HTML::escape($user['sex']);
    $col_sex="color:blue";
    if($esc_sex == "M") {
        $esc_sex="♂";
        $col_sex="color:blue";
    }
    else if($esc_sex == "F") {
        $esc_sex="♀";
        $col_sex="color:red";
    } else {
        $esc_sex="";
        $col_sex="color:black";
    }
    $esc_motto = HTML::escape($user['motto']);
    ?>
    <div class="panel panel-info">
        <div class="panel-heading">
            <h2 class="panel-title"><?= UOJLocale::get('user profile') ?></h2>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4 col-md-push-8">
                    <img class="media-object img-thumbnail center-block" alt="<?= $user['username'] ?> Avatar" src="<?= HTML::avatar_addr($user, 256) ?>" />
                </div>
                <div class="col-md-8 col-md-pull-4">
                    <h2><span class="uoj-honor" data-rating="<?= $user['rating'] ?>"><?= $user['username'] ?></span> <span><strong style="<?= $col_sex ?>"><?= $esc_sex ?></strong></span></h2>
                    <div class="list-group">
                        <div class="list-group-item">
                            <h4 class="list-group-item-heading"><?= UOJLocale::get('rating') ?></h4>
                            <p class="list-group-item-text"><strong style="color:red"><?= $user['rating'] ?></strong></p>
                        </div>
                        <div class="list-group-item">
                            <h4 class="list-group-item-heading"><?= UOJLocale::get('email') ?></h4>
                            <p class="list-group-item-text"><?= $esc_email ?></p>
                        </div>
                        <div class="list-group-item">
                            <h4 class="list-group-item-heading"><?= UOJLocale::get('QQ') ?></h4>
                            <p class="list-group-item-text"><?= $esc_qq ?></p>
                        </div>
                        <div class="list-group-item">
                            <h4 class="list-group-item-heading"><?= UOJLocale::get('motto') ?></h4>
                            <p class="list-group-item-text"><?= $esc_motto ?></p>
                        </div>
                        <?php if (isSuperUser($myUser)): ?>
                            <div class="list-group-item">
                                <h4 class="list-group-item-heading">register time</h4>
                                <p class="list-group-item-text"><?= $user['register_time'] ?></p>
                            </div>
                            <div class="list-group-item">
                                <h4 class="list-group-item-heading">remote_addr</h4>
                                <p class="list-group-item-text"><?= $user['remote_addr'] ?></p>
                            </div>
                            <div class="list-group-item">
                                <h4 class="list-group-item-heading">http_x_forwarded_for</h4>
                                <p class="list-group-item-text"><?= $user['http_x_forwarded_for'] ?></p>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
            <?php if (Auth::check()): ?>
                <?php if (Auth::id() != $user['username']): ?>
                    <a type="button" class="btn btn-info btn-sm" href="/user/msg?enter=<?= $user['username'] ?>"><span class="glyphicon glyphicon-envelope"></span>
                        <?= UOJLocale::get('send private message') ?>
                    </a>
                <?php else: ?>
                    <a type="button" class="btn btn-info btn-sm" href="/user/modify-profile">
                        <span class="glyphicon glyphicon-pencil"></span>
                        <?= UOJLocale::get('modify my profile') ?>
                    </a>
                <?php endif ?>
            <?php endif ?>

            <a type="button" class="btn btn-info btn-sm" href="<?= HTML::blog_url($user['username'], '/') ?>">
                <span class="glyphicon glyphicon-arrow-right"></span>
                <?= UOJLocale::get('visit his blog', $username) ?>
            </a>

            <div class="list-group-item">
                <h4 class="list-group-item-heading">群组</h4>
                <br>
                <?php
                if(Auth::check()&&Auth::id()==$username) {

                    global $group_manage_forms;
                    foreach ($group_manage_forms as $group_name => $form_array) {
                        if(isset($_GET['group_name']) && ($_GET['group_name']===$group_name)) {
                            echo "<div class='operate_group_board' id='operate_{$group_name}_board' style='display:inherit;'>";
                        }else {
                            echo "<div class='operate_group_board' id='operate_{$group_name}_board' style='display:none;'>";
                        }
                        $form_array['filter']->printHTML();
                        $form_array['operate']->printHTML();
                        echo "</div>";
                    }

                }
                ?>
                <p class="list-group-item-text">

                <h6 class="list-group-item-heading">已申请加入的群组</h6>

                <?php

                function show_exit_table($username)
                {
                    global $group_exit_forms;
                    $header_row = '';
                    $header_row .= '<tr>';
                    $header_row .= '<th style="width: 5em;">群组id</th>';
                    $header_row .= '<th style="width: 20em;">群组名</th>';
                    $header_row .= '<th style="width: 20em;">加入状态</th>';
                    $header_row .= '<th style="width: 20em;">是否是管理员</th>';
                    $header_row .= '<th style="width: 20em;">群组人数</th>';
                    if(Auth::check()&&Auth::id()==$username) {
                        $header_row .= '<th style="width: 10em;">操作</th>';
                    }
                    $header_row .= '</tr>';
                    $print_row = function ($each_row, $index) use ($group_exit_forms) {
                        $group_name = $each_row['group_name'];
                        $username = $each_row['username'];
                        $cur_group = DB::selectFirst("select * from group_description where group_name='{$group_name}'");
                        $count = count(DB::selectAll("select * from group_info where group_name= '{$group_name}' and state='in'"));
                        echo '<tr>';
                        echo '<td>' . $cur_group['group_id'] . '</td>';
                        echo '<td>' . $group_name . '</td>';
                        echo '<td>' . ($each_row['state'] == 'in' ? "已入群" : "等待验证") . '</td>';
                        echo '<td>' . ($each_row['is_admin'] == 'yes' ? "群组管理员" : "否") . '</td>';
                        echo '<td>' . $count . '</td>';

                        if(Auth::check()&&Auth::id()==$username) {
                            echo '<td>';
                            $group_exit_forms[$group_name]->printHTML();
                            echo '</td>';
                        }
                        echo '</tr>';

                    };
                    $col_names = array('*');

                    $config = array(
                        'echo_full' => 'yes',
                        'get_row_index' => "yes"
                    );
                    echoLongTable($col_names, 'group_info', 'username =\'' . $username . '\'', "", $header_row, $print_row, $config);
                }

                show_exit_table($user['username']);
                /*
                echo '<h6 class="list-group-item-heading">可申请加入的群组</h6>';
                if(Auth::check()&&Auth::id()==$username) {
                    function show_join_table($username)
                    {
                        global $group_view_forms;
                        $header_row = '';
                        $header_row .= '<tr>';
                        $header_row .= '<th style="width: 5em;">群组id</th>';
                        $header_row .= '<th style="width: 20em;">群组名</th>';
                        $header_row .= '<th style="width: 10em;">群组类型</th>';
                        $header_row .= '<th style="width: 40em;">群组管理员</th>';
                        $header_row .= '<th style="width: 10em;">群组人数</th>';
                        $header_row .= '<th style="width: 10em;">操作</th>';
                        $header_row .= '</tr>';
                        $count = 0;
                        $print_row = function ($each_row, $index) use ($group_view_forms, &$count, $username) {
                            if ($each_row['group_type'] == "private")
                                return;//组不开放
                            $exist = count(DB::selectAll("select * from group_info where group_name='{$each_row['group_name']}' and username='{$username}'"));
                            if ($exist > 0)
                                return;//已经在组中
                            $group_name = $each_row['group_name'];
                            $group_admins_arr = DB::selectAll('select username from group_info where group_name=\'' . $group_name . '\' and is_admin="yes"');
                            $group_admins = "";
                            foreach ($group_admins_arr as $each) {
                                if ($group_admins != '')
                                    $group_admins .= ',';
                                $group_admins .= getUserLink($each['username']);
                            }
                            $count = count(DB::selectAll("select * from group_info where group_name= '{$group_name}'"));
                            echo '<tr>';
                            echo '<td>' . $each_row['group_id'] . '</td>';
                            echo '<td>' . $group_name . '</td>';
                            echo '<td>' . ($each_row['group_type'] == "public" ? "自由加入" : "需要验证") . '</td>';
                            echo '<td>' . $group_admins . '</td>';
                            echo '<td>' . $count . '</td>';
                            echo '<td>';
                            $group_join_forms[$group_name]->printHTML();
                            echo '</td>';
                            echo '</tr>';
                        };
                        $col_names = array('*');

                        $config = array(
                            'echo_full' => 'yes',
                            'get_row_index' => "yes"
                        );
                        echoLongTable($col_names, 'group_description', 'group_name !=""', "", $header_row, $print_row, $config);
                    }

                    show_join_table($user['username']);
                }*/
                ?>
                </p>


            </div>

            <div class="top-buffer-lg"></div>
            <div class="list-group">
                <div class="list-group-item">
                    <h4 class="list-group-item-heading"><?= UOJLocale::get('rating changes') ?></h4>
                    <div class="list-group-item-text" id="rating-plot" style="height:500px;"></div>
                </div>
                <div class="list-group-item">
                    <?php
                    $ac_problems = DB::selectAll("select problem_id from best_ac_submissions where submitter = '{$user['username']}'");
                    ?>
                    <h4 class="list-group-item-heading"><?= UOJLocale::get('accepted problems').'：'.UOJLocale::get('n problems in total', count($ac_problems))?> </h4>
                    <p class="list-group-item-text">
                        <?php
                        foreach ($ac_problems as $problem) {
                            echo '<a href="/problem/', $problem['problem_id'], '" style="display:inline-block; width:4em;">', $problem['problem_id'], '</a>';
                        }
                        if (empty($ac_problems)) {
                            echo UOJLocale::get('none');
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        var rating_data = [[
            <?php
            $user_rating_min = $user_rating_max = 1500;
            $result = mysql_query("select contest_id, rank, user_rating from contests_registrants where username = '{$user['username']}' and has_participated = 1 order by contest_id");
            $is_first_row = true;
            $last_rating = 1500;
            while ($row = mysql_fetch_array($result)) {
                $contest = queryContest($row['contest_id']);
                $rating_delta = $row['user_rating'] - $last_rating;
                if (!$is_first_row) {
                    echo "[$last_contest_time, {$row['user_rating']}, $last_contest_id, '$last_contest_name', $last_rank, $rating_delta],";
                } else {
                    $is_first_row = false;
                }
                $contest_start_time = new DateTime($contest['start_time']);
                $last_contest_time = ($contest_start_time->getTimestamp() + $contest_start_time->getOffset()) * 1000;
                $last_contest_name = $contest['name'];
                $last_contest_id = $contest['id'];
                $last_rank = $row['rank'];
                $last_rating = $row['user_rating'];

                if ($row['user_rating'] < $user_rating_min) {
                    $user_rating_min = $row['user_rating'];
                }
                if ($row['user_rating'] > $user_rating_max) {
                    $user_rating_max = $row['user_rating'];
                }
            }
            if ($is_first_row) {
                $time_now_stamp = (UOJTime::$time_now->getTimestamp() + UOJTime::$time_now->getOffset()) * 1000;
                echo "[{$time_now_stamp}, {$user['rating']}, 0]";
            } else {
                $rating_delta = $user['rating'] - $last_rating;
                echo "[$last_contest_time, {$user['rating']}, $last_contest_id, '$last_contest_name', $last_rank, $rating_delta]";
            }
            if ($user['rating'] < $user_rating_min) {
                $user_rating_min = $user['rating'];
            }
            if ($user['rating'] > $user_rating_max) {
                $user_rating_max = $user['rating'];
            }

            $user_rating_min -= 400;
            $user_rating_max += 400;
            ?>
        ]];
        var rating_plot = $.plot($("#rating-plot"), [{
            color: "#3850eb",
            label: "<?= $user['username'] ?>",
            data: rating_data[0]
        }], {
            series: {
                lines: {
                    show: true
                },
                points: {
                    show: true
                }
            },
            xaxis: {
                mode: "time"
            },
            yaxis: {
                min: <?= $user_rating_min ?>,
                max: <?= $user_rating_max ?>
            },
            legend: {
                labelFormatter: function(username) {
                    return getUserLink(username, <?= $user['rating'] ?>, false);
                }
            },
            grid: {
                clickable: true,
                hoverable: true
            },
            hooks: {
                drawBackground: [
                    function(plot, ctx) {
                        var plotOffset = plot.getPlotOffset();
                        for (var y = 0; y < plot.height(); y++) {
                            var rating = <?= $user_rating_max ?> - <?= $user_rating_max - $user_rating_min ?> * y / plot.height();
                            ctx.fillStyle = getColOfRating(rating);
                            ctx.fillRect(plotOffset.left, plotOffset.top + y, plot.width(), Math.min(5, plot.height() - y));
                        }
                    }
                ]
            }
        });

        function showTooltip(x, y, contents) {
            $('<div id="rating-tooltip">' + contents + '</div>').css({
                position: 'absolute',
                display: 'none',
                top: y - 20,
                left: x + 10,
                border: '1px solid #fdd',
                padding: '2px',
                'font-size' : '11px',
                'background-color': '#fee',
                opacity: 0.80
            }).appendTo("body").fadeIn(200);
        }

        var prev = -1;
        function onHoverRating(event, pos, item) {
            if (prev != item.dataIndex) {
                $("#rating-tooltip").remove();
                var params = rating_data[item.seriesIndex][item.dataIndex];

                var total = params[1];
                var contestId = params[2];
                if (contestId != 0) {
                    var change = params[5] > 0 ? "+" + params[5] : params[5];
                    var contestName = params[3];
                    var rank = params[4];
                    var html = "= " + total + " (" + change + "), <br/>"
                        + "Rank: " + rank + "<br/>"
                        + '<a href="' + '/contest/' + contestId + '">' + contestName + '</a>';
                } else {
                    var html = "= " + total + "<br/>"
                        + "Unrated";
                }
                showTooltip(item.pageX, item.pageY, html);
                prev = item.dataIndex;
            }
        }
        $("#rating-plot").bind("plothover", function (event, pos, item) {
            if (item) {
                onHoverRating(event, pos, item);
            }
        });
        $("#rating-plot").bind("plotclick", function (event, pos, item) {
            if (item && prev == -1) {
                onHoverRating(event, pos, item);
            } else {
                $("#rating-tooltip").fadeOut(200);
                prev = -1;
            }
        });
    </script>
<?php else: ?>
    <?php echoUOJPageHeader('不存在该用户' . ' - 用户信息') ?>
    <div class="panel panel-danger">
        <div class="panel-heading">用户信息</div>
        <div class="panel-body">
            <h4>不存在该用户</h4>
        </div>
    </div>
<?php endif ?>

<?php echoUOJPageFooter() ?>
