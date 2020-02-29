<?php

/* 
 * @CODOLICENSE
 */

defined('IN_CODOF') or die();

$CF_installed=true;

function get_codo_db_conf() {


    $config = array (
  'driver' => 'mysql',
  'host' => '10.118.193.3',
  'database' => 'porsche_forum',
  'username' => 'forum-app',
  'password' => 'e9DBeGKd9*4anen&v9hjRg=Pf9Ex8fv,Q8>2Qe{BD(Ybs}vPvtRs2iq]X4zk(NJc',
  'prefix' => '',
  'charset' => 'utf8',
  'collation' => 'utf8_unicode_ci',
);

    return $config;
}

$DB = get_codo_db_conf();

$CONF = array (
    
  'driver' => 'Custom',
  'UID'    => '5e5305ae19553',
  'SECRET' => '5e5305ae19555',
  'PREFIX' => ''
);
