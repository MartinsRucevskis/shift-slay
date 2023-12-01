# Shift-slay!

A Tool for automated framework shifts.


# Currently supported
Minimal Lumen 8 to Laravel 8 migration - migrates mainly config and currently only for specific projects with codeception tests. Requires to fix custom defined configs and other specific Lumen to Laravel changes



## Commands

Commands for launching migrations

|        Migration        |Command                        
|----------------|-------------------------------------|
|Lumen8 - Laravel8|`php artisan Shift:lumen8tolaravel8`         


## Setup

- Pull `shift-slay` locally
- Edit `.env` file
    -  Set `SHIFT_PROJECT_PATH` to project You want to upgrade
    - Set `COMPOSER_AUTOLOAD_PATH` to `autoload_classmap` location in project You want to upgrade
    - Set `PLAIN_LARAVEL_PATH` to plain laravel with version you will upgrade to ***To be changed, such that there isn't a need to provide this***
- Launch migration


## Important!
Currently, shift doesn't support backup files, if using any of the migrations, please be sure that there is a backup project (version control or locally).

For now to run migration again, it is required to manually reset project to original state.

[![Static analysis](https://github.com/MartinsRucevskis/shift-slay/actions/workflows/stan.yml/badge.svg)](https://github.com/MartinsRucevskis/shift-slay/actions/workflows/stan.yml)
[![Code style](https://github.com/MartinsRucevskis/shift-slay/actions/workflows/pint.yml/badge.svg)](https://github.com/MartinsRucevskis/shift-slay/actions/workflows/pint.yml)
[![Tests](https://github.com/MartinsRucevskis/shift-slay/actions/workflows/laravel.yml/badge.svg)](https://github.com/MartinsRucevskis/shift-slay/actions/workflows/laravel.yml)
