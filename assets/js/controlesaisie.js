
$(function (){
    //Ajout d'un patient avec les informations civiles et indentifiant connu [addPatient]
    $("#AJOUTER").click(function(){
        valid = true ;

        if (!$("#nomPatient").val().match(/^[a-zA-Zàáôûéèîï]+$/)){
            $("#nomPatient").addClass("text-danger");
            $('#Notify').notify("Format du Nom Incorect", {
                className: "error",
                position: "top center",
                autoHide: true,
                autoHideDelay: 10000
            });
            valid = false ;
        }
        else {
            $("#nomPatient").removeClass("text-danger").addClass("text-dark");
        }
        if (!$("#prenomsPatient").val().match(/^[a-zA-Zàáôûéèîï ]+$/)){
            $("#prenomsPatient").addClass("text-danger");
            $('#Notify').notify("Format du Prénom(s) Incorect", {
                className: "error",
                position: "top center",
                autoHide: true,
                autoHideDelay: 10000
            });
            valid = false ;
        }
        else {
            $("#prenomsPatient").removeClass("text-danger").addClass("text-dark");
        }
        
        if ($("#numeroDossierPatient").val() > 250000 ){
            valid = false ;
        }
            return valid ;                
    }) ;
}) ; 


$(function (){
    //Ajout d'un patient avec les informations civiles et indentifiant non connu [addPatient]
    $("#AJOUTERNOI").click(function(){
        valid = true ;

        text = $("#servDemande").val() ;
        if ( text == "0" ) {
            $("#servDemande").addClass("text-danger");
            $('#Notify').notify("Veuillez choisir un service.", {
                className: "error",
                position: "top center",
                autoHide: true,
                autoHideDelay: 10000
            });

            valid = false ;
        }
        else {
            $("#servDemande").removeClass("text-danger").addClass("text-dark");
        }

        if (!$("#nomPatientNoi").val().match(/^[a-zA-Zàáôûéèîï]+$/)){
            $("#nomPatientNoi").addClass("text-danger");
            $('#Notify').notify("Format du Nom Incorect", {
                className: "error",
                position: "top center",
                autoHide: true,
                autoHideDelay: 10000
            });
            valid = false ;
        }
        else {
            $("#nomPatientNoi").removeClass("text-danger").addClass("text-dark");
        }
        
        if (!$("#prenomsPatientNoi").val().match(/^[a-zA-Zàáôûéèîï ]+$/)){
            $("#prenomsPatientNoi").addClass("text-danger");
            $('#Notify').notify("Format du Prénom(s) Incorect", {
                className: "error",
                position: "top center",
                autoHide: true,
                autoHideDelay: 10000
            });
            valid = false ;
        }
        else {
            $("#prenomsPatientNoi").removeClass("text-danger").addClass("text-dark");
        }

            return valid ;                
    }) ;
}) ; 


$(function (){
    //Recherche d'un patient avec le numero identifiant ou le numero de téléphone associé
    $("#RECHERCHER").click(function(){
        valid = true ;
        if ($("#snumeroDossierPatient").val() == "" &&  $("#stelephonePatient").val() == "221" ){
            $('#Notify').notify("Vous devez renseigner au moins un des filtres", {
                className: "error",
                position: "top center",
                autoHide: true,
                autoHideDelay: 10000
            });
            valid = false ;
        }

        if ($("#snumeroDossierPatient").val() > 250000 ){
            valid = false ;
        }
            return valid ;                
    }) ;
}) ; 

$(function (){
    //Selection d'un service et d'une date pour lister les rv enregistré [showrv]
    $("#LISTER").click(function(){
        valid = true ;
        text = $("#service_view").val() ;
        if ( text == "0" ) {
            $("#service_view").addClass("text-danger");
            $('#Notify').notify("Veuiller choisir un service", {
                className: "error",
                position: "top center",
                autoHide: true,
                autoHideDelay: 10000
            });
            valid = false ;
        }
        else {
            $("#service_view").removeClass("text-danger").addClass("text-dark");

        }
        return valid ;
    }) ;
}) ; 


$(function (){
    $("#AJOUTERRV").click(function(){
        //Ajout Rv en selectionnant un service et une date [managerendezvs/addrv]
        valid = true ;
        text = $("#servDemande").val() ;
        if ( text == "0" ) {
            $("#servDemande").addClass("text-danger");
            valid = false ;
            $('#Notify').notify("Veuillez choisir un service", {
                className: "error",
                position: "top center",
                autoHide: true,
                autoHideDelay: 10000
            });
        }
        else {
            $("#servDemande").removeClass("text-danger").addClass("text-dark");
        }
        return valid ;
    }) ;
}) ;
    
$(function (){
    $("#UPDATEAGENT").click(function(){
                
        valid = true ;
        if ( $("#password1").val() != $("#password2").val() ) {
            $("#password1").addClass("text-danger");
            $("#password2").addClass("text-danger");
            $('#Notify').notify("Les mots de passent doivent etre identiques", {
                className: "error",
                position: "top center",
                autoHide: true,
                autoHideDelay: 10000
            });

            valid = false ;
        }
        else {
            $("#password1").removeClass("text-danger").addClass("text-dark");
            $("#password2").removeClass("text-danger").addClass("text-dark");
        }

        if (!$("#prenom_agent").val().match(/^[a-zA-Zàáôûéèîï ]+$/)){
            $("#prenom_agent").addClass("text-danger");
            $('#Notify').notify("Format du Prénom(s) Incorect", {
                className: "error",
                position: "top center",
                autoHide: true,
                autoHideDelay: 10000
            });
            valid = false ;
        }
        else {
            $("#prenom_agent").removeClass("text-danger").addClass("text-dark");
        }

        if (!$("#nom_agent").val().match(/^[a-zA-Zàáôûéèîï]+$/)){
            $("#nom_agent").addClass("text-danger");
            $('#Notify').notify("Format du Nom Incorect", {
                className: "error",
                position: "top center",
                autoHide: true,
                autoHideDelay: 10000
            });
            valid = false ;
        }
        else {
            $("#nom_agent").removeClass("text-danger").addClass("text-dark");
        }
        return valid ;
    }) ;
}) ; 
