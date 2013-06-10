<?php

/**
 *  2Moons
 *  Copyright (C) 2012 Jan Kröpke
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package 2Moons
 * @author Jan Kröpke <info@2moons.cc>
 * @copyright 2012 Jan Kröpke <info@2moons.cc>
 * @license http://www.gnu.org/licenses/gpl.html GNU GPLv3 License
 * @version 1.7.2 (2013-03-18)
 * @info $Id: calculateAttack.php 2632 2013-03-18 19:05:14Z slaver7 $
 * @link http://2moons.cc/
 */

function calculateAttack(&$attackers, &$defenders, $FleetTF, $DefTF)
{
    global $pricelist, $CombatCaps, $resource;
    
    
    if(!class_exists("CombatSys"))
        return calculateAttack2Moons($attackers, $defenders, $FleetTF, $DefTF);
    //return calculateAttack2Moons($attackers, $defenders, $FleetTF, $DefTF);
    
    $laShipConfig = array();    //the special config for CombatSys
    $laIgmId2CsId = array();    //IngameID -> CombatSysID
    $laCsId2IgmId = array();    //and back to Igm
    $liCsId = 0;
    
    //basic config
    foreach($CombatCaps as $liUnitId => $laUnitData)
    {
        if($liUnitId > 200 && $liUnitId < 500 && isset($laUnitData["attack"]))
        {
            $laCsId2IgmId[$liCsId] = $liUnitId;
            $laIgmId2CsId[$liUnitId] = $liCsId;
            
            
            $laShipConfig[$liCsId] = array(
                "a" => $laUnitData["attack"],
                "h" => ($pricelist[$liUnitId]["cost"][901] + $pricelist[$liUnitId]["cost"][902]) / 10,
                "s" => $laUnitData["shield"]                
            );
            $liCsId++;
        }
    }
    
    //rapidfire
    foreach($laCsId2IgmId as $liCsId => $liUnitId)
    {
        $lsRapidFire = "";
        if(isset($CombatCaps[$liUnitId]["sd"]) && count($CombatCaps[$liUnitId]["sd"]) > 0)
        {
            ksort($CombatCaps[$liUnitId]["sd"]);
            foreach($CombatCaps[$liUnitId]["sd"] as $liFromId => $liRFAmount)
                $lsRapidFire .= $laIgmId2CsId[$liFromId].":".$liRFAmount.";";
        }
        $laShipConfig[$liCsId]["rf"] = $lsRapidFire;
    }
    
    //configure the CombatSystem
    $lcsInstance = new CombatSys();
    
    
    $lcsInstance->setShootAgainstExploded(true);
    $lcsInstance->setUnitCount(count($laShipConfig));
    
    foreach($laShipConfig as $liId => $laData)
        $lcsInstance->configShip($liId,$laData["a"],$laData["h"],$laData["s"],$laData["rf"]);
    
    
    $laIgnPlayer2CsPlayerA = array();
    $laCsPlayer2IgmPlayerA = array();
    $liPlayerId = 0;
    //Setting the Attackers
    
    $laAttackerConfig = array();
    foreach($attackers as $liId => $laPlayerData)
    {
        $laCsPlayer2IgmPlayerA[$liPlayerId] = $liId;
        $laIgnPlayer2CsPlayerA[$liId] = $liPlayerId;
        $laAttackerConfig[$liId] = array(
            "id" => $liPlayerId,
            "weapon" => $laPlayerData["player"]["military_tech"],
            "shield" => $laPlayerData["player"]["defence_tech"],
            "armor" => $laPlayerData["player"]["shield_tech"],
        );
        $liPlayerId++;
    }
    
    $laIgnPlayer2CsPlayerD = array();
    $laCsPlayer2IgmPlayerD = array();
    $liPlayerId = 0;
    
    //setting the defenders
    $laDefenderConfig = array();
    foreach($defenders as $liId => $laPlayerData)
    {
        $laCsPlayer2IgmPlayerD[$liPlayerId] = $liId;
        $laIgnPlayer2CsPlayerD[$liId] = $liPlayerId;
        $laDefenderConfig[$liId] = array(
            "id" => $liPlayerId,
            "weapon" => $laPlayerData["player"]["military_tech"],
            "shield" => $laPlayerData["player"]["defence_tech"],
            "armor" => $laPlayerData["player"]["shield_tech"],
        );
        $liPlayerId++;
    }
    
    //telling this the combatsystem
    foreach($laAttackerConfig as $laConfig)
        $lcsInstance->addAttacker($laConfig["id"],$laConfig["weapon"],$laConfig["shield"],$laConfig["armor"]);
        
    foreach($laDefenderConfig as $laConfig)
        $lcsInstance->addDefender($laConfig["id"],$laConfig["weapon"],$laConfig["shield"],$laConfig["armor"]);
    
    
    
    
    //config the ships
    
    $laAttackShips = array();
    foreach($attackers as $liPlayerId  => $laData)
    {
        $laAttackShips[$laIgnPlayer2CsPlayerA[$liPlayerId]] = array();
        foreach($laData["unit"] as $liUnitId => $liAmount)
            $laAttackShips[$laIgnPlayer2CsPlayerA[$liPlayerId]][$laIgmId2CsId[$liUnitId]] = $liAmount;
    }
    
    $laDefendShips = array();
    foreach($defenders as $liPlayerId  => $laData)
    {
        $laDefendShips[$laIgnPlayer2CsPlayerD[$liPlayerId]] = array();
        foreach($laData["unit"] as $liUnitId => $liAmount)
        {
            $laDefendShips[$laIgnPlayer2CsPlayerD[$liPlayerId]][$laIgmId2CsId[$liUnitId]] = $liAmount;
        }
    }
    
    //telling this the combatsystem
    foreach($laAttackShips as $liId => $laShips)
    {
        foreach($laShips as $liSID => $liAmount)
            $lcsInstance->addAttackShips($liId,$liSID,$liAmount);
    }
    
    foreach($laDefendShips as $liId => $laShips)
    {
        foreach($laShips as $liSID => $liAmount)
            $lcsInstance->addDefendShips($liId,$liSID,$liAmount);
    }
    
    /*
    * Now the combatsystem is ready to battle. Please note, that all further methods WON'T work, if the methods above weren't called in exactly that order.
    * If you are going to change or to reproduce the communication to the CombatSys class, you might risk fatal php errors and even crashes.
    * 
    * I also know, it would be better for perfomance, to let my foreachs above do the work, wich will be done in the next procedures. This will save a
    * few of microseconds during runtime. I'v chosen to save the developers chance for better source understanding instead.
    *
    * Well, let us proceed the 2Moons way of calculation
    */
    
    $TRES     = array('attacker' => 0, 'defender' => 0);
	$ARES 	= $DRES = array('metal' => 0, 'crystal' => 0);
	$ROUND	= array();
	$RF		= array();
	
	foreach ($attackers as $fleetID => $attacker) 
	{
		foreach ($attacker['unit'] as $element => $amount) 
		{
			$ARES['metal'] 		+= $pricelist[$element]['cost'][901] * $amount;
			$ARES['crystal'] 	+= $pricelist[$element]['cost'][902] * $amount;
		}
	}

	foreach($CombatCaps as $e => $arr) {
		if(!isset($arr['sd'])) continue;
		
		foreach($arr['sd'] as $t => $sd) {
			if($sd == 0) continue;
			$RF[$t][$e] = $sd;
		}
	}
	
	$TRES['attacker']	= $ARES['metal'] + $ARES['crystal'];

	foreach ($defenders as $fleetID => $defender) 
	{
		foreach ($defender['unit'] as $element => $amount)
		{
			if ($element < 300) {
				$DRES['metal'] 		+= $pricelist[$element]['cost'][901] * $amount;
				$DRES['crystal'] 	+= $pricelist[$element]['cost'][902] * $amount ;

				$TRES['defender'] 	+= $pricelist[$element]['cost'][901] * $amount;
				$TRES['defender'] 	+= $pricelist[$element]['cost'][902] * $amount;
			} else {
				if (!isset($STARTDEF[$element])) 
					$STARTDEF[$element] = 0;
				
				$STARTDEF[$element] += $amount;

				$TRES['defender']	+= $pricelist[$element]['cost'][901] * $amount;
				$TRES['defender']	+= $pricelist[$element]['cost'][902] * $amount;
			}
		}
	}
    
    //STOP! And instead of the 2Moon calculation, let CombatSys do that work...
    $lcsInstance->battle(); //For all the speed x10000 fans...565.768.131.655.133 ships in a battle (and even a few less...) will KILL your maschine!!!
                            //But the hope dies last, if the cousin of my wife manages it find a propability-theoretical solution, even this will be possible.
                            //Note: if the comment 1 row past comes true, it will f*ck up Gameforge! Did you know that they have losen their source code of the 
                            //combatsystem, and only open new servers with the compiled version they still have? Now you know it ;)
                            
    $lsResult = $lcsInstance->getResult(); //this comes in json-format
    //echo("<pre>".$lsResult);
    $laResult = (array)json_decode($lsResult,true);
    
    
    if(isset($laResult["error"]))
        die("something went terribly wrong, check the CombatSys-documentation for more information");
    
    $laRounds = $laResult["rounds"];
    
    //now comes the modification //the CS does only know 0 - 5, we don't want any errors!
    for ($ROUNDC = 0; $ROUNDC <= 6; $ROUNDC++) 
    {
		$attackDamage  = array('total' => 0);
		$attackShield  = array('total' => 0);
		$attackAmount  = array('total' => 0);
		$defenseDamage = array('total' => 0);
		$defenseShield = array('total' => 0);
		$defenseAmount = array('total' => 0);
		$attArray = array();
		$defArray = array();

		foreach ($attackers as $fleetID => $attacker) {
			$attackDamage[$fleetID] = 0;
			$attackShield[$fleetID] = 0;
			$attackAmount[$fleetID] = 0;

			$attTech	= (1 + (0.1 * $attacker['player']['military_tech']) + $attacker['player']['factor']['Attack']); //attaque
			$defTech	= (1 + (0.1 * $attacker['player']['defence_tech']) + $attacker['player']['factor']['Defensive']); //bouclier
			$shieldTech = (1 + (0.1 * $attacker['player']['shield_tech']) + $attacker['player']['factor']['Shield']); //coque
			$attackers[$fleetID]['techs'] = array($attTech, $defTech, $shieldTech);
				
			foreach ($attacker['unit'] as $element => $amount) {
                //removed the amount and the rand, because the real ogame combat gives enough randomization
                
				$thisAtt	= ($CombatCaps[$element]['attack']) * $attTech; 
				$thisDef	= ($CombatCaps[$element]['shield']) * $defTech ;
				$thisShield	= ($pricelist[$element]['cost'][901] + $pricelist[$element]['cost'][902]) / 10 * $shieldTech;

				$attArray[$fleetID][$element] = array('def' => $thisDef, 'shield' => $thisShield, 'att' => $thisAtt);

				$attackDamage[$fleetID] += $thisAtt;
				$attackDamage['total'] += $thisAtt;
				$attackShield[$fleetID] += $thisDef;
				$attackShield['total'] += $thisDef;
				$attackAmount[$fleetID] += $amount;
				$attackAmount['total'] += $amount;
			}
		}

		foreach ($defenders as $fleetID => $defender) {
			$defenseDamage[$fleetID] = 0;
			$defenseShield[$fleetID] = 0;
			$defenseAmount[$fleetID] = 0;

			$attTech	= (1 + (0.1 * $defender['player']['military_tech']) + $defender['player']['factor']['Attack']); //attaquue
			$defTech	= (1 + (0.1 * $defender['player']['defence_tech']) + $defender['player']['factor']['Defensive']); //bouclier
			$shieldTech = (1 + (0.1 * $defender['player']['shield_tech']) + $defender['player']['factor']['Shield']); //coque
			$defenders[$fleetID]['techs'] = array($attTech, $defTech, $shieldTech);

    
			foreach ($defender['unit'] as $element => $amount) {
                //echo "$ROUNDC $element $amount ".PHP_EOL;
                //same here
				$thisAtt	= ($CombatCaps[$element]['attack']) * $attTech; //attaque
				$thisDef	= ($CombatCaps[$element]['shield']) * $defTech ; //bouclier
				$thisShield	= ($pricelist[$element]['cost'][901] + $pricelist[$element]['cost'][902]) / 10 * $shieldTech; //coque

				if ($element == 407 || $element == 408 || $element == 409) $thisAtt = 0;

				$defArray[$fleetID][$element] = array('def' => $thisDef, 'shield' => $thisShield, 'att' => $thisAtt);

				$defenseDamage[$fleetID] += $thisAtt;
				$defenseDamage['total'] += $thisAtt;
				$defenseShield[$fleetID] += $thisDef;
				$defenseShield['total'] += $thisDef;
				$defenseAmount[$fleetID] += $amount;
				$defenseAmount['total'] += $amount;
			}
		}

		$ROUND[$ROUNDC] = array('attackers' => $attackers, 'defenders' => $defenders, 'attackA' => $attackAmount, 'defenseA' => $defenseAmount, 'infoA' => $attArray, 'infoD' => $defArray);

		if ($ROUNDC >= MAX_ATTACK_ROUNDS || $defenseAmount['total'] <= 0 || $attackAmount['total'] <= 0) {
			break;
		}

        //and now just fetch the data from that round
		
        $attacker_n = array();
        foreach($laRounds[$ROUNDC]["aunits"] as $liPlayerId => $laUnits)
        {
            $attacker_n[$laCsPlayer2IgmPlayerA[$liPlayerId]] = array();
            foreach($laUnits as $liCsId => $liAmount)
                $attacker_n[$laCsPlayer2IgmPlayerA[$liPlayerId]][$laCsId2IgmId[$liCsId]] = $liAmount;
        }
        
        $defender_n = array();
        foreach($defenders as $liPlayerId => $laUnits)
        {
            $defender_n[$liPlayerId] = array();
            foreach($laUnits["unit"] as $liIgmId => $liAmount)
                if(!isset($laRounds[$ROUNDC]["dunits"][$laIgnPlayer2CsPlayerD[$liPlayerId]][$laIgmId2CsId[$liIgmId]]))
                    $defender_n[$liPlayerId][$liIgmId] = 0;
                else
                    $defender_n[$liPlayerId][$liIgmId] = $laRounds[$ROUNDC]["dunits"][$laIgnPlayer2CsPlayerD[$liPlayerId]][$laIgmId2CsId[$liIgmId]];
        }
        /*
        foreach($laRounds[$ROUNDC]["dunits"] as $liPlayerId => $laUnits)
        {
            $defender_n[$laCsPlayer2IgmPlayerD[$liPlayerId]] = array();
            foreach($laUnits as $liCsId => $liAmount)
                $defender_n[$laCsPlayer2IgmPlayerD[$liPlayerId]][$laCsId2IgmId[$liCsId]] = $liAmount;
        }
        */
		
		$ROUND[$ROUNDC]['attack'] 		= $laRounds[$ROUNDC]["adamage"];
		$ROUND[$ROUNDC]['defense'] 		= $laRounds[$ROUNDC]["ddamage"];
		$ROUND[$ROUNDC]['attackShield'] = $laRounds[$ROUNDC]["aabsorbed"];
		$ROUND[$ROUNDC]['defShield'] 	= $laRounds[$ROUNDC]["dabsorbed"];
        //And we got 2 new infos
        $ROUND[$ROUNDC]['attackShoots'] = $laRounds[$ROUNDC]["ashot"];
    	$ROUND[$ROUNDC]['defShoots'] 	= $laRounds[$ROUNDC]["dshot"];
        
        
		foreach ($attackers as $fleetID => $attacker) {
			$attackers[$fleetID]['unit'] = array_map('round', $attacker_n[$fleetID]);
		}

		foreach ($defenders as $fleetID => $defender) {
			$defenders[$fleetID]['unit'] = array_map('round', $defender_n[$fleetID]);
		}
	}
    
    //And now, like before, proceed with default 2moons stuff
    
    if ($attackAmount['total'] <= 0 && $defenseAmount['total'] > 0) {
    	$won = "r"; // defender
	} elseif ($attackAmount['total'] > 0 && $defenseAmount['total'] <= 0) {
		$won = "a"; // attacker
	} else {
		$won = "w"; // draw
	}

	// CDR
	foreach ($attackers as $fleetID => $attacker) {					   // flotte attaquant en CDR
		foreach ($attacker['unit'] as $element => $amount) {
			$TRES['attacker'] -= $pricelist[$element]['cost'][901] * $amount ;
			$TRES['attacker'] -= $pricelist[$element]['cost'][902] * $amount ;

			$ARES['metal'] -= $pricelist[$element]['cost'][901] * $amount ;
			$ARES['crystal'] -= $pricelist[$element]['cost'][902] * $amount ;
		}
	}

	$DRESDefs = array('metal' => 0, 'crystal' => 0);

	foreach ($defenders as $fleetID => $defender) {
		foreach ($defender['unit'] as $element => $amount) {
			if ($element < 300) {							// flotte defenseur en CDR
				$DRES['metal'] 	 -= $pricelist[$element]['cost'][901] * $amount ;
				$DRES['crystal'] -= $pricelist[$element]['cost'][902] * $amount ;

				$TRES['defender'] -= $pricelist[$element]['cost'][901] * $amount ;
				$TRES['defender'] -= $pricelist[$element]['cost'][902] * $amount ;
			} else {									// defs defenseur en CDR + reconstruction
				$TRES['defender'] -= $pricelist[$element]['cost'][901] * $amount ;
				$TRES['defender'] -= $pricelist[$element]['cost'][902] * $amount ;

				$lost = $STARTDEF[$element] - $amount;
				$giveback = round($lost * (rand(56, 84) / 100));
				$defenders[$fleetID]['unit'][$element] += $giveback;
				$DRESDefs['metal'] 	 += $pricelist[$element]['cost'][901] * ($lost - $giveback) ;
				$DRESDefs['crystal'] += $pricelist[$element]['cost'][902] * ($lost - $giveback) ;
			}
		}
	}
	
	$ARES['metal']		= max($ARES['metal'], 0);
	$ARES['crystal']	= max($ARES['crystal'], 0);
	$DRES['metal']		= max($DRES['metal'], 0);
	$DRES['crystal']	= max($DRES['crystal'], 0);
	$TRES['attacker']	= max($TRES['attacker'], 0);
	$TRES['defender']	= max($TRES['defender'], 0);
	
	$totalLost = array('attacker' => $TRES['attacker'], 'defender' => $TRES['defender']);
	$debAttMet = ($ARES['metal'] * ($FleetTF / 100));
	$debAttCry = ($ARES['crystal'] * ($FleetTF / 100));
	$debDefMet = ($DRES['metal'] * ($FleetTF / 100)) + ($DRESDefs['metal'] * ($DefTF / 100));
	$debDefCry = ($DRES['crystal'] * ($FleetTF / 100)) + ($DRESDefs['crystal'] * ($DefTF / 100));

    //var_export($defenders);
    //var_export($laRounds);
	return array('won' => $won, 'debris' => array('attacker' => array(901 => $debAttMet, 902 => $debAttCry), 'defender' => array(901 => $debDefMet, 902 => $debDefCry)), 'rw' => $ROUND, 'unitLost' => $totalLost);
}

function calculateAttack2Moons(&$attackers, &$defenders, $FleetTF, $DefTF)
{
	global $pricelist, $CombatCaps, $resource;

	$TRES 	= array('attacker' => 0, 'defender' => 0);
	$ARES 	= $DRES = array('metal' => 0, 'crystal' => 0);
	$ROUND	= array();
	$RF		= array();
	
	foreach ($attackers as $fleetID => $attacker) 
	{
		foreach ($attacker['unit'] as $element => $amount) 
		{
			$ARES['metal'] 		+= $pricelist[$element]['cost'][901] * $amount;
			$ARES['crystal'] 	+= $pricelist[$element]['cost'][902] * $amount;
		}
	}

	foreach($CombatCaps as $e => $arr) {
		if(!isset($arr['sd'])) continue;
		
		foreach($arr['sd'] as $t => $sd) {
			if($sd == 0) continue;
			$RF[$t][$e] = $sd;
		}
	}
	
	$TRES['attacker']	= $ARES['metal'] + $ARES['crystal'];

	foreach ($defenders as $fleetID => $defender) 
	{
		foreach ($defender['unit'] as $element => $amount)
		{
			if ($element < 300) {
				$DRES['metal'] 		+= $pricelist[$element]['cost'][901] * $amount;
				$DRES['crystal'] 	+= $pricelist[$element]['cost'][902] * $amount ;

				$TRES['defender'] 	+= $pricelist[$element]['cost'][901] * $amount;
				$TRES['defender'] 	+= $pricelist[$element]['cost'][902] * $amount;
			} else {
				if (!isset($STARTDEF[$element])) 
					$STARTDEF[$element] = 0;
				
				$STARTDEF[$element] += $amount;

				$TRES['defender']	+= $pricelist[$element]['cost'][901] * $amount;
				$TRES['defender']	+= $pricelist[$element]['cost'][902] * $amount;
			}
		}
	}
	
	for ($ROUNDC = 0; $ROUNDC <= MAX_ATTACK_ROUNDS; $ROUNDC++) 
	{
		$attackDamage  = array('total' => 0);
		$attackShield  = array('total' => 0);
		$attackAmount  = array('total' => 0);
		$defenseDamage = array('total' => 0);
		$defenseShield = array('total' => 0);
		$defenseAmount = array('total' => 0);
		$attArray = array();
		$defArray = array();

		foreach ($attackers as $fleetID => $attacker) {
			$attackDamage[$fleetID] = 0;
			$attackShield[$fleetID] = 0;
			$attackAmount[$fleetID] = 0;

			$attTech	= (1 + (0.1 * $attacker['player']['military_tech']) + $attacker['player']['factor']['Attack']); //attaque
			$defTech	= (1 + (0.1 * $attacker['player']['defence_tech']) + $attacker['player']['factor']['Defensive']); //bouclier
			$shieldTech = (1 + (0.1 * $attacker['player']['shield_tech']) + $attacker['player']['factor']['Shield']); //coque
			$attackers[$fleetID]['techs'] = array($attTech, $defTech, $shieldTech);
				
			foreach ($attacker['unit'] as $element => $amount) {
				$thisAtt	= $amount * ($CombatCaps[$element]['attack']) * $attTech * (rand(80, 120) / 100); //attaque
				$thisDef	= $amount * ($CombatCaps[$element]['shield']) * $defTech ; //bouclier
				$thisShield	= $amount * ($pricelist[$element]['cost'][901] + $pricelist[$element]['cost'][902]) / 10 * $shieldTech; //coque

				$attArray[$fleetID][$element] = array('def' => $thisDef, 'shield' => $thisShield, 'att' => $thisAtt);

				$attackDamage[$fleetID] += $thisAtt;
				$attackDamage['total'] += $thisAtt;
				$attackShield[$fleetID] += $thisDef;
				$attackShield['total'] += $thisDef;
				$attackAmount[$fleetID] += $amount;
				$attackAmount['total'] += $amount;
			}
		}

		foreach ($defenders as $fleetID => $defender) {
			$defenseDamage[$fleetID] = 0;
			$defenseShield[$fleetID] = 0;
			$defenseAmount[$fleetID] = 0;

			$attTech	= (1 + (0.1 * $defender['player']['military_tech']) + $defender['player']['factor']['Attack']); //attaquue
			$defTech	= (1 + (0.1 * $defender['player']['defence_tech']) + $defender['player']['factor']['Defensive']); //bouclier
			$shieldTech = (1 + (0.1 * $defender['player']['shield_tech']) + $defender['player']['factor']['Shield']); //coque
			$defenders[$fleetID]['techs'] = array($attTech, $defTech, $shieldTech);

			foreach ($defender['unit'] as $element => $amount) {
				$thisAtt	= $amount * ($CombatCaps[$element]['attack']) * $attTech * (rand(80, 120) / 100); //attaque
				$thisDef	= $amount * ($CombatCaps[$element]['shield']) * $defTech ; //bouclier
				$thisShield	= $amount * ($pricelist[$element]['cost'][901] + $pricelist[$element]['cost'][902]) / 10 * $shieldTech; //coque

				if ($element == 407 || $element == 408 || $element == 409) $thisAtt = 0;

				$defArray[$fleetID][$element] = array('def' => $thisDef, 'shield' => $thisShield, 'att' => $thisAtt);

				$defenseDamage[$fleetID] += $thisAtt;
				$defenseDamage['total'] += $thisAtt;
				$defenseShield[$fleetID] += $thisDef;
				$defenseShield['total'] += $thisDef;
				$defenseAmount[$fleetID] += $amount;
				$defenseAmount['total'] += $amount;
			}
		}

		$ROUND[$ROUNDC] = array('attackers' => $attackers, 'defenders' => $defenders, 'attackA' => $attackAmount, 'defenseA' => $defenseAmount, 'infoA' => $attArray, 'infoD' => $defArray);

		if ($ROUNDC >= MAX_ATTACK_ROUNDS || $defenseAmount['total'] <= 0 || $attackAmount['total'] <= 0) {
			break;
		}

		// Calculate hit percentages (ACS only but ok)
		$attackPct = array();
		foreach ($attackAmount as $fleetID => $amount) {
			if (!is_numeric($fleetID)) continue;
				$attackPct[$fleetID] = $amount / $attackAmount['total'];
		}

		$defensePct = array();
		foreach ($defenseAmount as $fleetID => $amount) {
			if (!is_numeric($fleetID)) continue;
				$defensePct[$fleetID] = $amount / $defenseAmount['total'];
		}

		// CALCUL DES PERTES !!!
		$attacker_n = array();
		$attacker_shield = 0;
		$defenderAttack	= 0;
		foreach ($attackers as $fleetID => $attacker) {
			$attacker_n[$fleetID] = array();

			foreach($attacker['unit'] as $element => $amount) {
				if ($amount <= 0) {
					$attacker_n[$fleetID][$element] = 0;
					continue;
				}

				$defender_moc = $amount * ($defenseDamage['total'] * $attackPct[$fleetID]) / $attackAmount[$fleetID];
			
				if(isset($RF[$element])) {
					foreach($RF[$element] as $shooter => $shots) {
						foreach($defArray as $fID => $rfdef) {
							if(empty($rfdef[$shooter]['att']) || $attackAmount[$fleetID] <= 0) continue;

							$defender_moc += $rfdef[$shooter]['att'] * $shots / ($amount / $attackAmount[$fleetID] * $attackPct[$fleetID]);
							$defenseAmount['total'] += $defenders[$fID]['unit'][$shooter] * $shots;
						}
					}
				}
				
				$defenderAttack	+= $defender_moc;
				
				if (($attArray[$fleetID][$element]['def'] / $amount) >= $defender_moc) {
					$attacker_n[$fleetID][$element] = round($amount);
					$attacker_shield += $defender_moc;
					continue;
				}

				$max_removePoints = floor($amount * $defenseAmount['total'] / $attackAmount[$fleetID] * $attackPct[$fleetID]);

				$attacker_shield += min($attArray[$fleetID][$element]['def'], $defender_moc);
				$defender_moc 	 -= min($attArray[$fleetID][$element]['def'], $defender_moc);

				$ile_removePoints = max(min($max_removePoints, $amount * min($defender_moc / $attArray[$fleetID][$element]['shield'] * (rand(0, 200) / 100), 1)), 0);

				$attacker_n[$fleetID][$element] = max(ceil($amount - $ile_removePoints), 0);
			}
		}

		$defender_n = array();
		$defender_shield = 0;
		$attackerAttack	= 0;
		foreach ($defenders as $fleetID => $defender) {
			$defender_n[$fleetID] = array();

			foreach($defender['unit'] as $element => $amount) {
				if ($amount <= 0) {
					$defender_n[$fleetID][$element] = 0;
					continue;
				}

				$attacker_moc = $amount * ($attackDamage['total'] * $defensePct[$fleetID]) / $defenseAmount[$fleetID];
				if (isset($RF[$element])) {
					foreach($RF[$element] as $shooter => $shots) {
						foreach($attArray as $fID => $rfatt) {
							if (empty($rfatt[$shooter]['att']) || $defenseAmount[$fleetID] <= 0 ) continue;

							$attacker_moc += $rfatt[$shooter]['att'] * $shots / ($amount / $defenseAmount[$fleetID] * $defensePct[$fleetID]);
							$attackAmount['total'] += $attackers[$fID]['unit'][$shooter] * $shots;
						}
					}
				}
				
				$attackerAttack	+= $attacker_moc;
				
				if (($defArray[$fleetID][$element]['def'] / $amount) >= $attacker_moc) {
					$defender_n[$fleetID][$element] = round($amount);
					$defender_shield += $attacker_moc;
					continue;
				}
	
				$max_removePoints = floor($amount * $attackAmount['total'] / $defenseAmount[$fleetID] * $defensePct[$fleetID]);
				$defender_shield += min($defArray[$fleetID][$element]['def'], $attacker_moc);
				$attacker_moc 	 -= min($defArray[$fleetID][$element]['def'], $attacker_moc);
				
				$ile_removePoints = max(min($max_removePoints, $amount * min($attacker_moc / $defArray[$fleetID][$element]['shield'] * (rand(0, 200) / 100), 1)), 0);

				$defender_n[$fleetID][$element] = max(ceil($amount - $ile_removePoints), 0);
			}
		}
		
		$ROUND[$ROUNDC]['attack'] 		= $attackerAttack;
		$ROUND[$ROUNDC]['defense'] 		= $defenderAttack;
		$ROUND[$ROUNDC]['attackShield'] = $attacker_shield;
		$ROUND[$ROUNDC]['defShield'] 	= $defender_shield;
		foreach ($attackers as $fleetID => $attacker) {
			$attackers[$fleetID]['unit'] = array_map('round', $attacker_n[$fleetID]);
		}

		foreach ($defenders as $fleetID => $defender) {
			$defenders[$fleetID]['unit'] = array_map('round', $defender_n[$fleetID]);
		}
	}
	
	if ($attackAmount['total'] <= 0 && $defenseAmount['total'] > 0) {
		$won = "r"; // defender
	} elseif ($attackAmount['total'] > 0 && $defenseAmount['total'] <= 0) {
		$won = "a"; // attacker
	} else {
		$won = "w"; // draw
	}

	// CDR
	foreach ($attackers as $fleetID => $attacker) {					   // flotte attaquant en CDR
		foreach ($attacker['unit'] as $element => $amount) {
			$TRES['attacker'] -= $pricelist[$element]['cost'][901] * $amount ;
			$TRES['attacker'] -= $pricelist[$element]['cost'][902] * $amount ;

			$ARES['metal'] -= $pricelist[$element]['cost'][901] * $amount ;
			$ARES['crystal'] -= $pricelist[$element]['cost'][902] * $amount ;
		}
	}

	$DRESDefs = array('metal' => 0, 'crystal' => 0);

	foreach ($defenders as $fleetID => $defender) {
		foreach ($defender['unit'] as $element => $amount) {
			if ($element < 300) {							// flotte defenseur en CDR
				$DRES['metal'] 	 -= $pricelist[$element]['cost'][901] * $amount ;
				$DRES['crystal'] -= $pricelist[$element]['cost'][902] * $amount ;

				$TRES['defender'] -= $pricelist[$element]['cost'][901] * $amount ;
				$TRES['defender'] -= $pricelist[$element]['cost'][902] * $amount ;
			} else {									// defs defenseur en CDR + reconstruction
				$TRES['defender'] -= $pricelist[$element]['cost'][901] * $amount ;
				$TRES['defender'] -= $pricelist[$element]['cost'][902] * $amount ;

				$lost = $STARTDEF[$element] - $amount;
				$giveback = round($lost * (rand(56, 84) / 100));
				$defenders[$fleetID]['unit'][$element] += $giveback;
				$DRESDefs['metal'] 	 += $pricelist[$element]['cost'][901] * ($lost - $giveback) ;
				$DRESDefs['crystal'] += $pricelist[$element]['cost'][902] * ($lost - $giveback) ;
			}
		}
	}
	
	$ARES['metal']		= max($ARES['metal'], 0);
	$ARES['crystal']	= max($ARES['crystal'], 0);
	$DRES['metal']		= max($DRES['metal'], 0);
	$DRES['crystal']	= max($DRES['crystal'], 0);
	$TRES['attacker']	= max($TRES['attacker'], 0);
	$TRES['defender']	= max($TRES['defender'], 0);
	
	$totalLost = array('attacker' => $TRES['attacker'], 'defender' => $TRES['defender']);
	$debAttMet = ($ARES['metal'] * ($FleetTF / 100));
	$debAttCry = ($ARES['crystal'] * ($FleetTF / 100));
	$debDefMet = ($DRES['metal'] * ($FleetTF / 100)) + ($DRESDefs['metal'] * ($DefTF / 100));
	$debDefCry = ($DRES['crystal'] * ($FleetTF / 100)) + ($DRESDefs['crystal'] * ($DefTF / 100));

	return array('won' => $won, 'debris' => array('attacker' => array(901 => $debAttMet, 902 => $debAttCry), 'defender' => array(901 => $debDefMet, 902 => $debDefCry)), 'rw' => $ROUND, 'unitLost' => $totalLost);
}