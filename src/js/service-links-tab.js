/* 
 * The MIT License
 *
 * Copyright 2016 Jeppe Boysen Vennekilde.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

function fetchLinkedUser(){
    var data = generateAjaxPostData();
    $.ajax({
        type: "POST",
        url: webPath + "/REST/User/GetLinkedUser.php",
        data: data,
        success: function (e) {
            console.log(e);
            linkedUser = JSON.parse(e);
            parseLinkedUserServices();
            parseLinkedUserSecondaryLinks();
            if(linkedUser["link_id"] !== undefined){
                switchTab();
                retrieveAPIKeyName();
                onUserIdentifyEstablished = onUserIdentityEstablishedPostConfig;
            }
        },
        error: function (e) {
            console.log(e);
        }
    });
}

function fetchLinkedUserAndData(){
    var data = generateAjaxPostData();
    $.ajax({
        type: "POST",
        url: webPath + "/REST/User/GetLinkedUserWithData.php",
        data: data,
        success: function (e) {
            console.log(e);
            linkedUser = JSON.parse(e);
            parseLinkedUserServices();
            parseLinkedUserSecondaryLinks();
            parseLinkedUserData();
            if(linkedUser["link_id"] !== undefined){
                switchTab();
                retrieveAPIKeyName();
                onUserIdentifyEstablished = onUserIdentityEstablishedPostConfig;
            }
        },
        error: function (e) {
            console.log(e);
        }
    });
}

function onUserIdentityEstablishedPostConfig(serviceId){
    attemptLinkWithService(serviceId);
}

function linkWithLoggedInUser(serviceId, onResult){
    var data = generateAjaxPostData();
    data["service-id"] = 0;
    $.ajax({
        type: "POST",
        url: webPath + "/REST/User/LinkWithLoggedInUser.php",
        data: data,
        success: function (e) { 
            console.log(e);
            response = JSON.parse(e);
            if(response["result"] !== false){
                linkedUser["primary"][serviceId] = response["result"];
                onResult(true);
            } else {
                onResult(false);
            }
        },
        error: function (e) {
            console.log(e);
            onResult(false);
        }
    });
}

function attemptLinkWithService(serviceId){
    linkWithLoggedInUser(serviceId, function(linkSuccesful, userId, displayName){
        if(linkSuccesful){
            parseLinkedUserServices();
            
            var toast = document.querySelector('#snackbar-stepper-error');
            if (!toast){
                toast.MaterialSnackbar.showSnackbar({
                    message: services[serviceId] + " established link with user \""+displayName+"\" with id "+userId,
                    timeout: 4000,
                    actionText: 'Ok'
                });
            }
        } else {
            $('#service-id-'+serviceId+'-manage').toggle(); 
            adjustHeight();
        }
    })
}

function parseLinkedUserServices(){
    var hideDefaultServiceMsg = false;
    $(".service-entry").remove();
    $.each(services, function(serviceId, service){
        var userLink = linkedUser["primary"] !== undefined ? linkedUser["primary"][serviceId] : false;
        var isLinked = userLink !== undefined && userLink !== false;
        var serviceName = service["name"];
        var displayName = isLinked ? (userLink[1] ? userLink[1] : "No name cached") : '<font class="step-error">Not linked</font>';
        var userId = isLinked ? userLink[0] : '<font class="step-error">Not linked</font>';
        
        var optionsHtml = "";
        //Only show options if the user is actually linked with any service
        if(isLinked){
            //manageHtml = '<button class="mdl-button mdl-js-button mdl-button--raised" onclick="deleteServiceLink('+serviceId+')">Unlink</button>';
        } else if(linkedUser === false){
            //Not linked with any service, so manage option is not available
        } else if(service["can_determine_link"]){
            optionsHtml = '<button class="mdl-button mdl-js-button mdl-button--raised" onclick="attemptLinkWithService('+serviceId+')">Link</button>';
        } else {
            optionsHtml = service["link_not_available_desc"];
        }
        
        var entry = '\
        <tr id="service-id-'+serviceId+'" class="service-entry">\
            <td class="mdl-data-table__cell--non-numeric">'+serviceName+'</td>\
            <td class="mdl-data-table__cell--non-numeric">'+userId+'</td>\
            <td class="mdl-data-table__cell--non-numeric">'+displayName+'</td>\
            <td class="mdl-data-table__cell--non-numeric" style="text-align: right;">'+optionsHtml+'</td>\
        </tr>';
        $("#service-tbody").append(entry);
        
        if(!isLinked && service["can_determine_link"]){
            var linkSetupEntry = '\
            <tr id="service-id-'+serviceId+'-manage" class="service-entry" style="display: none">\
                <td class="mdl-data-table__cell--non-numeric" colspan="4" style="width: 100%">'+service["link_setup_html"]+'</td>\
            </tr>';
            $("#service-tbody").append(linkSetupEntry);
        }
        
        hideDefaultServiceMsg = true;
    })
    

    if(hideDefaultServiceMsg){
        $("#default-service-entry").hide();
        componentHandler.upgradeDom();
    } else {
        $("#default-service-entry").show();
    }
    adjustHeight();
    componentHandler.upgradeDom();
}

function parseLinkedUserSecondaryLinks(){
    var hideDefaultMusicBotMsg = false;
    $(".music-bot-entry").remove();
    $.each(linkedUser["secondary"], function(serviceId, value) {
        $.each(value, function(userId, displayName) {
            var displayName = displayName ? displayName : "No name cached";
            var serviceName = services[serviceId] ? services[serviceId]["name"] : "Unknown Service";
            var entry = '\
                <tr id="musicBot-'+serviceId+'-'+userId+'" class="music-bot-entry">\
                    <td class="mdl-data-table__cell--non-numeric">'+serviceName+'</td>\
                    <td>'+userId+'</td>\
                    <td>'+displayName+'</td>\
                    <td><button class="mdl-button mdl-js-button mdl-button--raised" onclick="deleteSecondaryLink('+userId+', '+serviceId+')">Delete</button></td>\n\
                </tr>';
            $("#music-bot-tbody").append(entry);
            hideDefaultMusicBotMsg = true;
        });
    });

    if(hideDefaultMusicBotMsg){
        $("#default-music-bot-entry").hide();
    } else {
        $("#default-music-bot-entry").show();
    }
    adjustHeight();
}

function parseLinkedUserData(){
    if(linkedUser["data"] !== undefined){
        if (linkedUser["data"]["a_username"] !== null) {
            $(".account-name").html(linkedUser["data"]["a_username"]);
            $(".gw2-world").html(getWorldNameFromId(linkedUser["data"]["a_world"]));
            $(".gw2-access").html(getAccessLabelFromAccessId(linkedUser["data"]["a_access"]));
            $(".account-name").html(linkedUser["data"]["a_username"]);
            $(".account-name-icon").html("done");
        } else {
            $(".account-name").html("<font class='step-error'>Not linked</div>");
            $(".account-name-icon").html("block");
        }

        $("#api-key-input").val(linkedUser["data"]["api_key"]);
        $("#api-key-input-field").addClass("is-dirty");
    }
    adjustHeight();
}

function deleteSecondaryLink(userId, serviceId){
    var toast = document.querySelector('#snackbar-stepper-error');
    if (!toast)
        return false;
    
    var data = generateAjaxPostData();
    data["service-id"] = serviceId;
    data["user-id"] = userId;
    $.ajax({
        type: "POST",
        url: webPath + "/REST/User/RemoveUserServiceLink.php",
        data: data,
        success: function (e) {
            console.log(e);
            response = JSON.parse(e);
            if(response["result"] == "success"){
                $('#musicBot-'+serviceId+'-'+userId).remove();
                if($(".music-bot-entry").length == 0){
                    $("#default-music-bot-entry").show();
                }
                toast.MaterialSnackbar.showSnackbar({
                    message: 'Music bot removed',
                    timeout: 4000,
                    actionText: 'Ok'
                });
            } else {
                toast.MaterialSnackbar.showSnackbar({
                    message: 'Could not remove music bot',
                    timeout: 4000,
                    actionText: 'Ok'
                });
            }
        },
        error: function (e) {
            console.log(e);
            toast.MaterialSnackbar.showSnackbar({
                message: 'Could not remove music bot',
                timeout: 4000,
                actionText: 'Ok'
            });
        }
    });
}