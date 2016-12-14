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
    //Create a UDP socket
    if(!($socksrv = socket_create(AF_INET, SOCK_DGRAM, 0))) {
      $errorcode = socket_last_error();
      $errormsg = socket_strerror($errorcode);
      die(log::add('xiaomihome', 'error', 'Création du socket impossible ' . $errorcode . ' : ' . $errormsg));
    }
    if (!socket_set_option($socksrv, SOL_SOCKET, SO_REUSEADDR, 1)) {
      log::add('xiaomihome', 'error', 'Impossible d appliquer les options au socket : ' . socket_strerror($errorcode));
    }
    // Bind the source address
    if( !socket_bind($socksrv, "224.0.0.50" , 4321) ) {
      $errorcode = socket_last_error();
      $errormsg = socket_strerror($errorcode);
      die(log::add('xiaomihome', 'error', 'Connexion au socket impossible ' . $errorcode . ' : ' . $errormsg));
    }
    log::add('xiaomihome', 'debug', 'Daemon en écoute');
    //Do some communication, this loop can handle multiple clients
    while(1) {
      //Receive some data
      $r = socket_recvfrom($socksrv, $buf, 1024, 0, $remote_ip, $remote_port);
/*      $body = json_decode($buf, true);
      log::add('xiaomihome', 'debug', 'Recu ' . print_r($body, true));
      xiaomihome::receiveId(init('sid'), init('model'));
      foreach ($body as $key => $value) {
          xiaomihome::receiveData(init('sid'), $key, $value);
      }*/
      log::add('xiaomihome', 'debug', 'Recu : ' . $buf . ' de ' . $remote_ip);
    }
    socket_close($socksrv);
  }

}

class xiaomihomeCmd extends cmd {

}
