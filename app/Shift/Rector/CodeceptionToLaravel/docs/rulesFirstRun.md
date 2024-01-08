# 16 Rules Overview

## AddResponseAsParam

Add Response parameter to method that needs it.

- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\AddResponseAsParam`](RulesFirstRun/AddResponseAsParam.php)

<br>

## AddResponseAsParamWhenCaller

Add Response argument when method has it as param

- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\AddResponseAsParamWhenCaller`](RulesFirstRun/AddResponseAsParamWhenCaller.php)

<br>

## AddTestAttributeForTests

Add test param to public methods in files that end with Cest.php

- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\AddTestAttributeForTests`](RulesFirstRun/AddTestAttributeForTests.php)

<br>

## ChainResponseCodes

Chain asserts to requests

- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\ChainResponseCodes`](RulesFirstRun/ChainResponseCodes.php)

<br>

## ExampleToTestWithDocs

Move from example to phpunit attributes

- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\ExampleToTestWithDocs`](RulesFirstRun/ExampleToTestWithDocs.php)

<br>

## RefactorDatabaseCalls


- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\RefactorDatabaseCalls`](RulesFirstRun/RefactorDatabaseCalls.php)

<br>

## RefactorGetResponse

Change access to response body

- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\RefactorGetResponse`](RulesFirstRun/RefactorGetResponse.php)

<br>

## RefactorGrabFromDatabase


- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\RefactorGrabFromDatabase`](RulesFirstRun/RefactorGrabFromDatabase.php)

<br>

## RefactorJsonFilesToArray

Covert Json files to array when sending requests

- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\RefactorJsonFilesToArray`](RulesFirstRun/RefactorJsonFilesToArray.php)

<br>

## RefactorMockAccess

Refactor made request retrieving from phiremock to HttpOutgoingRequestRetriever trait

- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\RefactorMockAccess`](RulesFirstRun/RefactorMockAccess.php)

<br>

## RefactorMockCreation

Refactor mock access from Phiremock to HttpStatelessMock trait

- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\RefactorMockCreation`](RulesFirstRun/RefactorMockCreation.php)

<br>

## RefactorToResponseMethods

Replace json path access to equivalent in Laravel feature tests

- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\RefactorToResponseMethods`](RulesFirstRun/RefactorToResponseMethods.php)

<br>

## RenameApiTesterMethod

Replace codeception methods to equivalents in Laravel Feature tests

- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\RenameApiTesterMethod`](RulesFirstRun/RenameApiTesterMethod.php)

<br>

## ReplaceApiTesterForOutsideMethodCalls

Replace Codeception Tester passing to other classes with `$this`

- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\ReplaceApiTesterForOutsideMethodCalls`](RulesFirstRun/ReplaceApiTesterForOutsideMethodCalls.php)

<br>

## ResponseCodesToAsserts

Improve asserts, by changing status code asserts to inbuilt methods

- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\ResponseCodesToAsserts`](RulesFirstRun/ResponseCodesToAsserts.php)

<br>

## StaticStatusCodeToInt

Replace Codeception HttpCode with int

- class: [`App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\StaticStatusCodeToInt`](RulesFirstRun/StaticStatusCodeToInt.php)

<br>
