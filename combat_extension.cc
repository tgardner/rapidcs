#include "combat_extension.h"

CombatSys* CreateCombatSys()
{
    return new CombatSys();
}

void DisposeCombatSys(CombatSys* combatSys)
{
    if(combatSys != 0)
    {
        delete combatSys;
        combatSys= 0;
    }
}

char* GetResult(CombatSys* combatSys) {
    if(combatSys != 0)
    {
        return combatSys->getResult();
    }
    return nullptr;
}

void SetUnitCount(CombatSys* combatSys, int count) {
    if(combatSys != 0)
    {
        combatSys->setUnitCount(count);
    }
}

void ConfigShip(CombatSys* combatSys, int id, long attack, long hull, long shield, char* rapidfire) {
    if(combatSys != 0)
    {
        combatSys->configShip(id, attack, hull, shield, rapidfire);
    }
}

void AddAttacker(CombatSys* combatSys, int id, int weapon, int shield, int armor) {
    if(combatSys != 0) {
        combatSys->addAttacker(id, weapon, shield, armor);
    }
}

void AddDefender(CombatSys* combatSys, int id, int weapon, int shield, int armor) {
    if(combatSys != 0) {
        combatSys->addDefender(id, weapon, shield, armor);
    }
}

void AddShips(CombatSys* combatSys, const unsigned fraction, int playerId, int shipId, int amount) {
    if(combatSys != 0) {
        combatSys->addShips(fraction, playerId, shipId, amount);
    }
}

void Battle(CombatSys* combatSys) {
    if(combatSys != 0) {
        combatSys->battle();
    }
}