<?php

use Droath\ConsoleForm\Form;
use Droath\ProjectX\OptionFormAwareInterface;
use Droath\ProjectX\Platform\PlatformType;
use Droath\ProjectX\Platform\PlatformTypeInterface;
use Droath\ProjectX\TaskSubTypeInterface;

/**
 * Define test project type.
 */
class TestPlatformType extends PlatformType implements TaskSubTypeInterface, OptionFormAwareInterface, PlatformTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getLabel()
    {
        return 'Testing Platform Type';
    }

    /**
     * {@inheritdoc}
     */
    public static function getTypeId()
    {
        return 'testing_platform_type';
    }

    /**
     * Display an option form during the initialization process.
     *
     * @return Form
     */
    public function optionForm() {
        $form = new Form();

        $form->addField(
            new TextField('site_name', 'Site Name', true)
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function environments()
    {
        return [
            'dev' => 'Dev',
            'stg' => 'Stage',
            'prd' => 'Production'
        ];
    }
}
