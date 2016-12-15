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
    public static function receiveId($sid, $model) {
        $xiaomihome = self::byLogicalId($sid, 'xiaomihome');
        if (!is_object($xiaomihome)) {
            $xiaomihome = new xiaomihome();
            $xiaomihome->setEqType_name('xiaomihome');
            $xiaomihome->setLogicalId($sid);
            $xiaomihome->setName($model . ' ' . $sid);
            $xiaomihome->setConfiguration('sid', $sid);
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
      $return['log'] = 'xiaomihome';
      $return['launchable'] = 'ok';
      $return['state'] = 'nok';
      $cron = cron::byClassAndFunction('xiaomihome', 'daemon');
      if (is_object($cron) && $cron->running()) {
          $return['state'] = 'ok';
      }
      return $return;
    }

    public static function deamon_start($_debug = false) {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$cron = cron::byClassAndFunction('xiaomihome', 'daemon');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron introuvable', __FILE__));
		}
		$cron->run();
	}

    public static function deamon_stop() {
		$cron = cron::byClassAndFunction('xiaomihome', 'daemon');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron introuvable', __FILE__));
		}
		$cron->halt();
	}

    public static function daemon() {
    $socket = stream_socket_server("udp://224.0.0.50:4321", $errno, $errstr, STREAM_SERVER_BIND);
    if (!$socket) {
        die("$errstr ($errno)");
    }

    do {
        $pkt = stream_socket_recvfrom($socket, 1, 0, $peer);
        log::add('xiaomihome', 'debug', 'Listen ' . $peer);
        //stream_socket_sendto($socket, date("D M j H:i:s Y\r\n"), 0, $peer);
    } while ($pkt !== false);
  }

}

class xiaomihomeCmd extends cmd {

}
