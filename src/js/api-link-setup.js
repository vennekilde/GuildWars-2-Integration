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


//Near checkboxes
$('.singleCheckbox').click(function () {
    $(this).siblings('input:checkbox').prop('checked', false);
});
//Every checkboxes in the page
$('.selectme input:checkbox').click(function () {
    $('.selectme input:checkbox').not(this).prop('checked', false);
});

var linkWithServices = [];
var apiKeyName = "<div style='color: #DC1515'>If you see this, something went wrong</div>";

function init() {
    setupSteps();
    fetchLinkedServices(function () {
        fetchLinkedUserAndData();
    });

    $(window).focus(function () {
        var element = document.querySelector('.mdl-stepper#verify-stepper');
        if (!element)
            return;
        var stepper = element.MaterialStepper;
        var stepId = stepper.getActiveId();
        if (stepId === 2) {
            fetchLinkSetupRequired();
        }
    });
}
window.addEventListener('load', init);

function nextLoop(stepper, skipCount) {
    for (var i = 0; i < skipCount; i++) {
        stepper.next();
    }
}

function onUserIdentifyEstablished(serviceId){
    fetchLinkSetupRequired();
}

function retrieveAPIKeyName(materialStepper) {
    var data = generateAjaxPostData();
    $.ajax({
        type: "POST",
        url: webPath + "/REST/User/GetAPIKeyNames.php",
        data: data,
        success: function (e) {
            console.log(e);
            var apiKeyNames = JSON.parse(e);
            if (apiKeyNames.length > 0) {
                $("#api-key-name").html(apiKeyNames[0]);
            }
            adjustHeight();
        },
        error: function (e) {
            console.log(e);
            materialStepper.error(e.responseText);
            var toast = document.querySelector('#snackbar-stepper-error');
            if (!toast)
                return false;
            toast.MaterialSnackbar.showSnackbar({
                message: 'Could not retrieve required API Keyname',
                timeout: 4000,
                actionText: 'Ok'
            });
            $("#api-key-name").html(apiKeyName);
            adjustHeight();
        }
    });
}

function setAPIKey(materialStepper, step) {
    var apiKey = $("#api-key-input").val();
    //Basic checking
    if (apiKey === "") {
        //weird fix for the message not getting removed when creating a new error
        $(".mdl-step.is-active").find(".mdl-step__title-message").remove();
        materialStepper.error("API Key is empty");
        return;
    }

    var data = generateAjaxPostData();
    data["api-key"] = apiKey;
    $.ajax({
        type: "POST",
        url: webPath + "/REST/User/SetAPIKey.php",
        data: data,
        success: function (e) {
            console.log(e);
            accountData = JSON.parse(e);
            fetchLinkedUserAndData();
            var toast = document.querySelector('#snackbar-stepper-complete');
            if (!toast)
                return false;
            toast.MaterialSnackbar.showSnackbar({
                message: 'Successfully added API Key!',
                timeout: 4000,
                actionText: 'Ok'
            });
            materialStepper.next();
            onComplete();
            adjustHeight();
        },
        error: function (e) {
            $(".characters-permission-icon").removeClass("step-error");
            $(".api-keyname-icon").removeClass("step-error");
            console.log(e);
            //weird fix for the message not getting removed when creating a new error
            $(".mdl-step.is-active").find(".mdl-step__title-message").remove();
            //API Keyname invalid
            if (e.status === 417) {
                $(".api-keyname-icon").addClass("step-error");
                materialStepper.error(e.responseText);
            } else if (e.status === 401) {
                var missingPermissions = JSON.parse(e.responseText);

                console.log(missingPermissions);
                var arrayLength = missingPermissions.length;
                for (var i = 0; i < arrayLength; i++) {
                    var missingPermission = missingPermissions[i];
                    console.log(missingPermission);
                    $("." + missingPermission + "-permission-icon").addClass("step-error");
                }
                materialStepper.error("API Key is missing required permissions " + e.responseText);
            } else {
                materialStepper.error(e.responseText);
            }
            adjustHeight();
        }
    });
}

function fetchLinkSetupRequired() {
    var data = generateAjaxPostData();
    data["services"] = linkWithServices.join();
    data["on-success-js-func"] = "onUserIdentifyEstablished";
    $.ajax({
        type: "POST",
        url: webPath + "/REST/Service/GetLinkedServicesSetup.php",
        data: data,
        success: function (e) {
            console.log(e);
            var json = JSON.parse(e);
            if (json.length > 0) {
                var content = "";
                for (var x in json) {
                    content += "<div class='link-service-setup'>";
                    content += decodeEntities(json[x]);
                    content += "</div>";
                }
                $("#link-setup-content").html(content);
                //Refresh MDL Components
                componentHandler.upgradeDom();
            } else {
                var element = document.querySelector('.mdl-stepper#verify-stepper');
                if (!element)
                    return false;
                var stepper = element.MaterialStepper;

                retrieveAPIKeyName();
                stepper.next();
                //Test if linked user can now be identified
                fetchLinkedUserAndData();
            }
            adjustHeight();
        },
        error: function (e) {
            console.log(e);
        }
    });
}

function fetchLinkedServices(onSuccess) {
    var data = generateAjaxPostData();
    data["on-success-js-func"] = "onUserIdentifyEstablished";
    $.ajax({
        type: "POST",
        url: webPath + "/REST/Service/GetLinkedServices.php",
        data: data,
        success: function (e) {
            services = JSON.parse(e);
            onSuccess();
        },
        error: function (e) {
            console.log(e);
        }
    });
}

// Error state demonstration 
var setupSteps = function () {
    var element = document.querySelector('.mdl-stepper#verify-stepper');
    if (!element)
        return false;
    var stepper = element.MaterialStepper;
    var steps = element.querySelectorAll('.mdl-step');
    steps[0].addEventListener('onstepnext', function (e) {
        $("#link-with-services input:checked").each(function () {
            linkWithServices.push(this.id.substr(9));
        });

        fetchLinkSetupRequired();

        stepper.next();
    });
    steps[1].addEventListener('onstepnext', function (e) {
        fetchLinkSetupRequired();
    });
    steps[1].addEventListener('onstepcancel', function (e) {
        location.reload();
    });
    steps[2].addEventListener('onstepnext', function (e) {
        setAPIKey(stepper, steps[2]);
    });
    steps[2].addEventListener('onstepcancel', function (e) {
        location.reload();
    });
    element.addEventListener('onsteppercomplete', function (e) {
        onComplete();
    });
};

function onComplete() {
    switchTab();
    onUserIdentifyEstablished = onUserIdentityEstablishedPostConfig;
}

function switchTab() {
    skipStepper();
    var tabId = getParameterByName("tab");
    if (tabId != null) {
        document.getElementById("tab" + tabId + "-link").click();
    } else {
        document.getElementById("tab2-link").click();
    }
    adjustHeight();
}

function skipStepper() {
    var element = document.querySelector('.mdl-stepper#verify-stepper');
    if (!element)
        return false;
    var stepper = element.MaterialStepper;
    var stepId = stepper.getActiveId();
    nextLoop(stepper, 3 - stepId);
}

function getAccessLabelFromAccessId(accessId) {
    switch (accessId) {
        case '0':
            return "GuildWars2";
        case '1':
            return "Heart of Thorns";
        case '2':
            return "Play For Free";
        case '3':
            return "None";
        case '-1':
            return "Temporary";
    }
}

function getWorldNameFromId(worldId) {
    return world_names[worldId];
}

//Pre-saved list of server names to avoid contacting the GW2 api each time to fetch
//the server names
var world_names = {
    1001: "Anvil Rock",
    1002: "Borlis Pass",
    1003: "Yak's Bend",
    1004: "Henge of Denravi",
    1005: "Maguuma",
    1006: "Sorrow's Furnace",
    1007: "Gate of Madness",
    1008: "Jade Quarry",
    1009: "Fort Aspenwood",
    1010: "Ehmry Bay",
    1011: "Stormbluff Isle",
    1012: "Darkhaven",
    1013: "Sanctum of Rall",
    1014: "Crystal Desert",
    1015: "Isle of Janthir",
    1016: "Sea of Sorrows",
    1017: "Tarnished Coast",
    1018: "Northern Shiverpeaks",
    1019: "Blackgate",
    1020: "Ferguson's Crossing",
    1021: "Dragonbrand",
    1022: "Kaineng",
    1023: "Devona's Rest",
    1024: "Eredon Terrace",
    2001: "Fissure of Woe",
    2002: "Desolation",
    2003: "Gandara",
    2004: "Blacktide",
    2005: "Ring of Fire",
    2006: "Underworld",
    2007: "Far Shiverpeaks",
    2008: "Whiteside Ridge",
    2009: "Ruins of Surmia",
    2010: "Seafarer's Rest",
    2011: "Vabbi",
    2012: "Piken Square",
    2013: "Aurora Glade",
    2014: "Gunnar's Hold",
    2101: "Jade Sea [FR]",
    2102: "Fort Ranik [FR]",
    2103: "Augury Rock [FR]",
    2104: "Vizunah Square [FR]",
    2105: "Arborstone [FR]",
    2201: "Kodash [DE]",
    2202: "Riverside [DE]",
    2203: "Elona Reach [DE]",
    2204: "Abaddon's Mouth [DE]",
    2205: "Drakkar Lake [DE]",
    2206: "Miller's Sound [DE]",
    2207: "Dzagonur [DE]",
    2301: "Baruch Bay [SP]"
};