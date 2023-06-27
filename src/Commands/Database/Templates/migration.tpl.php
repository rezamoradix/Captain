@php

namespace App\Database\Migrations;

use App\Libraries\EasyMigration;
use CodeIgniter\Database\Migration;

class @class extends Migration
{
    use EasyMigration;
    public $tableName = '@table';

    public function up()
    {
        @fields
    }

    public function down()
    {
        $this->dropTable();
    }
}