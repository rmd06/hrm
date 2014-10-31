<?php
// This file is part of the Huygens Remote Manager
// Copyright and license notice: see license.txt

////////////////////////////////////////////////////////////////////////////////
//
//                           DO NOT EDIT THIS FILE!
//
////////////////////////////////////////////////////////////////////////////////

if (!isset($isServer)) {
    $isServer = false;
}

if ($isServer == true) {
    require_once(dirname(__FILE__) . "/../config/hrm_server_config.inc");
} else {
    require_once(dirname( __FILE__ ) . "/../config/hrm_client_config.inc");
}

// This is a hidden parameter for advanced users
if (!isset($userManagerScript)) {

    // This is a temporary hack to restore the pre-HRM 3.2.0 functionality
    // that will be removed in 3.3.0. In case the administrator defined a
    // custom user management script in the configuration files, we do not
    // replace it -- no matter what the value of $change_ownership is!
    if (isset($change_ownership) && $change_ownership == true) {
        $userManagerScript = dirname(__FILE__) . "/../bin/hrm_user_manager_old";
    } else {
        // This default user manager script can be replaced in the configuration.
        // A demo server, for example, may use links to a demo directory instead of
        // creating an empty directory for each new user.
        $userManagerScript = dirname(__FILE__) . "/../bin/hrm_user_manager";
    }
}

?>
