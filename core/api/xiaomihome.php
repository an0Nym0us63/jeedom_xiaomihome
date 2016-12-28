<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

if (!jeedom::apiAccess(init('apikey'), 'xiaomihome')) {
    echo __('Clef API non valide, vous n\'êtes pas autorisé à effectuer cette action (xiaomihome)', __FILE__);
    die();
}

if (init('type') == 'yeelight') {
    log::add('xiaomihome', 'debug', 'Yeelight ' . init('yeelight'));
}

$body = json_decode(file_get_contents('php://input'), true);
if (init('sid') !== null && init('model') !== null) {
    if (init('model') == 'gateway') {
        if (init('cmd') == 'heartbeat') {
            xiaomihome::receiveHeartbeat(init('sid'), init('model'), $body['ip']);
        } else {
            xiaomihome::receiveId(init('sid'), init('model'), init('gateway'), init('short_id'));
        }
    } else {
        xiaomihome::receiveId(init('sid'), init('model'), init('gateway'), init('short_id'));
        log::add('xiaomihome', 'debug', 'Recu ' . init('sid') . ' ' . init('model') . ' ' . print_r($body, true));
        if (is_array($body)) {
            foreach ($body as $key => $value) {
                xiaomihome::receiveData(init('sid'), init('model'), $key, $value);
            }
        }
    }
}


return true;

?>
