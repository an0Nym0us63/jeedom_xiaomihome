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

    public static function receiveId($sid, $short_id, $model) {
        $xiaomihome = self::byLogicalId($sid, 'xiaomihome');
        if (!is_object($xiaomihome)) {
            $xiaomihome = new xiaomihome();
            $xiaomihome->setEqType_name('xiaomihome');
            $xiaomihome->setLogicalId($sid);
            $xiaomihome->setName($model . ' ' . $short_id);
            $xiaomihome->setConfiguration('sid', $sid);
            $xiaomihome->setConfiguration('short_id',$short_id);
            $xiaomihome->setConfiguration('model',$model);
            $xiaomihome->save();
        }
    }

    public static function receiveData($sid, $key, $value) {
        $xiaomihome = self::byLogicalId($sid, 'xiaomihome');
        if (is_object($xiaomihome)) {
            $xiaomihomeCmd = xiaomihomeCmd::byEqLogicIdAndLogicalId($xiaomihome->getId(),$key);
            if (!is_object($xiaomihomeCmd)) {
                log::add('xiaomihome', 'debug', 'Création de la commande ' . $key);
                $xiaomihomeCmd = new xiaomihomeCmd();
                $xiaomihomeCmd->setName(__($key, __FILE__));
                $xiaomihomeCmd->setEqLogic_id($xiaomihome->id);
                $xiaomihomeCmd->setEqType('xiaomihome');
                $xiaomihomeCmd->setLogicalId($key);
                $xiaomihomeCmd->setType('info');
                $xiaomihomeCmd->setSubType('string');
                $xiaomihomeCmd->setTemplate("mobile",'line' );
                $xiaomihomeCmd->setTemplate("dashboard",'line' );
                $xiaomihomeCmd->save();
            }
            $xiaomihome->checkAndUpdateCmd($key, $value);
        }
    }

    public static function deamon_info() {
        $return = array();
        $return['log'] = 'xiaomihome_node';
        $return['state'] = 'nok';
        $pid = trim( shell_exec ('ps ax | grep "xiaomihome.py" | grep -v "grep" | wc -l') );
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
        $sensor_path = realpath(dirname(__FILE__) . '/../../resources');
        $cmd = 'nice -n 19 python ' . $sensor_path . '/xiaomihome.py ' . $url;

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
        exec('kill $(ps aux | grep "xiaomihome.py" | awk \'{print $2}\')');
        log::add('xiaomihome', 'info', 'Arrêt du service xiaomihome');
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok') {
            sleep(1);
            exec('kill -9 $(ps aux | grep "xiaomihome.py" | awk \'{print $2}\')');
        }
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok') {
            sleep(1);
            exec('sudo kill -9 $(ps aux | grep "xiaomihome.py" | awk \'{print $2}\')');
        }
    }

    public static function dependancy_info() {
        $return = array();
        $return['log'] = 'xiaomihome_dep';
        $cmd = "pip list | grep mihome";
        exec($cmd, $output, $return_var);
        $return['state'] = 'nok';
        if (array_key_exists(0,$output)) {
            if ($output[0] != "") {
                $return['state'] = 'ok';
            }
        }
        return $return;
    }

    public static function dependancy_install() {
        exec('sudo apt-get -y install python-pip libglib2.0-dev && sudo pip install mihome > ' . log::getPathToLog('xiaomihome_dep') . ' 2>&1 &');
    }

}

class xiaomihomeCmd extends cmd {

}
