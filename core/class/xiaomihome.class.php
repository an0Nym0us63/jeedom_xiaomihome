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
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class xiaomihome extends eqLogic {

    public function checkCmdOk($_id, $_name, $_subtype, $_value) {
        $xiaomihomeCmd = xiaomihomeCmd::byEqLogicIdAndLogicalId($this->getId(),$_id);
        if (!is_object($xiaomihomeCmd)) {
            log::add('xiaomihome', 'debug', 'Création de la commande ' . $_id);
            $xiaomihomeCmd = new xiaomihomeCmd();
            $cmds = $this->getCmd();
            $order = count($cmds);
            $xiaomihomeCmd->setOrder($order);
            $xiaomihomeCmd->setName(__($_name, __FILE__));
            $xiaomihomeCmd->setEqLogic_id($this->id);
            $xiaomihomeCmd->setEqType('xiaomihome');
            $xiaomihomeCmd->setLogicalId($_id);
            $xiaomihomeCmd->setType('info');
            $xiaomihomeCmd->setSubType($_subtype);
            $xiaomihomeCmd->setTemplate("mobile",'line' );
            $xiaomihomeCmd->setTemplate("dashboard",'line' );
            $xiaomihomeCmd->setDisplay("forceReturnLineAfter","1");
            $xiaomihomeCmd->setConfiguration('value',$_value);
            $xiaomihomeCmd->save();
        }
    }


    public static function saveInclude($mode) {
        config::save('include_mode', $mode,  'xiaomihome');
        $state = 1;
        if ($mode == 1) {
            $state = 0;
        }
        event::add('xiaomihome::controller.data.controllerState',
            array(
                'state' => $state
            )
        );
    }

    public static function receiveData($json) {
        //log::add('rflink', 'debug', 'Body ' . print_r($json,true));
        $body = json_decode($json, true);
        switch ($body['type']) {
            case 'motion':
                xiaomihome::receiveMotion($json);
                break;
            case 'door':
                xiaomihome::receiveDoor($json);
                break;
            case 'switch':
                xiaomihome::receiveSwitch($json);
                break;
            case 'temp':
                xiaomihome::receiveTemp($json);
                break;
            case 'cube':
                xiaomihome::receiveMotion($json);
                break;
            default:
                # code...
                break;
        }
    }

    public static function deamon_info() {
        $return = array();
        $return['log'] = 'xiaomihome_node';
        $return['state'] = 'nok';
        $pid = trim( shell_exec ('ps ax | grep "xiaomihome/node/xiaomihome.js" | grep -v "grep" | wc -l') );
        if ($pid != '' && $pid != '0') {
            $return['state'] = 'ok';
        }
        $return['launchable'] = 'ok';
        return $return;
    }

    public static function deamon_start() {
        self::deamon_stop();
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }
        log::add('xiaomihome', 'info', 'Lancement du démon xiaomihome');

        $url = network::getNetworkAccess('internal') . '/plugins/xiaomihome/core/api/xiaomihome.php?apikey=' . jeedom::getApiKey('xiaomihome');
        $log = log::convertLogLevel(log::getLogLevel('xiaomihome'));
        $sensor_path = realpath(dirname(__FILE__) . '/../../node');
        $cmd = 'nice -n 19 nodejs ' . $sensor_path . '/xiaomihome.js ' . $url;

        log::add('xiaomihome', 'debug', 'Lancement démon xiaomihome : ' . $cmd);

        $result = exec('nohup ' . $cmd . ' >> ' . log::getPathToLog('xiaomihome_node') . ' 2>&1 &');
        if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
            log::add('xiaomihome', 'error', $result);
            return false;
        }

        $i = 0;
        while ($i < 30) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 30) {
            log::add('xiaomihome', 'error', 'Impossible de lancer le démon xiaomihome, vérifiez le port', 'unableStartDeamon');
            return false;
        }
        message::removeAll('xiaomihome', 'unableStartDeamon');
        log::add('xiaomihome', 'info', 'Démon xiaomihome lancé');
        sleep(5);
        return true;
    }

    public static function deamon_stop() {
        exec('kill $(ps aux | grep "xiaomihome/node/xiaomihome.js" | awk \'{print $2}\')');
        log::add('xiaomihome', 'info', 'Arrêt du service xiaomihome');
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok') {
            sleep(1);
            exec('kill -9 $(ps aux | grep "xiaomihome/node/xiaomihome.js" | awk \'{print $2}\')');
        }
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok') {
            sleep(1);
            exec('sudo kill -9 $(ps aux | grep "xiaomihome/node/xiaomihome.js" | awk \'{print $2}\')');
        }
    }

    public static function dependancy_info() {
        $return = array();
        $return['log'] = 'xiaomihome_dep';
        $serialport = realpath(dirname(__FILE__) . '/../../node/node_modules/serialport');
        $request = realpath(dirname(__FILE__) . '/../../node/node_modules/request');
        $return['progress_file'] = '/tmp/xiaomihome_dep';
        if (is_dir($serialport) && is_dir($request)) {
            $return['state'] = 'ok';
        } else {
            $return['state'] = 'nok';
        }
        return $return;
    }

    public static function dependancy_install() {
        log::add('xiaomihome','info','Installation des dépéndances nodejs');
        $resource_path = realpath(dirname(__FILE__) . '/../../resources');
        passthru('/bin/bash ' . $resource_path . '/nodejs.sh ' . $resource_path . ' > ' . log::getPathToLog('xiaomihome_dep') . ' 2>&1 &');
    }

}

class xiaomihomeCmd extends cmd {

}
