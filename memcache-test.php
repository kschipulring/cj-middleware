<?php
error_reporting(0);
if (class_exists('Memcache')) {
    $server = $_SERVER['SERVER_ADDR'];
    if (!empty($_REQUEST['server'])) {
        $server = $_REQUEST['server'];
    }
    $memcache = new Memcache;
    $isMemcacheAvailable = @$memcache->connect($server);

    if ($isMemcacheAvailable) {
        $aData = $memcache->get('data');
        echo '<pre>';
        if ($aData) {
            echo '<h2>Data from Cache:</h2>';
            print_r($aData);
        } else {
            $aData = array(
                'me' => 'you',
                'us' => 'them',
            );
            echo '<h2>Fresh Data:</h2>';
            print_r($aData);
            $memcache->set('data', $aData, 0, 90);
        }        
        if ($aData) {
            echo '<h3>Memcache seems to be working fine!</h3>';
        } else {
            echo '<h3>Memcache DOES NOT seems to be working!</h3>';
        }
        echo '</pre>';
    }
}
if (!$isMemcacheAvailable) {
    echo 'Memcache not available';
}



?>