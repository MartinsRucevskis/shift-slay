<?php

namespace App\Shift\Enums;

enum TypeEnums
{
    public const SIGNATURE_CHANGE = 'method_signature_change';
    public const CHAIN_EXT = 'method_chain_extension';
    public const REF_CHANGE = 'class_const_ref_change';
}
