<?php

/**
 * 生zbog1.8用的服务端脚本
 * @param string $hash
 * @return string
 */

function WP4ToZbp_Make_wp4($hash = '')
{

  $hash = md5($hash);
  $wp4 = '<?php   $tophp_pass = "' . $hash . '";';
  $wp4 .= file_get_contents(dirname(__FILE__) . '/html/wp4');

  return $wp4;
}

/**
 * 用curl发送post数据
 * @param $url
 * @param $PostData
 * @return mixed
 */
function WP4ToZbpPostData($url, $PostData)
{
  if (empty($url) || empty($PostData))
    return '参数不能为空';
  $network = Network::Create();
  if (!$network) throw new Exception('主机没有开启网络功能');
  $network->open("POST", $url);
  $network->send($PostData);
  $output = $network->responseText;
  return trim(RemoveBOM($output));
}

/**
 * 检查服务度数据完整性
 * @param $data
 * @return bool|mixed|string
 */
function WP4ToZbpCheckData($data)
{
  $header = substr($data, 0, 3);
  $body = substr($data, 3, -3);
  $foot = substr($data, -3);

  if ($header === '-->' && $foot === '<--') {
    $body = json_decode($body, true);
    if (!$body) {
      echo '无法解析为json，可能含有特殊字符!';
      return false;
    } else {
      return $body;
    }
    return $body;
  } elseif ($header <> '-->' || $foot <> '<--') {
    echo "返回信息错误:";
    return false;
  } else {
    return false;
  }
}

/**
 * 在数据库中建立任务
 * @param $time
 * @param $url
 * @param $type
 * @param $min
 * @param $max
 */
function WP4ToZbpBuildJob($time, $url, $type, $min, $max)
{
  global $zbp;
  $o = new WP4ToZbp();
  $o->ID = 0;
  $o->Time = $time;
  $o->Url = $url;
  $o->Type = $type;
  $o->Min = (int)$min;
  $o->Max = $max;
  $o->Save();
}

/**
 * 将任务分解
 * @param $time
 * @param $url
 * @param $type
 * @param $min
 * @param $max
 */
function WP4ToZbpBuild_for($time, $url, $type, $min, $max)
{
  global $zbp;
  $MaxCoun = $zbp->Config('WP4ToZbp')->MaxCoun;
  for ($limit = $min; $limit <= $max; $limit += $MaxCoun) {
    WP4ToZbpBuildJob($time, $url, $type, $limit, $max);
  }
}

/**
 * 获取任务列表
 * @param null $where
 * @return array
 */
function WP4ToZbp_Getlist($where = null)
{
  global  $zbp;
  if (empty($where)) {
    $where = array();
  }
  $sql = $zbp->db->sql->Select($zbp->table['plugin_WP4ToZbp'], array('*'), $where, null, null, null);
  #echo $sql;exit;
  return $zbp->GetListType('WP4ToZbp', $sql);
}

/**
 * 从服务度获取数据，建立任务
 * @param $time
 * @param $url
 * @param $hash
 */
function WP4ToZbp_todo_s0(&$time, $url, $hash)
{
  global $zbp;
  #echo $hash;
  $MaxCoun = $zbp->Config('WP4ToZbp')->MaxCoun;
  $PostData = "hash=$hash&todo=s0";
  $str = WP4ToZbpPostData($url, $PostData);
  $str_body = WP4ToZbpCheckData($str);

  if (!$str_body) {
    echo $str;
    exit;
  } else {
    $sql = $zbp->db->sql->Delete($zbp->table['plugin_WP4ToZbp'], array(array('=', 'Result', '')));
    $zbp->db->Delete($sql); #echo $sql ;exit;
    foreach ($str_body as $type => $value) {
      if ($value['count'] <= $MaxCoun) {
        WP4ToZbpBuildJob($time, $url, $type, $value['min'], $value['max']);
      } else {
        WP4ToZbpBuild_for($time, $url, $type, $value['min'], $value['max']);
      }
    }
  } //
}


/**
 *  从服务端导出数据
 * @param $job
 * @param $hash
 * @param $count
 */
function WP4ToZbp_todo_s1(&$job, $hash, $count)
{
  global $zbp, $WP4ToZbp_source_host;


  $tempu = parse_url($job->Url);
  $WP4ToZbp_source_host = $tempu['host'];
  $PostData = "hash=$hash&todo=s1&count=$count&type=$job->Type&min=$job->Min&max=$job->Max";
  $str = WP4ToZbpPostData($job->Url, $PostData);
  $str_body = WP4ToZbpCheckData($str);

  if (!$str_body) {
    echo ':' . $str . '/';
    exit;
  } else {

    //写入数据库

    $str = $str_body;
    $Typename = $str['typename'];
    $o = new $Typename;
    $i = 0;
    //print_r($str['data'][0]);exit;
    foreach ($str['data'] as $data) {
      $i++;
      $o->ID = 0;  //丢掉ID重新加载，免得一直updata
      $o->LoadInfoByID($data['ID']);
      foreach ($data as $key => $vel) {
        if ($key == 'ID') continue;
        $o->$key = $vel;
      }
      if ($Typename == 'Member') {
        if ($o->ID == $zbp->user->ID || $o->Name == $zbp->user->Name) {
          $o->ID = 0;
          $o->Name = $o->Name . '0';
          $o->Guid = $o->Password = GetGuid();
          $o->Save();
          continue;
        }
      }

      if ($o->ID == 0 && $data['ID'] <> 0) {
        $o->ID = $data['ID'];
        WP4ToZbp_Insert($o);
      } else {
        $o->Save();
      }
    }

    $job->Result = time();
    $job->ResultCount = $i;
    $job->Save();
  }
}

/**
 * 插入数据
 * @param $Type
 */
function WP4ToZbp_Insert(&$Type)
{
  global $zbp, $WP4ToZbp_source_host;
  $datainfo = $Type->GetDataInfo();
  $keys = array();
  foreach ($datainfo as $key => $value) {
    if (!is_array($value) || count($value) != 4) continue;
    $keys[] = $value[0];
  }
  $keyvalue = array_fill_keys($keys, '');

  foreach ($datainfo as $key => $value) {
    if (!is_array($value) || count($value) != 4) continue;
    if ($value[1] == 'boolean') {
      $keyvalue[$value[0]] = (integer)$Type->$key;
    } elseif ($value[1] == 'integer') {
      $keyvalue[$value[0]] = (integer)$Type->$key;
    } elseif ($value[1] == 'float') {
      $keyvalue[$value[0]] = (float)$Type->$key;
    } elseif ($value[1] == 'double') {
      $keyvalue[$value[0]] = (double)$Type->$key;
    } elseif ($value[1] == 'string') {
      if ($key == 'Meta') {
        $keyvalue[$value[0]] = $Type->$key;
      } else {
        $keyvalue[$value[0]] = str_replace($WP4ToZbp_source_host, '{#ZC_BLOG_HOST#}', $Type->$key);
      }
    } else {
      $keyvalue[$value[0]] = $Type->$key;
    }
  }

  $sql = $zbp->db->sql->Insert($Type->GetTable(), $keyvalue);
  //echo $sql ;//exit;
  $zbp->db->Insert($sql);
}

class WP4ToZbp extends Base
{


  function __construct()
  {
    global $zbp;

    parent::__construct($zbp->table['plugin_WP4ToZbp'], $zbp->datainfo['plugin_WP4ToZbp']);
  }

  /**
   * @param $name
   * @return mixed|string
   */
  public function __get($name)
  {
    global $zbp;
    if ($name == 'Times') {
      return date('Y-m-d H:i:s', $this->Time);
    }
    if ($name == 'ID') {
      return $this->data['ID'];
    }

    return parent::__get($name);
  }
}
