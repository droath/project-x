<?php

namespace Droath\ProjectX;

/**
 * Define option form aware interface.
 */
interface OptionFormAwareInterface
{
    /**
     * Display an option form during the initialization process.
     *
     * @return \Droath\ConsoleForm\Form
     */
    public function optionForm();
}
