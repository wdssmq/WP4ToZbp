<?php
/**
 * require报错，改用require_once 2014-9-21
 * 日志重复 2014-12-14;
 */
if (!isset($zbp)) {
  exit;
}
#注册插件
RegisterPlugin("WP4ToZbp", "ActivePlugin_WP4ToZbp");

$zbp->table['plugin_WP4ToZbp'] = '%pre%plugin_WP4ToZbp';
$zbp->datainfo['plugin_WP4ToZbp'] = array(
  'ID' => array('ID', 'integer', 250, 0),
  'Time' => array('Time', 'integer', '', 0),
  'Url' => array('Url', 'string', '', ''),
  'Type' => array('Type', 'string', '', ''),
  'Min' => array('Min', 'integer', '', 0),
  'Max' => array('Max', 'integer', '', 0),
  'Result' => array('Result', 'string', '', ''),
  'ResultCount' => array('ResultCount', 'integer', '', 0),
);


function ActivePlugin_WP4ToZbp()
{ }

function InstallPlugin_WP4ToZbp()
{
  global $zbp;
  if (!$zbp->db->ExistTable($GLOBALS['table']['plugin_WP4ToZbp'])) {
    $s = $zbp->db->sql->CreateTable($GLOBALS['table']['plugin_WP4ToZbp'], $GLOBALS['datainfo']['plugin_WP4ToZbp']);
    $zbp->db->QueryMulit($s);
  }
  $zbp->Config('WP4ToZbp')->Ver = '2015-1-10-0.2';
  $zbp->Config('WP4ToZbp')->Hash = $zbp->guid . '2015-1-10-0.2';
  $zbp->Config('WP4ToZbp')->MaxCoun = 100;
  $zbp->SaveConfig('WP4ToZbp');
}

function UninstallPlugin_WP4ToZbp()
{ }
