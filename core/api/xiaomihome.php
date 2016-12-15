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

 $body = json_decode(file_get_contents('php://input'), true);
 log::add('xiaomihome', 'debug', 'Recu ' . print_r($body, true));
 xiaomihome::receiveId($body['sid'], $body['model']);
 foreach ($body['data'] as $key => $value) {
     xiaomihome::receiveData($body['sid'], $key, $value);
 }

 return true;

?>
