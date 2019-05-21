<?php
require '../../../zb_system/function/c_system_base.php';
require '../../../zb_system/function/c_system_admin.php';
require 'function.php';
$zbp->Load();
$action = 'root';
if (!$zbp->CheckRights($action)) {
  $zbp->ShowError(6);
  die();
}
if (!$zbp->CheckPlugin('WP4ToZbp')) {
  $zbp->ShowError(48);
  die();
}

$setup = GetVars('s', 'GET');
$text = '';
// echo __LINE__ . ":<br />";
// var_dump($zbp->db);
// echo "<br />";
// die();

$dbtype = $zbp->db->type;
switch ($setup) {
  case 'make':
    WP4ToZbp_Make_();
    break;
  case 's1': //生成任务列表
    $text =  WP4ToZbp_s1();
    break;
  case 's2':        //触发js，执行导入
    $text =  WP4ToZbp_s2();
    break;
  case 's3':
    //后台线程导入 json
    $text = WP4ToZbp_s3();
    break;
  case 's4':
    //显示执行结果 json
    WP4ToZbp_s4();
    break;
  default:
    ///生成服务端文件 ,提交RemoteUrl
    $text =  WP4ToZbp_s0();
    break;
}
$blogtitle = 'WP4ToZbp';
require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';
?>
<div id="divMain">
  <script type="text/javascript">
  function Post(i) {
    if (i >= IDArray.length) {
      alert('任务完成！');
      return;
    }

    $.ajax({
      url: "main.php?s=s3&Time=" + t_time + "&ID=" + IDArray[i],
      type: "POST",
      //data: $.extend(that.queue[that.pos], {"count": that.pos + 1, "sum": that.queue.length}),
      dataType: "json",
      error: function(xhr) {
        alert("任务" + i + "失败");
        //that.log("任务" + (that.pos + 1) + "执行失败，出现" + xhr.status + "错误，具体错误为" + xhr.responseText);
      },

      <?php

                if ($dbtype == 'DbSQLite' || $dbtype == 'DbSQLite3') {
                  ?>success: function(data) {
        GetResult(t_time);
        i++;
        setTimeout("Post(" + i + ")", 1000);
      }
    });

    <?php

          } else {
            ?>success: function(data) {
      GetResult(t_time);
    }
  });
  i++;
  setTimeout("Post(" + i + ")", 5000);
  <?php

      }
      ?>



  }

  function GetResult(time) {
    if (time < 10000) {
      return;
    }
    $.ajax({
      url: "main.php?s=s4&Time=" + time + "&ID=",
      type: "POST",
      dataType: "json",
      success: function(data) {
        var str = '<tr>';
        $.each(data, function(i, v) {

          str += '<td  class="td20" >' + v + '</td>';
        });
        str += "</tr>"
        $("#sss").append(str);
      }
    });
  }
  //GetResult("1420267152");
  </script>
  <div class="divHeader"><?php echo $blogtitle; ?></div>
  <div class="SubMenu">
  </div>
  <div id="divMain2">
    <!--代码-->
    <?php echo $text; ?>
    <div id="ssss">
      <table id="sss">
        <tbody id="ss">
          <tr>
            <td>开始时间</td>
            <td>完成进度</td>
            <td>完成时间</td>
          </tr>
          <tr>
            <td class="td20"><?php echo date('Y-m-d H:i:s'); ?></td>
            <td class="td20"> 0/0</td>
            <td class="td20"><?php echo date('Y-m-d H:i:s'); ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <?php if ($dbtype == 'DbSQLite' || $dbtype == 'DbSQLite3') {
      echo 'SQLite型数据库，单线程导入';
    } ?><p>导入时请不要关闭本页</p>
</div>
</div>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();


$url = "http://127.0.0.1:8091/0.asp";
$hash = md5($zbp->Config('WP4ToZbp')->Hash . 'wp4');
$count = $zbp->Config('WP4ToZbp')->MaxCoun;
$time = time();

function WP4ToZbp_Make_()
{
  global $zbp;

  Header("Content-type: application/octet-stream");
  Header("Content-Disposition: attachment; filename=wp4tozbp.php");
  echo WP4ToZbp_Make_wp4($zbp->Config('WP4ToZbp')->Hash . 'wp4');
  exit;
}

function WP4ToZbp_s0()
{
  global $zbp;
  $filename = $_SERVER["SCRIPT_NAME"];
  $url = $zbp->host . 'wp4tozbp.php';
  $html = <<<html

    <form id="edit" name="edit" method="post" action="$filename?s=s1">
    <p><a href="main.php?s=make" >一 、将脚本将上传至原网站程序的根目录下(点击下载)</a> <br /></p>
    <p>二、输入上传后的URL : <input id="RemoteUrl" class="edit" size="40" name="RemoteUrl" type="text" value="$url" placeholder="$url"/></p>
		<hr /><p>三、<input type="submit" class="button" value="提交" id="btnPost" onclick="" /></p>
	  <p />
	  </form>
html;
  return $html;
}

function WP4ToZbp_s1()
{
  global $zbp;
  $time = time();
  $url = $zbp->host . 'zb_users/plugin/WP4ToZbp/main.php?s=s2&time=' . $time;
  WP4ToZbp_todo_s0($time, GetVars('RemoteUrl'), md5($zbp->Config('WP4ToZbp')->Hash . 'wp4'));  //S获取任务
  // WP4ToZbp_todo_s0($time,"http://wp.wdssmq.tk/wp4tozbp.php",md5($zbp->Config('WP4ToZbp')->Hash.'wp4'));  //S获取任务
  $str =  '<p>任务已经获取完毕,点击进入：</p><p><input type="submit" class="button" value="下一步" id="btnPost" onclick="location.href=\'' . $url . '\'" /></p>';
  return $str;
}

function WP4ToZbp_s2()
{

  global $zbp;
  $time = GetVars('time');
  $sql = $zbp->db->sql->Select($zbp->table['plugin_WP4ToZbp'], array('ID'), array(array('=', 'Time', $time), array('=', 'Result', '')), '', '');
  $reust = $zbp->db->Query($sql);


  foreach ($reust as $key) {
    $strArray[] = $key['ID'];
  }

  if (count($reust) < 1 || count($strArray) < 1) {
    $html = '该任务不存在，或者已经完成';
  } else {

    $html = '<script type="text/javascript">';
    $html .= "var t_time =$time; var IDArray=[" . implode(',', $strArray) . '];';
    $html .= 'Post(0);';
    $html .= '</script> ';
  }
  return $html;
}

function WP4ToZbp_s3()
{
  set_time_limit(0);
  global $zbp;
  $hash = md5($zbp->Config('WP4ToZbp')->Hash . 'wp4');
  $count = $zbp->Config('WP4ToZbp')->MaxCoun;
  $job = WP4ToZbp_Getlist(array(array('=', 'Time', GetVars('Time')), array('=', 'Result', ''), array('=', 'ID', GetVars('ID'))));
  if (count($job) > 0) {
    WP4ToZbp_todo_s1($job[0], $hash, $count);
    echo '{"Result":"ok"}';
    exit;
  } else {
    return '任务不存在或已经执行完毕';
  }
}

function WP4ToZbp_s4()
{
  global $zbp;
  $time = GetVars('Time');
  $reust_conut = $zbp->db->sql->Count($zbp->table['plugin_WP4ToZbp'], array(array('COUNT', '*', 'num')), array(array('=', 'Time', GetVars('Time'))));
  $job_conut = GetValueInArrayByCurrent($zbp->db->Query($reust_conut), 'num');
  $reust_conut = $zbp->db->sql->Count($zbp->table['plugin_WP4ToZbp'], array(array('COUNT', '*', 'num')), array(array('<>', 'Result', ''), array('=', 'Time', GetVars('Time'))));
  $ok_conut = GetValueInArrayByCurrent($zbp->db->Query($reust_conut), 'num');
  echo '{"Time" :"' . date('Y-m-d H:i:s', $time) .  '" ,"Progress" :" ' . "$ok_conut/$job_conut" . '" ,"Lasttime" : "' . date('Y-m-d H:i:s') . '"}';
  exit;
}
?>
