<?php

use GW2Integration\Persistence\Helper\ServicePersistencyHelper;
use GW2Integration\Persistence\Helper\SettingsPersistencyHelper;
use GW2Integration\REST\RESTHelper;

require_once __DIR__ . "/RestrictAdminPanel.php";

$linkedUser = RESTHelper::getLinkedUserFromParams();
?>

<!-- Material Icons -->
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<!-- MDL CSS -->
<link rel="stylesheet" href="https://code.getmdl.io/1.2.0/material.indigo-blue.min.css" />
<!-- JQuery UI CSS -->
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.0/themes/base/jquery-ui.css">
<!-- Custom CSS -->
<link rel="stylesheet" href="<?php echo $webPath ?>/css/style.css">
<!-- MDL JS -->
<script src="https://code.getmdl.io/1.2.0/material.min.js"></script>
<!--getmdl-select-->   
<link rel="stylesheet" href="https://cdn.rawgit.com/CreativeIT/getmdl-select/master/getmdl-select.min.css">
<script defer src="https://cdn.rawgit.com/CreativeIT/getmdl-select/master/getmdl-select.min.js"></script>
<!-- JQuery UI JS -->
<script src="<?php echo $webPath ?>/js/jquery-2.2.3.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.0/jquery-ui.js"></script>
<script src="https://malsup.github.io/jquery.form.js"></script> 
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script src="<?php echo $webPath ?>/js/common.js"></script>
<script src="<?php echo $webPath ?>/js/admin.js"></script>

<meta name="viewport" content="initial-scale=1, maximum-scale=1">
<script>
    webPath = "<?php echo $webPath ?>";
</script>

<div id="gw2i-container">
    <div id="gw2i-notification-container">
        <?php
        if (SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::IS_API_DOWN)) {
            echo '<div class="alert-box warning">The Guild Wars 2 API is current experiencing issues</div>';
        }
        ?>
    </div>
    <div class="mdl-tabs vertical-mdl-tabs mdl-js-tabs">
        <div style="width: 100%;">
            <div class='vertical-mdl-tabs-bar'>
                <div class="mdl-tabs__tab-bar">
                    <a id="tab1-link" href="#tab1" class="mdl-tabs__tab is-active">Lookup</a>
                    <a id="tab2-link" href="#tab2" class="mdl-tabs__tab">Synchronization</a>
                    <a id="tab3-link" href="#tab3" class="mdl-tabs__tab">API Key Management</a>
                    <a id="tab4-link" href="#tab4" class="mdl-tabs__tab">Bans</a>
                    <a id="tab5-link" href="#tab5" class="mdl-tabs__tab">Logging</a>
                    <a id="tab6-link" href="#tab6" class="mdl-tabs__tab">Statistics</a>
                    <a id="tab7-link" href="#tab7" class="mdl-tabs__tab">Config</a>
                    <a id="tab8-link" href="#tab8" class="mdl-tabs__tab">Services</a>
                </div>
            </div>

            <div class="vertical-mdl-tabs-panels">
                <div class="mdl-tabs__panel is-active" id="tab1">
                    <div class='primaryheading'>
                        <h5>Data Lookup</h5>
                        <p>Lookup data for a user using either of the user's id<br />
                            Output is currently dumped directly from the database, so it isn't as nice to read as it could be</p>
                        <form action='ManagementController.php' method="POST" name='search' class="default-admin-form">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input class="mdl-textfield__input" type="text" name="search-user-id" id="search-user-id">
                                <label class="mdl-textfield__label" for="search-user-id">User Identification</label>
                            </div>
                            <br />
                            <?php
                            echo'   <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" style="width: initial; padding-right: 10px" for="checkbox-link-id">
                                                <input type="radio" name="search-service" id="checkbox-link-id" class="mdl-radio__button" value="link-id">
                                                <span class="mdl-radio__label">Universal User Id</span>
                                            </label>';
                            echo'   <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" style="width: initial; padding-right: 10px" for="checkbox-account-name">
                                                <input type="radio" name="search-service" id="checkbox-account-name" class="mdl-radio__button" value="account-name">
                                                <span class="mdl-radio__label">Account Name</span>
                                            </label>';
                            foreach ($gw2i_linkedServices AS $linkedService) {
                                echo'   <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" style="width: initial; padding-right: 10px" for="checkbox-' . $linkedService->getServiceId() . '">
                                                <input type="radio" name="search-service" id="checkbox-' . $linkedService->getServiceId() . '" class="mdl-radio__button" name="options" value="' . $linkedService->getServiceId() . '">
                                                <span class="mdl-radio__label">' . $linkedService->getName() . '</span>
                                            </label>';
                            }
                            ?>
                            <br /><br />
                            <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner">
                                Search
                            </button> 
                            <div class="mdl-spinner mdl-js-spinner is-active spinner-button"></div>

                            <div class="response-div response-div-style"></div>
                            <br /><br />
                        </form>
                    </div>
                </div>
                <div class="mdl-tabs__panel" id="tab2">
                    <div class='primaryheading'>
                        <h5>Synchronize Selected Users</h5>
                        <p>Synchronize the provided users below based on their id's, sepereated by commas</p>

                        <form action='ManagementController.php' method="POST" name='sync-users' class="default-admin-form">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input class="mdl-textfield__input" type="text" id="sync-users" name="sync-users">
                                <label class="mdl-textfield__label" for="sync-users">Synchronize users</label>
                            </div>
                            <br />
                            <?php
                            echo'   <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" style="width: initial; padding-right: 10px" for="sync-service-link-id">
                                            <input type="radio" id="sync-service-link-id" class="mdl-radio__button" name="sync-service" value="link-id">
                                            <span class="mdl-radio__label">Universal Link Id</span>
                                        </label>';
                            foreach ($gw2i_linkedServices AS $linkedService) {
                                echo'   <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" style="width: initial; padding-right: 10px" for="sync-service-' . $linkedService->getServiceId() . '">
                                                <input type="radio" id="sync-service-' . $linkedService->getServiceId() . '" class="mdl-radio__button" name="sync-service" value="' . $linkedService->getServiceId() . '">
                                                <span class="mdl-radio__label">' . $linkedService->getName() . '</span>
                                            </label>';
                            }
                            ?>
                            <br /><br />
                            <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner">
                                Sync Users
                            </button> 
                            <div class="mdl-spinner mdl-js-spinner is-active spinner-button"></div>

                            <div class="response-div response-div-style"></div>
                            <br /><br />
                        </form>
                    </div>

                    <div class='secondaryheading'>
                        <h5>Batch Syncronization</h5>
                        <p>Synchronize the next X amount of user's schedualed to be synchronized with the GuildWars 2 API</p>
                        <form action='ManagementController.php' method="POST" name='batch-process' class="default-admin-form">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input class="mdl-textfield__input" type="text" pattern="-?[0-9]*(\.[0-9]+)?" name="batch-process-count" id="batch-process-count" value="<?php echo SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEYS_PER_RUN); ?>">
                                <label class="mdl-textfield__label" for="batch-process-count">Synchronize amount</label>
                                <span class="mdl-textfield__error">Input is not a number!</span>
                            </div>
                            <br />
                            <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner">
                                Run Batch Sync
                            </button> 
                            <div class="mdl-spinner mdl-js-spinner is-active spinner-button"></div>

                            <div class="response-div response-div-style"></div>
                            <br /><br />
                        </form>
                    </div>

                    <div class='primaryheading'>
                        <h5>Generate Session Link</h5>
                        <p>Generate a unique link for accessing/linking a user</p>

                        <form action='ManagementController.php' method="POST" name='gen-user-session' class="default-admin-form">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input class="mdl-textfield__input" type="text" name="gen-session-user-id">
                                <label class="mdl-textfield__label" for="gen-session-user-id">User Identification</label>
                            </div>
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input class="mdl-textfield__input" type="text" name="gen-session-user-ip">
                                <label class="mdl-textfield__label" for="gen-session-user-ip">User Ip</label>
                            </div>
                            <br />

                            <p>Is the session a primary or secondary one?</p>
                            <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="gen-service-is-primary">
                                <input type="checkbox" name="is-primary" id="gen-service-is-primary" class="mdl-checkbox__input">
                                <span class="mdl-checkbox__label">Primary</span>
                            </label>
                            <br />
                            <br />
                            <p>What service is the Id for?</p>
                            <?php
                            foreach ($gw2i_linkedServices AS $linkedService) {
                                echo'   <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" style="width: initial; padding-right: 10px" for="gen-service-' . $linkedService->getServiceId() . '">
                                                <input type="radio" id="gen-service-' . $linkedService->getServiceId() . '" class="mdl-radio__button" name="gen-service" value="' . $linkedService->getServiceId() . '">
                                                <span class="mdl-radio__label">' . $linkedService->getName() . '</span>
                                            </label>';
                            }
                            ?>
                            <br /><br />
                            <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner">
                                Generate
                            </button> 
                            <div class="mdl-spinner mdl-js-spinner is-active spinner-button"></div>

                            <div class="response-div response-div-style"></div>
                            <br /><br />
                        </form>
                    </div>
                </div>
                <div class="mdl-tabs__panel" id="tab3">
                    <div class='primaryheading'>
                        <h5>Set API Key</h5>
                        <p>Set the API Key for a user</p>
                        <form action='ManagementController.php' method="POST" name='set-api-key' class="default-admin-form">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input class="mdl-textfield__input" type="text" name="user-id" id="user-id">
                                <label class="mdl-textfield__label" for="user-id">User Identification</label>
                            </div>
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label" style="width: 100%">
                                <input class="mdl-textfield__input" type="text" name="api-key" id="api-key">
                                <label class="mdl-textfield__label" for="batch-process-count">API Key</label>
                            </div>
                            <br />

                            <?php
                            foreach ($gw2i_linkedServices AS $linkedService) {
                                echo'   <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" style="width: initial; padding-right: 10px" for="set-key-service-' . $linkedService->getServiceId() . '">
                                                <input type="radio" id="set-key-service-' . $linkedService->getServiceId() . '" class="mdl-radio__button" name="set-key-service" value="' . $linkedService->getServiceId() . '">
                                                <span class="mdl-radio__label">' . $linkedService->getName() . '</span>
                                            </label>';
                            }
                            ?>
                            <br />

                            <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="ignore-api-key-restrictions">
                                <input type="checkbox" name="ignore-api-key-restrictions" id="ignore-api-key-restrictions" class="mdl-checkbox__input" disabled>
                                <span class="mdl-checkbox__label">Ignore API Key Restrictions</span>
                            </label>
                            <br /><br />

                            <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner">
                                Set API Key
                            </button> 
                            <div class="mdl-spinner mdl-js-spinner is-active spinner-button"></div>

                            <div class="response-div response-div-style"></div>
                            <br /><br />
                        </form>
                    </div>

                    <div class='secondaryheading'>
                        <h5>Generate Unique API Key Name</h5>
                        <p>Generate a Unique API Key name for a specific user given the service they attempt to verify themselves with</p>

                        <form action='ManagementController.php' method="POST" name='get-api-key-name' class="default-admin-form">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input class="mdl-textfield__input" type="text" name="user-id" id="user-id">
                                <label class="mdl-textfield__label" for="user-id">User Identification</label>
                            </div>
                            <br />

                            <?php
                            foreach ($gw2i_linkedServices AS $linkedService) {
                                echo'   <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" style="width: initial; padding-right: 10px" for="get-key-name-service-' . $linkedService->getServiceId() . '">
                                                <input type="radio" id="get-key-name-service-' . $linkedService->getServiceId() . '" class="mdl-radio__button" name="get-key-name-service" value="' . $linkedService->getServiceId() . '">
                                                <span class="mdl-radio__label">' . $linkedService->getName() . '</span>
                                            </label>';
                            }
                            ?>
                            <br /><br />

                            <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner">
                                Generate API Key Name
                            </button> 
                            <div class="mdl-spinner mdl-js-spinner is-active spinner-button"></div>

                            <div class="response-div response-div-style"></div>
                            <br /><br />
                        </form>
                    </div>
                </div>
                <div class="mdl-tabs__panel" id="tab4">
                    <div class='primaryheading'>
                        <h5>Ban User</h5>
                        <p>Ban a user based on the selected attribute</p>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input class="mdl-textfield__input" type="text" id="ban-attr-value">
                            <label class="mdl-textfield__label" for="ban-attr-value">User Attribute</label>
                        </div>
                        <br />

                        <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" style="width: initial; padding-right: 10px" for="option-gw2-account">
                            <input type="radio" id="option-gw2-account" class="mdl-radio__button" name="options" value="gw2-account">
                            <span class="mdl-radio__label">Guild Wars 2 Account Name</span>
                        </label>
                        <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" style="width: initial; padding-right: 10px" for="option-ip">
                            <input type="radio" id="option-ip" class="mdl-radio__button" name="options" value="user-ip">
                            <span class="mdl-radio__label">User IP</span>
                        </label>

                        <br /><br />
                        <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner" disabled>
                            Ban
                        </button> 
                        <div class="mdl-spinner mdl-js-spinner is-active spinner-button"></div>

                        <br /><br />
                    </div>
                    <div class='secondaryheading'>
                        <h5>Banned Users</h5>
                        <p>See existing bans and/or unban banned users</p>
                        <table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp table-td-ta-left" style="width: 100%">
                            <thead>
                                <tr>
                                    <th class="mdl-data-table__cell--non-numeric">Ban Attribute</th>
                                    <th class="mdl-data-table__cell--non-numeric">Ban Type</th>
                                    <th class="mdl-data-table__cell--non-numeric">Reason</th>
                                    <th class="mdl-data-table__cell--non-numeric">Banned By</th>
                                    <th class="mdl-data-table__cell--non-numeric">Timestamp</th>
                                    <th class="mdl-data-table__cell--non-numeric">Manage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Not available</td>
                                    <td>Not available</td>
                                    <td>Not available</td>
                                    <td>Not available</td>
                                    <td>Not available</td>
                                    <td>Not available</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mdl-tabs__panel" id="tab5">
                    <div class='primaryheading'>
                        <h5>Event Log</h5>
                        <p>Log of every event created by the Guild Wars 2 Integration</p>
                        <table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp table-td-ta-left" style="width: 100%">
                            <thead>
                                <tr>
                                    <th class="mdl-data-table__cell--non-numeric">Link Id</th>
                                    <th class="mdl-data-table__cell--non-numeric">Account Name</th>

                                    <?php
                                    foreach ($gw2i_linkedServices AS $linkedService) {
                                        echo '<th class="mdl-data-table__cell--non-numeric">' . $linkedService->getName() . '</th>';
                                    }
                                    ?>

                                    <th class="mdl-data-table__cell--non-numeric">Timestamp</th>
                                    <th class="mdl-data-table__cell--non-numeric">Event Type</th>
                                    <th class="mdl-data-table__cell--non-numeric">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Not available</td>
                                    <td>Not available</td>
                                    <td>Not available</td>
                                    <td>Not available</td>
                                    <td>Not available</td>
                                    <td>Not available</td>
                                    <td>Not available</td>
                                </tr>
                            </tbody>
                        </table>
                        <br />
                        <center>
                            <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner">
                                Newer
                            </button> 
                            <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner">
                                Older
                            </button> 
                            <div class="mdl-spinner mdl-js-spinner is-active spinner-button"></div>
                        </center>
                    </div>
                    <div class='secondaryheading'>
                        <h5>Detailed Log</h5>
                        <p>Detailed log read directly from the log file <b><?php echo "log_" . date("Y-m-d") . ".log"; ?></b></p>

                        <form action='ManagementController.php' method="POST" name='get-log' class="log-admin-form">
                            <p>
                                Current Timestamp: <b style="text-transform: uppercase;"><?php echo date("Y-m-d H:i:s"); ?></b>
                                <br />
                                Current logging level: <b style="text-transform: uppercase;"><?php echo $loggingLevel; ?></b>
                            </p>
                            <button id="fetch-latest-log-btn" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner">
                                Fetch latest log
                            </button> 
                            <button onclick="scrollToBottom('#detailed-log-container'); return false;" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner" style="margin-left: 10px;">
                                Scroll to bottom
                            </button> 
                            <div class="mdl-spinner mdl-js-spinner is-active spinner-button"></div>
                            <br />
                            <br />
                            <div id="detailed-log" class="detailed-log resizeable">
                                <div id="detailed-log-container" class="detailed-log-container mdl-shadow--2dp response-div">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="mdl-tabs__panel" id="tab6">
                    <div class='primaryheading'>
                        <h5>World Distribution</h5>
                        <p>See how world distrubution for the linked members evolv over time</p>
                        <form action='ManagementController.php' method="POST" name='get-statistics-world-distribution' class="statistics-admin-form">
                            <div id="chart_div"></div>
                            <button id='update-world-dist-btn' class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner">
                                Update
                            </button>
                            <div class="mdl-spinner mdl-js-spinner is-active spinner-button"></div>

                            <div class="response-div response-div-style"></div>
                            <br /><br />
                        </form>
                    </div>
                    <div class='secondaryheading'>
                        <h5>Collect Available Statistics</h5>
                        <p>Collect the currently available statistics that does not require certain events to be collected</p>
                        <form action='ManagementController.php' method="POST" name='collect-statistics' class="default-admin-form">
                            <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner">
                                Collect Statistics
                            </button> 
                            <div class="mdl-spinner mdl-js-spinner is-active spinner-button"></div>

                            <div class="response-div response-div-style"></div>
                            <br /><br />
                        </form>
                    </div>
                </div>
                <div class="mdl-tabs__panel" id="tab7">
                    <div class='primaryheading'>
                        <h5>Settings</h5>
                        <p>Edit the settings used by the application below</p>
                        <form action='ManagementController.php' method="POST" name='update-settings' class="default-admin-form">
                            <?php
                            $settings = SettingsPersistencyHelper::getAllSetting();
                            foreach (SettingsPersistencyHelper::$visibleSettings AS $settingName) {
                                $settingValue = isset($settings[$settingName]) ? $settings[$settingName] : "";
                                echo '  <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                                <input class="mdl-textfield__input" type="text" id="setting-' . $settingName . '" value="' . $settingValue . '" name="' . $settingName . '">
                                                <label class="mdl-textfield__label" for="setting-' . $settingName . '" style="text-transform: uppercase;">' . str_replace("_", " ", $settingName) . '</label>
                                            </div><br />';
                            }
                            ?>
                            <br />
                            <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner">
                                Save
                            </button> 
                            <div class="mdl-spinner mdl-js-spinner is-active spinner-button"></div>

                            <div class="response-div response-div-style"></div>
                            <br /><br />
                        </form>
                    </div>
                    <div class='secondaryheading'>
                        <h5>Service Group Mapping</h5>
                        <p>Map a world to a given group in a service</p>
                        <form action='ManagementController.php' method="POST" name='update-service-group-mappings' class="default-admin-form">
                            <table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>World</th>
                                        <th>Group Id</th>
                                        <th>Primary</th>
                                        <th>Manage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach (ServicePersistencyHelper::getWorldToGroupSettings() AS $worldToGroup) {
                                        echo '<tr class="world-to-group">';
                                        echo '<td>' . $gw2i_linkedServices[$worldToGroup["service_id"]]->getName() . '</td>';
                                        echo '<td>' . $worldToGroup["world"] . '</td>';
                                        echo '<td>' . $worldToGroup["group_id"] . '</td>';
                                        echo '<td>' . $worldToGroup["is_primary"] . '</td>';
                                        echo '<td></td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>

                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label getmdl-select" style="width: inherit;">
                                <input class="mdl-textfield__input" value="Belarus" type="text" id="country" readonly tabIndex="-1" data-val="BLR"/>
                                <label class="mdl-textfield__label" for="country">Country</label>
                                <ul class="mdl-menu mdl-menu--bottom-left mdl-js-menu" for="country">
                                    <li class="mdl-menu__item" data-val="BLR">Belarus</li>
                                    <li class="mdl-menu__item" data-val="RUS">Russia</li>
                                </ul>
                            </div>
                            <br />
                            <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner">
                                Save
                            </button> 
                            <div class="mdl-spinner mdl-js-spinner is-active spinner-button"></div>

                            <div class="response-div response-div-style"></div>
                            <br /><br />
                        </form>
                    </div>
                </div>
                <div class="mdl-tabs__panel" id="tab8">

                    <div class="mdl-tabs mdl-js-tabs">
                        <?php
                        $even = true;
                        $content = '';
                        foreach ($gw2i_linkedServices AS $service) {
                            if ($service->hasConfigPage()) {
                                $content .= '<div class="' . ($even ? "primaryheading" : "secondaryheading") . '" id="service-tab' . $service->getServiceId() . '"><h4>' . $service->getName() . '</h4>' . $service->getConfigPageHTML() . '</div>';

                                $even = !$even;
                            }
                        }
                        echo $content;
                        ?>
                    </div>
                </div>
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