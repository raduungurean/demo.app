<?php

use Phoenix\Migration\AbstractMigration;

class CreatePostsTable extends AbstractMigration
{
    protected function up(): void
    {
        $this->table('posts', 'id')
            ->setCharset('utf8mb4')
            ->setCollation('utf8mb4_unicode_ci')
            ->addColumn('id', 'integer', ['autoincrement' => true])
            ->addColumn('title', 'string', ['null' => true])
            ->addColumn('body', 'string', ['null' => true])
            ->addColumn('created_at', 'datetime', ['null' => true])
            ->addColumn('created_user_id', 'integer', ['null' => true])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addColumn('updated_user_id', 'integer', ['null' => true])
            ->addIndex('created_user_id', '', 'btree', 'created_user_id')
            ->addIndex('updated_user_id', '', 'btree', 'updated_user_id')
            ->create();
    }

    protected function down(): void
    {
        $this->table('posts')
            ->drop();
    }
}
