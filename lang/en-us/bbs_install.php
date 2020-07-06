<?php

return array(
	'installed_tips' => 'You have been installed, and if you need to re install, please delete the conf/conf.php!',
	'please_set_conf_file_writable' => 'Please set the conf/conf.php file to write!',
	'evn_not_support_php_mysql' => 'The current PHP environment does not support mysql and pdo_mysql driver, can not continue to install.',
	'dbhost_is_empty' => 'Database host cannot be empty',
	'dbname_is_empty' => 'Database name cannot be empty',
	'dbuser_is_empty' => 'User name cannot be empty',
	'adminuser_is_empty' => 'Administrator user name can not be empty',
	'adminpass_is_empty' => 'Administrator password can not be empty',
	'conguralation_installed' => 'Congratulations, installation success, please remove install directory for security.',
	
	'step_1_title' => '1. Environmental Check',
	'runtime_env_check' => 'Runtime environment detection',
	'required' => 'Required',
	'current' => 'Current',
	'check_result' => 'Check Result',
	'passed' => 'Passed',
	'not_passed' => 'Not Passed',
	'not_the_best' => 'Not the ideal environment',
	'dir_writable_check' => 'Directory / file permissions',
	'writable' => 'Writable',
	'unwritable' => 'Unwritable',
	'check_again' => 'Check Again',
	'os' => 'OS',
	'unix_like' => 'UNIX Like',
	'php_version' => 'PHP Version',
	
	'step_2_title' => '2. Database settings',
	'db_type' => 'Database type',
	'db_engine' => 'Database Engine',
	'db_host' => 'Database Host',
	'db_name' => 'Database Name',
	'db_user' => 'Database User',
	'db_pass' => 'Database Password',
	'step_3_title' => '3. Administrator information',
	'admin_email' => 'Administrator Email',
	'admin_username' => 'Administrator Username',
	'admin_pw' => 'Administrator Password',
	'installing_about_moment' => 'Installing, it takes about a minute or so',
	'license_title' => 'Xiuno BBS 4.0 License Agreement',
	'license_content' => 'Thank you to choose BBS Xiuno 4, it is a domestic, compact, stable, support in the large amount of data is still maintained a high load capacity of light forum. It is only more than 20 table, 1M compression source code about running very fast, processing a single request in 0.01 second level, in APC, Xcache, Yac environment can ran to the 0.00x seconds, to third-party library, very few dependencies, the front only dependent jquery.js, as thought it is just like a car handmade Ferrari, the power is strong, without the slightest throatiness, convenient deployment and maintenance is the cornerstone of a very good secondary development.
Xiuno BBS (bulletin board system) 4.0 using bootstrap 4 + jQuery 3 as a front-end library, full support for mobile browser; the back-end XiunoPHP 4.0 support NoSQL way to operate a variety of databases, this version is a great leap forward.
Xiuno pronunciation "Shura", English Shura, which is one of the six Buddhist "Shura", in between humanity and heaven.
BBS Xiuno 4 using the MIT agreement, you can freely modify, derived version, commercial without fear of any legal risks (the original copyright information should be retained after the modification)。
	',
	'license_date' => 'Release date: Jan 22, 2018',
	'agree_license_to_continue' => 'Agree to continue to install the agreement',
	'install_title' => 'Xiuno BBS 4.0 Installation wizard',
	'install_guide' => 'Installation Wizard',

	
	'function_check' => 'Function dependency check',
	'supported' => 'Supported',
	'not_supported' => 'Not Supported',
	'function_glob_not_exists' => 'Plugin install dependent on it, please setting php.ini, set disabled_functions = ; Lifting restrictions on this function',
	'function_gzcompress_not_exists' => 'Plugin install dependent on it, on Linux server, add compile argument: --with-zlib, on Windows Server, please setting php.ini open extension=php_zlib.dll',
	'function_mb_substr_not_exists' => 'System dependent on it, on Linux server, add compile argument: --with-mbstring, on Windows Server, please setting php.ini open extension=php_mbstring.dll',
	
	// hook lang_en_us_bbs_install.php
);

?>