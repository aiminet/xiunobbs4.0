<?php

define('SKIP_ROUTE', 1);
include './index.php';

$user = user_read(1);
$salt = 'k9keks';
$password = md5(md5('1').$salt);
$update = array('password'=>$password, 'salt'=>$salt);
user_update(1, array('uid'=>1), $update);

echo $user['username'].' 密码已经重设为：1';

?>