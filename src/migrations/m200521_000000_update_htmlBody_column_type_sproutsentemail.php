<?php

namespace barrelstrength\sproutsentemail\migrations;

use barrelstrength\sproutbasesentemail\migrations\m200521_000000_update_htmlBody_column_type;
use craft\db\Migration;

class m200521_000000_update_htmlBody_column_type_sproutsentemail extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $migration = new m200521_000000_update_htmlBody_column_type();

        ob_start();
        $migration->safeUp();
        ob_end_clean();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m200521_000000_update_htmlBody_column_type_sproutsentemail cannot be reverted.\n";

        return false;
    }
}
