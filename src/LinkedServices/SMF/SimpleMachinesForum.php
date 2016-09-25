<?php

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

namespace GW2Integration\LinkedServices\SMF;

require_once($_SERVER['DOCUMENT_ROOT'].'/SSI.php');

use GW2Integration\Entity\LinkedUser;
use GW2Integration\LinkedServices\LinkedService;

/**
 * Description of SimpleMachineForums
 *
 * @author Jeppe Boysen Vennekilde
 */
class SimpleMachinesForum extends LinkedService{
    
    const serviceId = 0;
    
    function __construct() {
        parent::__construct(
                static::serviceId, 
                "Website", //"Simple Machine Forums", 
                "Link your GuildWars 2 account with the website. <br /><i>The advantage here is the ease of managing your API keys with your website login</i>", 
                "You should not be able to see this message, unless an error happened, contact an admin",
                true,
                true,
                false
            );
        $this->hasUserGroupId(151,16);
    }
    
    /**
     * 
     * @param LinkedUser $linkedUser
     * @return LinkedUser
     */
    public function getLinkedUserIfAvailable(LinkedUser $linkedUser) {
        $userId = $this->getCurrentlyLoggedInUserId();
        if(isset($userId) && $userId > 0){
            $linkedUser->setPrimaryarySeviceId($this->getCurrentlyLoggedInUserId(), $this->getServiceId(), $this->getCurrentlyLoggedInDisplayName());
        }
        return $linkedUser;
    }
    
    /*******************************************
    * Forum Integration methods
    *******************************************/
    public function getCurrentlyLoggedInUserId(){
        global $user_info, $context;
        if ($context['user']['is_guest']){
            return -1;
        } else {
            return $user_info["id"];
        }
    }

    public function getCurrentlyLoggedInDisplayName(){
        global $user_info, $context;
        if ($context['user']['is_guest']){
            return null;
        } else {
            return $user_info['name'];
        }
    }
    
    public function getCurrentlyLoggedInUserIp(){
        global $user_info;
        return $user_info["ip"];
    }
    
    public function getCreateNewUserPageURL(){
        return "/index.php?action=register";
    }
    
    public function hasUserGroupId($serviceUserId, $groupId) {
        global $user_profile;
        loadMemberData($serviceUserId, false, 'profile');
        if($user_profile[$serviceUserId]["id_group"] == $groupId){
            return true;
        } else {
            $additionalGroups = explode(",", $user_profile[$serviceUserId]["additional_groups"]);
            return in_array($groupId, $additionalGroups);
        }
    }
    
    public function getConfigPageHTML(){
        return null;
    }
    
    /**
     * 
     * @global type $context
     * @global type $scripturl
     * @global type $settings
     * @global type $txt
     * @param type $successJSFunction This JS function will be called when the has been succesfully set up the link
     * @return string
     */
    public function getLinkSetupHTML($successJSFunction) {
        global $context, $scripturl, $settings, $txt;
        $html = '<script type="text/javascript" src="/Themes/default/scripts/sha1.js"></script>
            <script type="text/javascript" src="/Themes/default/scripts/script.js"></script>
            <script src="//malsup.github.io/jquery.form.js"></script> 
            <script>
                <!-- // --><![CDATA[
                    var smf_theme_url = "'. $settings['theme_url']. '";
                    var smf_default_theme_url = "'. $settings['default_theme_url']. '";
                    var smf_images_url = "'. $settings['images_url']. '";
                    var smf_scripturl = "'. $scripturl. '";
                    var smf_iso_case_folding = '. ($context['server']['iso_case_folding'] ? 'true' : 'false'). ';
                    var smf_charset = "'. $context['character_set'] .'";'. ($context['show_pm_popup'] ? '
                    var fPmPopup = function ()
                    {
                        if (confirm("' . $txt['show_personal_messages'] . '"))
                            window.open(smf_prepareScriptUrl(smf_scripturl) + "action=pm");
                    }
                    addLoadEvent(fPmPopup);' : ''). '
                    var ajax_notification_text = "'. $txt['ajax_in_progress']. '";
                    var ajax_notification_cancel_text = "'. $txt['modify_cancel']. '";
                // ]]>
                
                $( document ).ready(function() {
                    //Ensure script is loaded, no way someone will log-in within 2 seconds anyway (probably ^^)
                    setTimeout(function(){ 
                        $("#frmLogin").ajaxForm({url: "'. $scripturl. '?action=login2", type: "post", success: onLoginFormSubmittedSuccess});
                    }, 2000);
                    
                });
                
                function onLoginFormSubmittedSuccess(){
                    '.$successJSFunction.'('.$this->getServiceId().');
                }
                
                function createNewForumUser(){
                    window.open("'.$this->getCreateNewUserPageURL().'","_blank");
                    return false;
                }
                    
                function submitLogin(doForm, cur_session_id){
                    hashLoginPassword(doForm, cur_session_id);
                    return false;
                }
            </script>
            <form name="frmLogin" id="frmLogin" method="post" style="white-space: normal;" accept-charset="'. $context['character_set']. '" '. (empty($context['disable_login_hashing']) ? ' onsubmit="submitLogin(this, \'' . $context['session_id'] . '\');"' : ''). '>
                <h5>Login to the website</h5>
                <div class="mdl-textfield mdl-js-textfield">
                    <label for="user" class="mdl-textfield__label">Username</label>
                    <input type="text" id="user" name="user" class="mdl-textfield__input"/>
                </div>
                <div class="mdl-textfield mdl-js-textfield">
                    <label for="passwrd" class="mdl-textfield__label">Password</label>
                    <input type="password" name="passwrd" id="passwrd" class="mdl-textfield__input">
                </div>
                <input type="hidden" name="cookielength" value="-1" />
                <input type="hidden" name="hash_passwrd" value="" />
                <div style="display: inline-block">
                    <button class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored">
                        Login
                    </button>
                    <button type="button" class="mdl-button mdl-js-button mdl-button--raised" style="margin-left: 5px;" onclick="createNewForumUser()">
                        Create User
                    </button>
                </div>
            </form>';
        
        return $html;
    }
}
