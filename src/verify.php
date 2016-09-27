<?php

use GW2Integration\API\APIKeyManager;
use GW2Integration\Controller\LinkedUserController;
use GW2Integration\Persistence\Helper\SettingsPersistencyHelper;
use GW2Integration\REST\RESTHelper;

require_once __DIR__ . "/Source.php";

$mainUserServiceLink = RESTHelper::getMainUserServiceLink();

if(isset($mainUserServiceLink)){
    $sessionUserServiceLinks = RESTHelper::getSessionUserServiceLinks();
    LinkedUserController::mergeUserServiceLinks($mainUserServiceLink, $sessionUserServiceLinks);
}
$linkedUser = RESTHelper::getLinkedUserFromParams();
?>


<!doctype html>
<!--
  Material Design Lite
  Copyright 2015 Google Inc. All rights reserved.

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      https://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License
-->
<!-- Material Icons -->
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<!-- MDL CSS -->
<link rel="stylesheet" href="https://code.getmdl.io/1.2.0/material.indigo-blue.min.css" />
<!-- MDL JS -->
<script src="https://code.getmdl.io/1.2.0/material.min.js"></script>
<!-- Stepper minified CSS -->
<link rel="stylesheet" href="<?php echo $webPath ?>/mdl/css/stepper.min.css">
<!-- Custom CSS -->
<link rel="stylesheet" href="<?php echo $webPath ?>/css/style.css">
<!-- Stepper minified JS -->
<script src="<?php echo $webPath ?>/mdl/js/stepper.min.js"></script>
<script src="<?php echo $webPath ?>/js/jquery-2.2.3.min.js"></script>
<script src="<?php echo $webPath ?>/js/common.js"></script>
<script src="<?php echo $webPath ?>/js/api-link-setup.js"></script>
<script src="<?php echo $webPath ?>/js/service-links-tab.js"></script>

<script> 
    webPath = "<?php echo $webPath ?>";
</script>

<div id="gw2i-container">
    <div id="gw2i-notification-container">
        <?php
            if(SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::IS_API_DOWN)){
                echo '<div class="alert-box warning">The Guild Wars 2 API is current experiencing issues</div>';
            }
        ?>
    </div>
    <div class="mdl-tabs mdl-js-tabs">
        <div class="mdl-tabs__tab-bar">
            <a id="tab1-link" href="#tab1" class="mdl-tabs__tab is-active">Integration Setup</a>
            <a id="tab2-link" href="#tab2" class="mdl-tabs__tab">Integration Status</a>
            <a id="tab3-link" href="#tab3" class="mdl-tabs__tab is-disabled">Music Bots</a>
        </div>

        <div class="mdl-tabs__panel is-active" id="tab1">
            <!-- markup -->
            <ul class="mdl-stepper mdl-stepper--linear mdl-stepper--feedback" id="verify-stepper">
                <li class="mdl-step">
                    <span class="mdl-step__label">
                        <span class="mdl-step__title">
                            <span class="mdl-step__title-text">Which services do you wish to link your GuildWars 2 Account with?</span>
                        </span>
                    </span>
                    <div id="link-with-services" class="mdl-step__content">
                        <?php
                            foreach($gw2i_linkedServices AS $linkedService){

                                $isAvailable = $linkedService->canDetermineLinkDuringSetup() ? true : isset($linkedUser->getPrimaryUserServiceLinks()[$linkedService->getServiceId()]);

                                echo'<div class="linked-service-container"><label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="checkbox-'.$linkedService->getServiceId().'">
                                        <input type="checkbox" id="checkbox-'.$linkedService->getServiceId().'" class="mdl-checkbox__input" '.($isAvailable ? "checked" : "disabled").'>
                                        <span class="mdl-checkbox__label">'. $linkedService->getName() .'</span>
                                    </label>';
                                echo $linkedService->getDescription();

                                if(!$isAvailable){
                                    echo "<div class='service_link_unavailable'>".$linkedService->getLinkNotAvailable()."</div>";
                                }

                                echo "</div>";
                            }
                        ?>
                    </div>
                    <div class="mdl-step__actions">
                        <button class="mdl-button mdl-js-button mdl-js-ripple-effect mdl-button--colored mdl-button--raised" data-stepper-next>
                            Continue
                        </button>
                    </div>
                </li>
                <li class="mdl-step mdl-step--optional">
                    <span class="mdl-step__label">
                        <span class="mdl-step__title">
                            <span class="mdl-step__title-text">Establish Links</span>
                            <span class="mdl-step__title-message">Some services require you to possibly perform a few tasks, like logging in, in order to establish a link</span>
                        </span>
                    </span>
                    <div id="link-setup-content" class="mdl-step__content" id="demo-error-state-content">
                        <i>Acquiring required steps...</i>
                    </div>
                    <div class="mdl-step__actions">
                        <button class="mdl-button mdl-js-button mdl-js-ripple-effect mdl-button--colored mdl-button--raised" data-stepper-next>
                            Continue
                        </button>
                        <button class="mdl-button mdl-js-button mdl-js-ripple-effect" data-stepper-cancel>
                            Cancel
                        </button>
                    </div>
                </li>
                <li class="mdl-step mdl-step--editable">
                    <span class="mdl-step__label">
                        <span class="mdl-step__title">
                            <span class="mdl-step__title-text">Enter your API Key</span>
                        </span>
                    </span>
                    <div class="mdl-step__content">
                        <h5>Go to <a href="https://account.arena.net/applications/create" target="_blank">GuildWars 2 Website</a> and create your own API Key</h5>
                        <div class="information-container">
                            <i class="material-icons api-keyname-icon">info</i>
                            Ensure that your API Key is named
                        </div>
                        <div id="api-key-name"></div>
                        <br />
                        <div class="information-container">
                            <i class="material-icons account-permission-icon characters-permission-icon">info</i>
                            The API Key has to have the following permissions
                        </div>
                        <ul class="demo-list-item mdl-list">
                            <li class="mdl-list__item" style='padding: 0px 16px; min-height: inherit;'>
                                <span class="mdl-list__item-primary-content">
                                    <i class="material-icons mdl-list__item-icon account-permission-icon">label</i>
                                    Account
                                </span>
                            </li>
                            <li class="mdl-list__item" style='padding: 0px 16px; min-height: inherit;'>
                                <span class="mdl-list__item-primary-content">
                                    <i class="material-icons mdl-list__item-icon characters-permission-icon">label</i>
                                    Characters
                                </span>
                            </li>
                        </ul>
                        <div id="api-key-input-field" class="mdl-textfield mdl-js-textfield" style="width: 100%">
                            <label class="mdl-textfield__label" for="api-key-input">Enter your API Key</label>
                            <input id="api-key-input" class="mdl-textfield__input" type="text" pattern="<?php echo substr(APIKeyManager::API_KEY_REGEX, 0, -1) ?>" id="api-key-input">
                            <span class="mdl-textfield__error">This is not a valid API Key</span>
                        </div>
                    </div>
                    <div class="mdl-step__actions">
                        <button class="mdl-button mdl-js-button mdl-js-ripple-effect mdl-button--colored mdl-button--raised" data-stepper-next>
                            Continue
                        </button>
                        <button class="mdl-button mdl-js-button mdl-js-ripple-effect" data-stepper-cancel>
                            Cancel
                        </button>
                    </div>
                </li>
            </ul>
        </div>
        <div class="mdl-tabs__panel" id="tab2">
            <div class='primaryheading'>
                <h5>GuildWars 2 API Data Available</h5>
                <table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp table-td-ta-left" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="mdl-data-table__cell--non-numeric">Account Name</th>
                            <th class="mdl-data-table__cell--non-numeric">World</th>
                            <th class="mdl-data-table__cell--non-numeric">Access</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="account-name">Not available</td>
                            <td class="gw2-world">Not available</td>
                            <td class="gw2-access">Not available</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class='secondaryheading'>
                <h5>Service Linking</h5>
                <table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp table-td-ta-left" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="mdl-data-table__cell--non-numeric">Service</th>
                            <th class="mdl-data-table__cell--non-numeric">User Identification</th>
                            <th class="mdl-data-table__cell--non-numeric" style="width: 100%;">User Display Name</th>
                            <th class="mdl-data-table__cell--non-numeric" style="white-space: pre-wrap;text-align: right;width: 350px">Manage</th>
                        </tr>
                    </thead>
                    <tbody id="service-tbody">
                        <tr id="default-service-entry">
                            <td class="mdl-data-table__cell--non-numeric">There are currently no services to link with</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mdl-tabs__panel" id="tab3">
            <div class='primaryheading'>
                <h5>Registered Music Bots</h5>
                <table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp table-td-ta-left" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="mdl-data-table__cell--non-numeric">Service</th>
                            <th class="mdl-data-table__cell--non-numeric">Music Bot Identification</th>
                            <th class="mdl-data-table__cell--non-numeric" style="width: 100%;">Music Bot Display Name</th>
                            <th class="mdl-data-table__cell--non-numeric">Manage</th>
                        </tr>
                    </thead>
                    <tbody id="music-bot-tbody">
                        <tr id="default-music-bot-entry">
                            <td class="mdl-data-table__cell--non-numeric">You do not have any registered music bots</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div id="snackbar-stepper-complete" class="mdl-js-snackbar mdl-snackbar">
        <div class="mdl-snackbar__text"></div>
        <button class="mdl-snackbar__action" type="button"></button>
    </div>
    <div id="snackbar-stepper-error" class="mdl-js-snackbar mdl-snackbar">
        <div class="mdl-snackbar__text"></div>
        <button class="mdl-snackbar__action" type="button"></button>
    </div>
</div>