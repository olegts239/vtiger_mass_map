/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: EntExt
 * The Initial Developer of the Original Code is EntExt.
 * All Rights Reserved.
 * If you have any questions or comments, please email: devel@entext.com
 ************************************************************************************/

jQuery.Class("EEMassMap_Js",{},{

    autocomplete : null,

    registerLeaflet : function() {
        var link = document.createElement('link');
        link.type = 'text/css';
        link.rel = 'stylesheet';
        link.href = 'layouts/vlayout/modules/Settings/EEMassMap/resources/leaflet/leaflet.css';
        document.head.appendChild(link);

        var script = document.createElement('script');
        script.src = "layouts/vlayout/modules/Settings/EEMassMap/resources/leaflet/leaflet.js";
        document.head.appendChild(script);
    },

    registerMassMapAction : function() {
        if(!$(".listViewMassActions #showMap").length) {
            $(".listViewMassActions ul").append('<li id="showMap"><a href="javascript:void(0);">Map</a></li>');
        }
    },

    registerMapModal : function() {
        $("body").append(
            '<div class="modal fade" id="MapModal" style="display: none; width: 90%; margin: -45vh 0 0 -45% !important; max-height: 95vh !important;">' +
                '<div class="modal-dialog">' +
                    '<div class="modal-content">' +
                        '<div class="modal-header"> ' +
                            '<button type="button" class="close" data-dismiss="modal">&times;</button> ' +
                            '<h4 class="modal-title">Map</h4> ' +
                        '</div>' +
                        '<div class="modal-body">' +
                            '<div id="map" style="height:75vh;"></div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>'
        )
    },

    registerShowMapAction : function() {
        var MapModal = jQuery('#MapModal');

        var map = null;

        jQuery('#showMap').on('click',function(e) {
            var listInstance = Vtiger_List_Js.getInstance();
            var validationResult = listInstance.checkListRecordSelected();
            if(validationResult != true) {
                var aDeferred = jQuery.Deferred();
                var params = {};
                var moduleName = app.getModuleName();
                MapModal.modal('show');

                var progressIndicatorElement = jQuery.progressIndicator({
                    'position' : 'html',
                    'blockInfo' : {
                        'enabled' : true
                    }
                });

                params['module'] = 'EEMassMap';
                params['source_module'] = moduleName;
                params['action'] = 'GetSelectedRecordsCoordinates';
                params['selectedIds'] = listInstance.readSelectedIds(true);
                AppConnector.request(params).then(
                    function(data) {
                        if(data.success && data.result) {

                            progressIndicatorElement.progressIndicator({'mode' : 'hide'});

                            map = L.map('map', {scrollWheelZoom:true}).setView([0, 0], 15);
                            L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
                            }).addTo(map);

                            var markersArray = [];
                            var markerDetails = data.result;
                            for(var i = 0; i < markerDetails.length; i++) {

                                if (markerDetails[i]['lat'] == null || markerDetails[i]['lng'] == null) {
                                    continue;
                                }

                                var marker = L.marker([markerDetails[i]['lat'], markerDetails[i]['lng']]).addTo(map);

                                marker.on('mouseover', (function (marker, i) {
                                    return function () {
                                        var tooltipURL = '?module=' + moduleName + '&xview=Detail&record=' + markerDetails[i]['recordId'] + '&view=TooltipAjax';
                                        AppConnector.request(tooltipURL).then(function(data) {
                                            var htmlData = $(data);
                                            htmlData.find('.fieldLabel').css({'white-space':'normal'});
                                            htmlData.find('td').css({'padding' : '3px 5px', 'overflow-wrap':'break-word'});
                                            marker.bindPopup('<div id="infoWindow" style="width: 280px">' + htmlData.prop('outerHTML') + '</div>');
                                            marker.openPopup();
                                        });
                                    }
                                })(marker, i));

                                marker.on('mouseout', (function (marker, i) {
                                    return function () {
                                        marker.closePopup();                                    }
                                })(marker, i));

                                marker.on('click', (function (marker, i) {
                                    return function () {
                                        window.location.href = 'index.php?module=' + moduleName + '&view=Detail&record=' + markerDetails[i]['recordId'];
                                    }
                                })(marker, i));

                                markersArray.push(marker);
                            }

                            if(markersArray.length > 0) {
                                var group = new L.featureGroup(markersArray);
                                map.fitBounds(group.getBounds().pad(0.5));
                            }
                        }
                        progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                        aDeferred.resolve(data);
                    },

                    function(error) {
                        progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                        aDeferred.reject(error);
                    }
                );


            } else {
                listInstance.noRecordSelectedAlert();
                MapModal.modal('hide');
            }
        });

        MapModal.on('hidden', function () {
            if(map) {
                map.remove();
            }
        });
    },

    validViewAndModule : function() {
        var viewName = app.getViewName();
        var currentModule = app.getModuleName();
        return !!(viewName == 'List' && ['Leads', 'Accounts', 'Contacts'].indexOf(currentModule) != -1);

    },

    registerEvents : function() {
        this.registerLeaflet();
        this.registerMassMapAction();
        this.registerMapModal();
        this.registerShowMapAction();
    }

});

jQuery(document).ready(function() {
    var eeAddressAutocompleteInstance = new EEMassMap_Js();
    if(!eeAddressAutocompleteInstance.validViewAndModule()) return;
    eeAddressAutocompleteInstance.registerEvents();
    app.listenPostAjaxReady(function() {
        var eeAddressAutocompleteInstance = new EEMassMap_Js();
        eeAddressAutocompleteInstance.registerEvents();
    });

});


