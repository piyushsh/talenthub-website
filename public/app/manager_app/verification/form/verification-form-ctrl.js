/**
 * Created by Piyush Sharma on 4/19/2017.
 */
'use strict';

managerApp.controller('verificationFormController',['managerProfileViewService','verificationService',
    function(managerProfileViewService, verificationService, COUNTRIES){
    var vm = this;
    vm.model = verificationService.model;

    vm.submitForm = submitForm;
    vm.listOfCountries = COUNTRIES;
    activate();

    function activate(){
        vm.managerProfile = managerProfileViewService.getManagerProfile()
            .then(function(managerProfile){
                vm.model.sportType = managerProfile.sport_type;
            });
    }

    function submitForm(){
        console.log("VM:", vm);
        return;
    }
}]);
