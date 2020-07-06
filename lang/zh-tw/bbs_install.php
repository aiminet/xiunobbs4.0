<?php

return array(
	'installed_tips' => '程序已經安裝過了，如需重新安裝，請刪除 conf/conf.php ！',
	'please_set_conf_file_writable' => '請設置 conf/conf.php 文件為可寫！',
	'evn_not_support_php_mysql' => '當前 PHP 環境不支持 mysql 和 pdo_mysql，無法繼續安裝。',
	'dbhost_is_empty' => '數據庫主機不能為空',
	'dbname_is_empty' => '數據庫名不能為空',
	'dbuser_is_empty' => '用戶名不能為空',
	'adminuser_is_empty' => '管理員用戶名不能為空',
	'adminpass_is_empty' => '管理員密碼不能為空',
	'conguralation_installed' => '恭喜，安裝成功！為了安全請刪除 install 目錄。',
	
	'step_1_title' => '壹、安裝環境檢測',
	'runtime_env_check' => '網站運行環境檢測',
	'required' => '需要',
	'current' => '當前',
	'check_result' => '檢測結果',
	'passed' => '通過',
	'not_passed' => '通過',
	'not_the_best' => '不是最理想的環境',
	'dir_writable_check' => '目錄 / 文件 權限檢測',
	'writable' => '可寫',
	'unwritable' => '不可寫',
	'check_again' => '重新檢測',
	'os' => '操作系統',
	'unix_like' => '類 UNIX',
	'php_version' => 'PHP 版本',
	
	'step_2_title' => '二、數據庫設置',
	'db_type' => '數據庫類型',
	'db_engine' => '數據庫引擎',
	'db_host' => '數據庫服務器',
	'db_name' => '數據庫名',
	'db_user' => '數據庫用戶名',
	'db_pass' => '數據庫密碼',
	'step_3_title' => '三、管理員信息',
	'admin_email' => '管理員郵箱',
	'admin_username' => '管理員用戶名',
	'admin_pw' => '管理員密碼',
	'installing_about_moment' => '正在安裝，大概需要壹分鐘左右',
	'license_title' => 'Xiuno BBS 4.0 中文版授權協議',
	'license_content' => '感謝您選擇 Xiuno BBS 4.0，它是壹款國產、小巧、穩定、支持在大數據量下仍然保持高負載能力的輕型論壇。它只有 20 多個表，源代碼壓縮後 1M 左右，運行速度非常快，處理單次請求在 0.01 秒級別，在有 APC、XCache、Yac 的環境下可以跑到 0.00x 秒，對第三方類庫依賴極少，僅僅前端依賴 jquery.js，作者認為它就像壹輛純手工打造的法拉利，動力強勁，沒有壹絲贅肉，方便部署和維護，是壹個非常好的二次開發的基石。
	
Xiuno BBS 4.0 采用 Bootstrap 4 + JQuery 3 作為前端類庫，全面支持移動端瀏覽器；後端 XiunoPHP 4.0 支持了 NoSQL 的方式操作各種數據庫，這個版本是壹個巨大的飛躍。
	
Xiuno 發音“修羅”，英文為 Shura，在佛教裏面為六道之壹"修羅道"，處於人道和天道之間。

Xiuno BBS 4.0 采用 MIT 協議發布，您可以自由修改、派生版本、商用而不用擔心任何法律風險（修改後應保留原來的版權信息）。',
	'license_date' => '發布時間：2016年7月26日',
	'agree_license_to_continue' => '同意協議繼續安裝',
	'install_title' => 'Xiuno BBS 4.0 安裝向導',
	'install_guide' => '安裝向導',

	'function_check' => '函數依賴檢查',
	'supported' => '支持',
	'not_supported' => '不支持',
	'function_glob_not_exists' => '後臺插件功能依賴該函數，請配置 php.ini，設置 disabled_functions = ; 去除對該函數的限制',
	'function_gzcompress_not_exists' => '後臺插件功能依賴該函數，Linux 主機請添加編譯參數 --with-zlib，Windows 主機請配置 php.ini 註釋掉  extension=php_zlib.dll',
	'function_mb_substr_not_exists' => '系統依賴該函數，Linux 主機請添加編譯參數 --with-mbstring，Windows 主機請配置 php.ini 註釋掉 extension=php_mbstring.dll',
	
	// hook lang_zh_tw_bbs_admin.php
);

?>