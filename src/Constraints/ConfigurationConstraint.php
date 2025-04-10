<?php

namespace App\Constraints;

final class ConfigurationConstraint
{
    public static array $AVAILABLE_MANDATORY_PC_COMPONENTS = ['cpu', 'motherboard', 'gpu', 'ram', 'storage', 'psu'];
    public static array $AVAILABLE_MANDATORY_PC_COMPONENTS_IDS = ['cpu_id', 'motherboard_id', 'gpu_id', 'ram_id', 'storage_id', 'psu_id'];

    public static array $AVAILABLE_OPTIONAL_PC_COMPONENTS = ['network_card', 'sound_card'];
    public static array $AVAILABLE_OPTIONAL_PC_COMPONENTS_IDS = ['network_card_id', 'sound_card_id'];
}