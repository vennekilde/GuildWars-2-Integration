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

$(document).ready(function () {
    if (typeof jQuery.ui !== 'undefined') {
        $(".resizeable").resizable();
    }

    $('.mdl-layout').on('mdl-componentupgraded', function (e) {
        if ($(e.target).hasClass('mdl-layout')) {
            adjustHeight();
        }
    });

    setTimeout(function () {
        adjustHeight();
    }, 100);

    //Resize content if inside iframe, when switching tab
    $('.mdl-tabs__tab').on('click', function () {
        setTimeout(function () {
            adjustHeight();
        }, 50);
    });

    $('body').on('mousedown', '.sceditor-grip', function(event) {
        console.log("test1");
        $(document).bind('mousemove', adjustHeight);
    });

    $(document).mouseup(function (e) {
        $(document).unbind('mousemove', adjustHeight);
    });
})

function generateAjaxPostData() {
    var data = {};
    var sessions = getParameterByName("ls-sessions");
    if (sessions !== undefined) {
        data["ls-sessions"] = sessions;
    }

    return data;
}
function adjustHeight() {
    if (parent.AdjustIframeHeight !== undefined) {
        var newHeight = document.getElementById("gw2i-container").scrollHeight;
//        if(newHeight <= 0) {
//            newHeight = $(".mdl-layout__tab-panel.is-active").outerHeight() + 120;
//            console.log(newHeight);
//        }
        parent.AdjustIframeHeight(newHeight + 10);
    }
}
function getParameterByName(name, url) {
    if (!url)
        url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
    if (!results)
        return null;
    if (!results[2])
        return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function setCookie(cname, cvalue, exseconds) {
    var d = new Date();
    d.setTime(d.getTime() + (exseconds * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

function decodeEntities(encodedString) {
    var textArea = document.createElement('textarea');
    textArea.innerHTML = encodedString;
    return textArea.value;
}

function mergeObjects(obj1, obj2) {
    return jQuery.extend(true, obj1, obj2);
}


function scrollToBottom(container) {
    $(container).scrollTop($(container)[0].scrollHeight);
}

function addNotitification(msg, type) {
    $("#gw2i-notification-container").append('<div class="alert-box ' + type + '">' + msg + '</div>');
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

function nextPage($inputCounter) {
    console.log($inputCounter);
    test = $inputCounter;
    var page = parseInt($inputCounter.val());
    page++;
    $inputCounter.val(page);
}
function prevPage($inputCounter) {
    var page = parseInt($inputCounter.val());
    if (page > 1) {
        page--;
        $inputCounter.val(page);
    }
}

function replaceTagName(id, replacementTag){
    // Replace all a tags with the type of replacementTag
    $(id).each(function() {
        var outer = this.outerHTML;

        // Replace opening tag
        var regex = new RegExp('<' + this.tagName, 'i');
        var newTag = outer.replace(regex, '<' + replacementTag);

        // Replace closing tag
        regex = new RegExp('</' + this.tagName, 'i');
        newTag = newTag.replace(regex, '</' + replacementTag);

        $(this).replaceWith(newTag);
    });
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