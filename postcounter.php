<?php
/**
 * \_/ \_/ \_/ \_/ \_/   \_/ \_/ \_/ \_/ \_/ \_/   \_/ \_/ \_/ \_/
 */
// Achievements mod by MelvinMeow
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'bittorrent.php';
require_once CLASS_DIR . 'page_verify.php';
require_once INCL_DIR . 'user_functions.php';
dbconn();
loggedinorreturn();
$newpage = new page_verify();
$newpage->check('takecounts');
$res = sql_query('SELECT COUNT(*) FROM posts WHERE user_id=' . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
$arr3 = mysqli_fetch_row($res);
$forumposts = $arr3['0'];
sql_query('UPDATE usersachiev SET forumposts=' . sqlesc($forumposts) . ' WHERE id=' . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
header("Location: {$INSTALLER09['baseurl']}/index.php");
