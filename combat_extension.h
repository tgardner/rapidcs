#include "combat.h"

#if defined DLL_EXPORTS
    #if defined WIN32
        #define LIB_API(RetType) extern "C" __declspec(dllexport) RetType
    #else
        #define LIB_API(RetType) extern "C" RetType __attribute__((visibility("default")))
    #endif
#else
    #if defined WIN32
        #define LIB_API(RetType) extern "C" __declspec(dllimport) RetType
    #else
        #define LIB_API(RetType) extern "C" RetType
    #endif
#endif

LIB_API(CombatSys*) CreateCombatSys();
LIB_API(void) DisposeCombatSys(CombatSys* combatSys);
LIB_API(void) SetUnitCount(CombatSys* combatSys, int count);
LIB_API(void) ConfigShip(CombatSys* combatSys, int id, long attack, long hull, long shield, char* rapidfire);
LIB_API(void) AddAttacker(CombatSys* combatSys, int id, int weapon, int shield, int armor);
LIB_API(void) AddDefender(CombatSys* combatSys, int id, int weapon, int shield, int armor);
LIB_API(void) AddShips(CombatSys* combatSys, const unsigned fraction, int playerId, int shipId, int amount);
LIB_API(void) Battle(CombatSys* combatSys);
LIB_API(char*) GetResult(CombatSys* combatSys);