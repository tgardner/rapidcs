cmake_minimum_required(VERSION 3.14)

add_compile_definitions(_CRT_SECURE_NO_WARNINGS)

project(RapidCS)

set(CMAKE_CXX_STANDARD 17)

add_compile_definitions(DLL_EXPORTS)

add_library(RapidCS SHARED combat_extension.cc combat_extension.h combat.cc combat.h speedrand.cc speedrand.h)
