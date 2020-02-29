<?php
/**
 * User: silva
 * Date: 16/12/2017
 * Time: 21:50
 */

use CODOF\Util;

$tpl = Admin_tpl::get();
$db = \DB::getPDO();
$flash = array('flash' => false);

if (isset($_POST['FREICHAT_APP_KEY']) && CODOF\Access\CSRF::valid($_POST['CSRF_token'])) {
    unset($_POST['CSRF_token']);
    foreach ($_POST as $key => $value) {
        $query = "UPDATE " . PREFIX . "codo_config SET option_value=:value WHERE option_name=:key";
        $ps = $db->prepare($query);
        $ps->execute(array(':key' => $key, ':value' => htmlentities($value, ENT_QUOTES, 'UTF-8')));
    }
    $flash = array('flash' => true, 'message' => 'Settings saved successfully.');

}
CODOF\Util::get_config($db, true);

$tpl->assign('float_enabled_options', array(
    "yes" => "Yes",
    "no" => "No"
));

$tpl->assign('float_enabled_selected', Util::get_opt("FREICHAT_FLOAT_ENABLED"));

$tpl->assign('flash', $flash);
echo Admin_tpl::render('FreiChat/admin/FreiChat.admin.tpl');