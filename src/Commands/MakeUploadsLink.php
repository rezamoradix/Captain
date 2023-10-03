<?php

namespace Rey\Captain\Commands;

use CodeIgniter\CLI\BaseCommand;

class MakeUploadsLink extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Captain';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'make:uploads-link';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = '';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'make:uploads-link';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        if (is_windows())
        {
            $uploads_in_public = escapeshellarg(realpath(is_dir(ROOTPATH . 'public_html') ? ROOTPATH . 'public_html' : ROOTPATH . 'public'));
            $uploads_in_writable = escapeshellarg(realpath(WRITEPATH . 'uploads/'));
            exec("powershell ni -ItemType Symbolink -Path $uploads_in_public -Target $uploads_in_writable");
        }
    }

}
