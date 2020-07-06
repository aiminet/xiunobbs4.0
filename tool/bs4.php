<?php

// Ŀ¼
function glob_recursive($pattern, $flags = 0) {
        $files = glob($pattern, $flags);
        foreach(glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
                 $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
        }
        return $files;
}


// 
    function file_ext($filename, $max = 16) {
        $ext = strtolower(substr(strrchr($filename, '.'), 1));
        $ext = xn_urlencode($ext);
        strlen($ext) > $max AND $ext = substr($ext, 0, $max);
        if(!preg_match('#^\w+$#', $ext)) $ext = 'attach';
        return $ext;
}

function xn_urlencode($s) {
    $s = urlencode($s);
    $s = str_replace('_', '_5f', $s);
    $s = str_replace('-', '_2d', $s);
    $s = str_replace('.', '_2e', $s);
    $s = str_replace('+', '_2b', $s);
    $s = str_replace('=', '_3d', $s);
    $s = str_replace('%', '_', $s);
    return $s; 
}

$dir = empty($argv[1]) ? './*' : $argv[1] ;
$files = glob_recursive("$dir");
foreach ($files as $file) {
        $ext = file_ext($file);
        if(strpos($file, $_SERVER['PHP_SELF']) !== FALSE) continue;
        if($ext != 'htm' && $ext != 'php' && $ext != 'js') continue;
        $content = file_get_contents($file);
        $s = $content;

        
        $s = str_replace('m-l-0', 'ml-0', $s);
        $s = str_replace('m-r-0', 'mr-0', $s);
        $s = str_replace('m-t-0', 'mt-0', $s);
        $s = str_replace('m-b-0', 'mb-0', $s);
        $s = str_replace('m-x-0', 'mx-0', $s);
        $s = str_replace('m-y-0', 'my-0', $s);

        //$s = str_replace('m-xs', 'm-1', $s);
        $s = str_replace('m-l-xs', 'ml-1', $s);
        $s = str_replace('m-r-xs', 'mr-1', $s);
        $s = str_replace('m-t-xs', 'mt-1', $s);
        $s = str_replace('m-b-xs', 'mb-1', $s);
        $s = str_replace('m-x-xs', 'mx-1', $s);
        $s = str_replace('m-y-xs', 'my-1', $s);

        //$s = str_replace('m-sm', 'm-2', $s);
        $s = str_replace('m-l-sm', 'ml-2', $s);
        $s = str_replace('m-r-sm', 'mr-2', $s);
        $s = str_replace('m-t-sm', 'mt-2', $s);
        $s = str_replace('m-b-sm', 'mb-2', $s);
        $s = str_replace('m-x-sm', 'mx-2', $s);
        $s = str_replace('m-x-sm', 'my-2', $s);

       // $s = str_replace('m-1', 'm-3', $s);
        $s = str_replace('m-l-1', 'ml-3', $s);
        $s = str_replace('m-r-1', 'mr-3', $s);
        $s = str_replace('m-t-1', 'mt-3', $s);
        $s = str_replace('m-b-1', 'mb-3', $s);
        $s = str_replace('m-x-1', 'mx-3', $s);
        $s = str_replace('m-y-1', 'my-3', $s);


        //$s = str_replace('p-xs', 'p-1', $s);
        $s = str_replace('p-l-xs', 'pl-1', $s);
        $s = str_replace('p-r-xs', 'pr-1', $s);
        $s = str_replace('p-t-xs', 'pt-1', $s);
        $s = str_replace('p-b-xs', 'pb-1', $s);
        $s = str_replace('p-x-xs', 'px-1', $s);
        $s = str_replace('p-y-xs', 'py-1', $s);

        //$s = str_replace('p-sm', 'p-2', $s);
        $s = str_replace('p-l-sp', 'pl-2', $s);
        $s = str_replace('p-r-sp', 'pr-2', $s);
        $s = str_replace('p-t-sp', 'pt-2', $s);
        $s = str_replace('p-b-sp', 'pb-2', $s);
        $s = str_replace('p-x-sp', 'px-2', $s);
        $s = str_replace('p-x-sp', 'py-2', $s);

       // $s = str_replace('p-1', 'p-3', $s);
        $s = str_replace('p-l-1', 'pl-3', $s);
        $s = str_replace('p-r-1', 'pr-3', $s);
        $s = str_replace('p-t-1', 'pt-3', $s);
        $s = str_replace('p-b-1', 'pb-3', $s);
        $s = str_replace('p-x-1', 'px-3', $s);
        $s = str_replace('p-y-1', 'py-3', $s);

        $s = str_replace('p-l-0', 'pl-0', $s);
        $s = str_replace('p-r-0', 'pr-0', $s);
        $s = str_replace('p-t-0', 'pt-0', $s);
        $s = str_replace('p-b-0', 'pb-0', $s);
        $s = str_replace('p-x-0', 'px-0', $s);
        $s = str_replace('p-y-0', 'py-0', $s);

        $s = str_replace('col-xs-', 'col-', $s);

        $s = str_replace('center-block', 'mx-auto', $s);
        $s = str_replace('card-block', 'card-body', $s);
        $s = str_replace('text-bold', 'font-weight-bold', $s);
        $s = str_replace('pull-left', 'float-left', $s);
        $s = str_replace('pull-right', 'float-right', $s);

        $s = str_replace('hidden-md-up', 'hidden-md hidden-lg', $s);
        $s = str_replace('hidden-md-down', 'hidden-sm hidden-md', $s);

       // $s = str_replace('d-block d-md-none', 'hidden-md hidden-lg', $s);
       // $s = str_replace('d-none d-md-block', 'hidden-sm hidden-md', $s);
        

        //$s = str_replace('.tab("show")', '.addClass("active")', $s);
        //$s = str_replace(".tab('show')", ".addClass('active')", $s);

        /*
        $s = str_replace("logo-xs", "logo-1", $s);
        $s = str_replace("logo-sm", "logo-2", $s);
        $s = str_replace("logo-lg", "logo-3", $s);
        $s = str_replace("avatar-xs", "avatar-1", $s);
        $s = str_replace("avatar-sm", "avatar-2", $s);
        $s = str_replace("avatar-lg", "avatar-3", $s);
        */

        if($s != $content) {
              echo $file."\r\n";
              file_put_contents($file, $s);
        }
}