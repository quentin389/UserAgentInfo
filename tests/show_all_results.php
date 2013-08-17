<?php

function test_formatTime($time)
{
  return number_format($time * 1000, 1) . '&nbsp;ms';
}

$start_time = microtime(true);
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../UserAgentInfoPeer.class.php';
$start_time = test_formatTime(microtime(true) - $start_time);

$my_time = microtime(true);
$my_ua_string = UserAgentInfoPeer::getMy()->getUserAgentString();
$my_time = test_formatTime(microtime(true) - $my_time);

$user_agents = explode("\n", trim(file_get_contents(UserAgentInfoConfig::$base_dir . UserAgentInfoConfig::DIR_TESTS . DIRECTORY_SEPARATOR . 'user-agent-examples.txt')));

array_unshift($user_agents,
  $my_ua_string,
  'Random UA 1 (no match): ' . uniqid(null, true),
  'Random UA 2 (no match): ' . uniqid(null, true),
  'Random UA 3 (no match): ' . uniqid(null, true)
);

$results = array();

foreach ($user_agents as $i => $one_ua)
{
  $t = microtime(true);
  $mua = UserAgentInfoPeer::getOther($one_ua, true);
  $t = test_formatTime(microtime(true) - $t);
  
  if ($mua->isMobileAndroid())
  {
    $mobile_info = $mua->isMobileTablet() ? 'Android&nbsp;tablet' : 'Android';
  }
  elseif ($mua->isMobileAppleIos())
  {
    $mobile_info = $mua->isMobileTablet() ? 'iPad' : 'iPhone';
  }
  elseif ($mua->isMobile())
  {
    $mobile_info = $mua->isMobileTablet() ? 'tablet' : 'mobile';
  }
  else
  {
    $mobile_info = '';
  }

  $results[md5($mua->getUserAgentString())] = array
  (
    $i,
    $t,
    $mua->isIdentifiedFully() ? '' : ($mua->isIdentified() ? '?' : 'x'),
    $mua->getUserAgentString(),
    $mua->renderInfoBrowser(true),
    $mua->renderInfoOs(true),
    $mua->renderInfoDevice(),
    $mua->getMobileGrade() ? 'grade&nbsp;' . $mua->getMobileGrade() : '',
    $mua->isBanned() ? 'banned' : '',
    $mua->isBot() ? ($mua->isBotReader() ? 'reader' : 'bot') : '',
    $mobile_info
  );
}

$old_results = call_user_func(array(UserAgentInfoConfig::CACHE_CLASS_NAME, 'get'), 'test-archive-of-user-agents');
call_user_func(array(UserAgentInfoConfig::CACHE_CLASS_NAME, 'set'), 'test-archive-of-user-agents', $results);

$old_md5 = md5(serialize($old_results));
$now_md5 = md5(serialize($results));

if (!empty($old_results) && $old_md5 != $now_md5)
{
  foreach ($results as $md5 => $one_ua)
  {
    if (isset($old_results[$md5]))
    {
      foreach ($one_ua as $k => $data)
      {
        if ($k > 1 && $data != $old_results[$md5][$k])
        {
          $results[$md5][$k] = '<span>' . $old_results[$md5][$k] . '</span><br />' . $data;
        }
      }
    }
  }
}

?>

<style>
body, table {
  font-size: 12px;
}
table {
  border-collapse: collapse;
}
tr > * {
  padding: 2px 5px;
  border: 1px solid gray;
}
span {
  color: red;
  text-decoration: line-through;
}
</style>

<p>require_once time: <?=$start_time?></p>
<p>getMy() time: <?=$my_time?></p>

<table>
<thead><tr>
  <th>id</th>
  <th>time</th>
  <th>?</th>
  <th>user agent string</th>
  <th>browser</th>
  <th>OS</th>
  <th>device</th>
  <th>grade</th>
  <th>banned</th>
  <th>bot</th>
  <th>mobile type</th>
</tr></thead>
<tbody>
<? foreach ($results as $one_ua):?>
<tr>
  <? foreach ($one_ua as $row): ?>
  <td><?=$row?></td>
  <? endforeach; ?>
</tr>
<? endforeach ?>
</tbody>
</table>
