Fork of wildside/userstamps that allows custom `created/updated/deleted_by` column names similer to Laravel's `created/updated/deleted_at` constants.

To change the name, simply add the following to whichever class you are using userstamps in.

    const CREATED_BY = 'new_created_by_col_name';
    const UPDATED_BY = 'new_updated_by_col_name';
    const DELETED_BY = 'new_deleted_by_col_name';