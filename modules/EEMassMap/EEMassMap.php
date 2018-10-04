<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: EntExt
 * The Initial Developer of the Original Code is EntExt.
 * All Rights Reserved.
 * If you have any questions or comments, please email: devel@entext.com
 ************************************************************************************/
include_once 'include/Zend/Json.php';
require_once('data/CRMEntity.php');
require_once('data/Tracker.php');

class EEMassMap {

    /**
     * Invoked when special actions are performed on the module.
     *
     * @param String $moduleName
     * @param String $event_type
     */
    function vtlib_handler($moduleName, $event_type) {

        if($event_type == 'module.postinstall') {

            $this->linksManager(true);

        } else if($event_type == 'module.disabled') {

            $this->linksManager(false);

        } else if($event_type == 'module.enabled') {

            $this->linksManager(true);

        } else if($event_type == 'module.preuninstall') {
            // TODO Handle actions when this module is about to be deleted.
        } else if($event_type == 'module.preupdate') {
            // TODO Handle actions before this module is updated.
        } else if($event_type == 'module.postupdate') {
            // TODO Handle actions after this module is updated.
        }

    }

    /**
     * True - add links, false - delete
     *
     * @param $flag
     */
    function linksManager($flag) {
        $moduleInstance = Vtiger_Module::getInstance('EEMassMap');

        $vtigerVersion = vtws_getVtigerVersion();
        if($vtigerVersion[0] == '7') {
            $url = 'layouts/v7/modules/EEMassMap/resources/EEMassMap.js';
        } else {
            $url = 'layouts/vlayout/modules/Settings/EEMassMap/resources/EEMassMap.js';
        }

        if($flag) {
            $moduleInstance->addLink('HEADERSCRIPT', 'EEMassMapHeaderScript', $url);
        } else {
            $moduleInstance->deleteLink('HEADERSCRIPT', 'EEMassMapHeaderScript');
        }
    }
}

?>
