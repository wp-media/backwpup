<?php

// -*- coding: utf-8 -*-

namespace Inpsyde\BackWPup\Settings;

/**
 * Class SettingsUpdatable.
 */
interface SettingUpdatable
{
    public function update();

    public function reset();
}
