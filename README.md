# Shift-slay!

A Tool for automated framework shifts.


# Currently supported
## Lumen8 - Laravel 8
Migrates mainly config and currently only for specific projects with codeception tests. Requires to fix custom defined configs and other specific Lumen to Laravel changes
## Laravel 8 - Laravel 9
Updates with changes from Laravels documentation
## LAravel 9 - Laravel 10
Updates with changes from Laravels documentation, adds config file updates, Gelf and Monolog updates
## Codeception - Laravel Feature tests
Is specialized to replace phiremock. Shifts most of the tests, yet can break some stuff.
Requires that ApiTester is named as I.


## Commands

Commands for launching migrations

| Migration                           |Command                        
|-------------------------------------|-------------------------------------|
| Lumen8 - Laravel8                   |`php artisan shift:Lumen8tolaravel8`       
| Laravel8 - Laravel 9                | `php artisan shift:Laravel8ToLaravel9`
| Laravel 9 - Laravel 10              | `php artisan shift:Laravel9ToLaravel10`
| Codeception - Laravel Feature tests | ``


## Setup

- Pull `shift-slay` locally
- Edit `.env` file
    -  Set `SHIFT_PROJECT_PATH` to project You want to upgrade
  - Launch migration


## Important!
Currently, shift doesn't support backup files, if using any of the migrations, please be sure that there is a backup project (version control or locally).

For now to run migration again, it is required to manually reset project to original state.

[![Static analysis](https://github.com/MartinsRucevskis/shift-slay/actions/workflows/stan.yml/badge.svg)](https://github.com/MartinsRucevskis/shift-slay/actions/workflows/stan.yml)
[![Code style](https://github.com/MartinsRucevskis/shift-slay/actions/workflows/pint.yml/badge.svg)](https://github.com/MartinsRucevskis/shift-slay/actions/workflows/pint.yml)
[![Tests](https://github.com/MartinsRucevskis/shift-slay/actions/workflows/laravel.yml/badge.svg)](https://github.com/MartinsRucevskis/shift-slay/actions/workflows/laravel.yml)
