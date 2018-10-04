<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: EntExt
 * The Initial Developer of the Original Code is EntExt.
 * All Rights Reserved.
 * If you have any questions or comments, please email: devel@entext.com
 ************************************************************************************/

include_once 'include/Webservices/Query.php';

class EEMassMap_GetSelectedRecordsCoordinates_Action extends Vtiger_Action_Controller {

    /**
     * Check user permission for module
     *
     * @param Vtiger_Request $request
     */
    public function checkPermission(Vtiger_Request $request) {

    }

    /**
     * Main action logic
     *
     * @param Vtiger_Request $request
     */
    public function process(Vtiger_Request $request) {
        $module = $request->get('source_module');
        $selectedIds = $request->get('selectedIds');
        $locationFields = $this->getLocationFields($module);
        $markerDetails = array();
        $records = $this->getRecords($module, $selectedIds);

        $i = 0;
        foreach($records as $wsEntity) {
            $address = array();
            foreach($locationFields as $key => $value) {
                $address[$key] = Vtiger_Util_Helper::getDecodedValue($wsEntity[$value]);
            }

            if(empty($address['street']) && empty($address['city']) && empty($address['country'])) continue;

			$opts = array('http'=>array('header'=>"User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.146 Safari/537.36\r\n"));
			$context = stream_context_create($opts);

            $url = "https://nominatim.openstreetmap.org/search?q=".urlencode(join(",", $address))."&format=json";
            $res = json_decode(file_get_contents($url, false, $context));
            $markerDetails[$i]['lat'] = $res[0]->lat;
            $markerDetails[$i]['lng'] = $res[0]->lon;
            if(strpos($wsEntity['id'], 'x') === false) {
                $markerDetails[$i]['recordId'] = $wsEntity['id'];
            } else {
                $idSlices = vtws_getIdComponents($wsEntity['id']);
                $markerDetails[$i]['recordId'] = $idSlices[1];
            }

            $entityNames = array_values(getEntityName($module, $markerDetails[$i]['recordId']));
            $markerDetails[$i]['entityName'] = $entityNames[0];

            $i++;
        }

        $response = new Vtiger_Response();
        $response->setResult($markerDetails);
        $response->emit();
    }

    /**
     * Get location values for: street, city, country
     *
     * @param $module
     * @return array
     */
    private function getLocationFields($module) {
        switch ($module) {
            case 'Contacts':
                return array(
                    'street' => 'mailingstreet',
                    'city' => 'mailingcity',
                    'country' => 'mailingcountry'
                );
                break;
            case 'Leads' :
                return array(
                    'street' => 'lane',
                    'city' => 'city',
                    'country' => 'country'
                );
                break;
            case 'Accounts' :
                return array(
                    'street' => 'bill_street',
                    'city' => 'bill_city',
                    'country' => 'bill_country'
                );
                break;
            default :
                return array();
                break;
        }
    }

    /**
     * Get records based on user access
     *
     * @param $moduleName
     * @param $selectedIds
     * @return array
     */
    private function getRecords($moduleName, $selectedIds) {
        global $current_user;
        $query = $this->queryBuilder($moduleName, $selectedIds);
        try {
            return vtws_query($query, $current_user);
        } catch (WebServiceException $ex) {
            return array();
        }
    }

    /**
     * Build select query
     *
     * @param $moduleName
     * @param $selectedIds
     * @return string
     */
    private function queryBuilder($moduleName, $selectedIds) {
        $query = "SELECT * FROM $moduleName";
        if($selectedIds != 'all') {
            $wsIds = array();
            foreach($selectedIds as $id) {
                $wsIds[] = vtws_getWebserviceEntityId($moduleName, $id);
            }
            $query .= " WHERE id IN (" . implode(',', $wsIds) . ")";
        }
        $query .= ";";
        return $query;
    }
}
